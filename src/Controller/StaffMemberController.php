<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\StaffMember;
use App\Repository\EventRepository;
use App\Repository\StaffMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

class StaffMemberController extends AbstractController
{
    #[Route('/api/staff', name: 'app_staff_add')]
    public function index(StaffMemberRepository $staffMemberRepository): Response
    {

        $staffMembers = $staffMemberRepository->findAll();
        return $this->json($staffMembers, 200, [], ['groups' => 'staff:detail']);

    }

    #[Route('/api/staff/create', name: 'create_staff', methods: ['POST'])]
    public function create(Request $request, StaffMemberRepository $staffMemberRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $staff = $serializer->deserialize($request->getContent(), StaffMember::class, 'json');
        $author = $security->getUser();
        if (!$author) {
            throw new AccessDeniedException('You must be logged in to add a staff member.');
        }

        $manager->persist($staff);
        $manager->flush();

        return $this->json(['message' => 'StaffMember created successfully'], 200);
    }

    #[Route('/api/delete/staff/{id}', name: 'app_staff_delete', methods: ['DELETE'])]
    public function delete(Request $request, StaffMember $staffMember, Security $security, EntityManagerInterface $manager): Response
    {
        if (!$staffMember) {
            return $this->json(['error' => 'Staff member not found'], 404);
        }

        $manager->remove($staffMember);
        $manager->flush();


        return $this->json(['message' => 'Staff Member deleted successfully'], 200);

    }

    #[Route('/api/edit/staff/{id}', name: 'edit_staff', methods: ['PUT'])]
    public function edit(Request $request, StaffMember $staffMember, EventRepository $eventRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$staffMember) {
            return $this->json(['error' => 'Staff member not found'], 404);
        }


        $serializer->deserialize($request->getContent(), StaffMember::class, 'json', ['object_to_populate' => $staffMember]);

        $manager->flush();

        return $this->json($staffMember, 200, [], ['groups' => ['staff:detail']]);

    }

}
