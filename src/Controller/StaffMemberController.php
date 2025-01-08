<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\StaffMember;
use App\Repository\BedRepository;
use App\Repository\EventRepository;
use App\Repository\StaffMemberRepository;
use App\Repository\UserRepository;
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

    #[Route('/api/admin/update-role/{id}', name: 'update_staff_role', methods: ['PUT'])]
    public function updateRole(int $id, Request $request, UserRepository $userRepository, Security $security, EntityManagerInterface $manager): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $currentUser = $security->getUser();
        if ($currentUser && $currentUser->getId() === $id) {
            return $this->json(['error' => 'You cannot edit your own role'], 403);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $role = $data['role'] ?? null;

        if (!$role) {
            return $this->json(['error' => 'Missing role'], 400);
        }

        $user->setRoles([$role]);

        $manager->persist($user);
        $manager->flush();

        return $this->json([
            'message' => 'Role updated successfully',
            'roles' => $user->getRoles()
        ], 200);
    }



    #[Route('/api/admin/users', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(UserRepository $userRepository, Security $security): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied. Only administrators can access this route.'], 403);
        }

        $users = $userRepository->findAll();

        return $this->json($users, 200, [], ['groups' => 'userjson']);
    }


    #[Route('api/staff/edit', name: 'edit', methods: ['PUT'])]
    public function editStaffProfile(Request $request, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $staffMember = $user->getStaffMember() ?? new StaffMember();
        $data = json_decode($request->getContent(), true);

        $staffMember->setFirstName($data['firstName'] ?? $staffMember->getFirstName());
        $staffMember->setLastName($data['lastName'] ?? $staffMember->getLastName());
        $staffMember->setDescription($data['description'] ?? $staffMember->getDescription());
        $staffMember->setMember($user);
        $manager->persist($staffMember);
        $manager->flush();

        return $this->json([
            'message' => 'Staff profile updated successfully',
            'staff' => $staffMember,
        ], 200, [], ['groups' => 'staff:detail']);
    }

    #[Route('api/list/staff', name: 'list', methods: ['GET'])]
    public function listStaffMembers(StaffMemberRepository $repository): JsonResponse
    {
        $staffMembers = $repository->findAll();

        return $this->json($staffMembers, 200, [], ['groups' => 'staff:detail']);
    }


}
