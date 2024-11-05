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

    #[Route('/api/change-bed-status/{bedId}', name: 'change_bed_status', methods: ['PUT'])]
    public function changeBedStatus(int $bedId, Request $request, BedRepository $bedRepository, Security $security, EntityManagerInterface $manager): JsonResponse
    {

        if (!$this->isGranted('ROLE_CLEANER')) {
            throw new AccessDeniedException('You must be login');
        }

        $bed = $bedRepository->find($bedId);
        if (!$bed) {
            return $this->json(['error' =>'bed not exist'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['isCleaned'])) {
            return $this->json(['error' => 'missing data'], 400);
        }

        $status = (bool)$data['isCleaned'];

        $bed->setCleaned($status);
        $manager->flush();

        return $this->json($bed, 200, [], ['groups' => 'bedjson']);
    }


    #[Route('/api/staff/update-role/{id}', name: 'update_staff_role', methods: ['PUT'])]
    public function updateRole(int $id, Request $request, UserRepository $userRepository, Security $security, EntityManagerInterface $manager): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not exist'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['role']) || !isset($data['action'])) {
            return $this->json(['error' => 'missing data'], 400);
        }

        $role = $data['role'];
        $action = $data['action'];

        if ($action === 'add') {
            if (!in_array($role, $user->getRoles(), true)) {
                $user->addRole($role);
            } else {
                return $this->json(['message' => 'role already exist'], 200);
            }
        } elseif ($action === 'remove') {
            $roles = $user->getRoles();
            if (in_array($role, $roles, true)) {
                $roles = array_diff($roles, [$role]);
                $user->setRoles(array_values($roles));
            } else {
                return $this->json(['message' => 'role does not exist.'], 200);
            }
        } else {
            return $this->json(['error' => 'invalid action'], 400);
        }
        $manager->persist($user);
        $manager->flush();

        return $this->json($user, 200, [], ['groups' => 'staff:detail']);
    }


}
