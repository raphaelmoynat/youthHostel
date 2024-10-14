<?php

namespace App\Controller;

use App\Entity\Bed;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

class EventController extends AbstractController
{
    #[Route('/api/events', name: 'app_event')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        return $this->json($events, 200, [], ['groups' => 'event_list']);
    }

    #[Route('/api/create/event', name: 'create_event', methods: ['POST'])]
    public function create(Request $request, EventRepository $eventRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $event = $serializer->deserialize($request->getContent(), Event::class, 'json');
        $author = $security->getUser();
        if (!$author) {
            throw new AccessDeniedException('You must be logged in to create an event.');
        }

        $manager->persist($event);
        $manager->flush();

        return $this->json(['message' => 'Event created successfully'], 200);
    }

    #[Route('/api/delete/event/{id}', name: 'app_event_delete', methods: ['DELETE'])]
    public function delete(Request $request, Event $event, Security $security, EntityManagerInterface $manager): Response
    {
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $manager->remove($event);
        $manager->flush();


        return $this->json(['message' => 'Event deleted successfully'], 200);

    }

    #[Route('/api/edit/event/{id}', name: 'edit_event', methods: ['PUT'])]
    public function edit(Request $request, Event $event, EventRepository $eventRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }


        $serializer->deserialize($request->getContent(), Event::class, 'json', ['object_to_populate' => $event]);

        $manager->flush();

        return $this->json($event, 200, [], ['groups' => ['eventroomsjsons']]);

    }

    #[Route('/api/event/register/{id}', name: 'register_event', methods: ['POST'])]
    public function registerToEvent($id, EventRepository $eventRepository, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'You must be logged in to register for an event.'], Response::HTTP_UNAUTHORIZED);
        }

        $event = $eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        if ($event->getAvailablePlaces() <= 0) {
            return $this->json(['error' => 'No more available spots for this event.'], Response::HTTP_BAD_REQUEST);
        }

        if ($event->getParticipants()->contains($user)) {
            return $this->json(['error' => 'You are already registered for this event.'], Response::HTTP_CONFLICT);
        }

        $event->addParticipant($user);

        $manager->flush();

        return $this->json(['message' => 'Successfully registered to the event!', 'availablePlaces' => $event->getAvailablePlaces()], Response::HTTP_OK);
    }

    #[Route('/api/event/cancel/{id}', name: 'cancel_event_registration', methods: ['DELETE'])]
    public function cancelRegistration($id, EventRepository $eventRepository, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'You must be logged in to cancel a registration.'], Response::HTTP_UNAUTHORIZED);
        }

        $event = $eventRepository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$event->getParticipants()->contains($user)) {
            return $this->json(['error' => 'You are not registered for this event.'], Response::HTTP_BAD_REQUEST);
        }

        $event->removeParticipant($user);

        $manager->flush();

        return $this->json(['message' => 'Your registration has been successfully canceled.', 'availablePlaces' => $event->getAvailablePlaces()], Response::HTTP_OK);
    }


}
