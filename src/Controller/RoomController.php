<?php

namespace App\Controller;

use App\Entity\Bed;
use App\Entity\Room;
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

class RoomController extends AbstractController
{
    #[Route('/api/rooms', name: 'app_room')]
    public function index(RoomRepository $roomRepository): Response
    {
        $rooms = $roomRepository->findAll();
        return $this->json($rooms, 200, [], ['groups' => 'roomsjson']);
    }

    #[Route('/api/staff/create/room', name: 'create_room', methods: ['POST'])]
    public function create(Request $request, RoomRepository $roomRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $room = $serializer->deserialize($request->getContent(), Room::class, 'json');

        if (!isset($data['pricePerNight']) || !is_numeric($data['pricePerNight'])) {
            return $this->json(['error' => 'Invalid or missing pricePerNight'], 400);
        }

        $pricePerNight = (float) $data['pricePerNight'];

        $totalBeds = $room->getTotalBeds();
        $room->setAvailableBeds($totalBeds);

        for ($i = 0; $i < $totalBeds; $i++) {
            $bed = new Bed();
            $bed->setRoom($room);
            $bed->setPricePerNight($pricePerNight);
            $manager->persist($bed);
        }

        $manager->persist($room);
        $manager->flush();

        return $this->json(['message' => 'Room created successfully'], 200);
    }

    #[Route('/api/staff/delete/room/{id}', name: 'app_room_delete', methods: ['DELETE'])]
    public function delete(Request $request, Room $room, Security $security, EntityManagerInterface $manager): Response
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }

        $manager->remove($room);
        $manager->flush();


        return $this->json(['message' => 'Room deleted successfully'], 200);

    }

    #[Route('/api/staff/edit/room/{id}', name: 'edit_room', methods: ['PUT'])]
    public function edit(Request $request, Room $room, RoomRepository $roomRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }


        $serializer->deserialize($request->getContent(), Room::class, 'json', ['object_to_populate' => $room]);

        $manager->flush();

        return $this->json($room, 200, [], ['groups' => ['roomsjson']]);

    }

}
