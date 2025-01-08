<?php

namespace App\Controller;

use App\Entity\Bed;
use App\Entity\Room;
use App\Repository\BedRepository;
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

class BedController extends AbstractController
{
    #[Route('/api/bed', name: 'app_bed')]
    public function index(): Response
    {
        return $this->render('bed/index.html.twig', [
            'controller_name' => 'BedController',
        ]);
    }

    #[Route('/api/staff/create/bed', name: 'create_bed', methods: ['POST'])]
    public function create(Request $request, BedRepository $bedRepository, RoomRepository $roomRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $bed = $serializer->deserialize($request->getContent(), Bed::class, 'json');

        $roomId = $data['room'];
        $room = $roomRepository->find($roomId);

        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }

        if (!isset($data['pricePerNight']) || !is_numeric($data['pricePerNight'])) {
            return $this->json(['error' => 'Invalid or missing pricePerNight'], 400);
        }

        $bed = new Bed();

        $bed->setPricePerNight((float) $data['pricePerNight']);
        $bed->setRoom($room);


        $manager->persist($bed);
        $manager->flush();

        return $this->json($bed, 200, [], ['groups' => ['bedjson']]);


    }

    #[Route('/api/beds', name: 'get_all_beds', methods: ['GET'])]
    public function getAllBeds(BedRepository $bedRepository, SerializerInterface $serializer): JsonResponse
    {
        $beds = $bedRepository->findAll();
        return $this->json($beds, 200, [], ['groups' => 'bedjson']);
    }

    #[Route('/api/staff/bed/clean/{id}', name: 'bed_clean', methods: ['PATCH'])]
    public function markBedAsClean(int $id, BedRepository $bedRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $bed = $bedRepository->find($id);

        if (!$bed) {
            return new JsonResponse(['error' => 'Bed not found'], 404);
        }

        $bed->markAsClean();
        $entityManager->flush();

        return new JsonResponse(['message' => 'Bed is cleaned successfully']);
    }

    #[Route('/api/staff/bed/not-clean/{id}', name: 'bed_not_clean', methods: ['PATCH'])]
    public function markBedAsNotClean(int $id, BedRepository $bedRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $bed = $bedRepository->find($id);

        if (!$bed) {
            return new JsonResponse(['error' => 'Bed not found'], 404);
        }

        $bed->markAsNotClean();
        $entityManager->flush();

        return new JsonResponse(['message' => 'Bed marked as not clean successfully']);
    }


    #[Route('/api/staff/bed/delete/{id}', name:'delete_bed', methods:["DELETE"])]
    public function deleteBed(int $id, BedRepository $bedRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $bed = $bedRepository->find($id);

        if (!$bed) {
            return new JsonResponse(['error' => 'Bed not found'], 404);
        }
        $entityManager->remove($bed);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Bed deleted successfully'], 200);
    }
}
