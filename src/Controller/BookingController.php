<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Room;
use App\Repository\BedRepository;
use App\Repository\BookingRepository;
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

class BookingController extends AbstractController
{
    #[Route('/api/bookings', name: 'app_booking')]
    public function index(BookingRepository $bookingRepository): Response
    {
        $bookings = $bookingRepository->findAll();
        return $this->json($bookings, 200, [], ['groups' => 'bookings']);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/api/create/booking', name: 'create_booking')]
    public function create(Request $request,  BedRepository $bedRepository, RoomRepository $roomRepository, SerializerInterface $serializer, EntityManagerInterface $manager,): JsonResponse
    {
            $data = json_decode($request->getContent(), true);
            $booking = new Booking();
            $booking->setStartDate(new \DateTime($data['startDate']));
            $booking->setEndDate(new \DateTime($data['endDate']));
            $booking->setEmail($data['email']);
            $booking->setFirstName($data['firstName']);
            $booking->setLastName($data['lastName']);
            $booking->setPhoneNumber($data['phoneNumber']);
            $booking->setTotalAmount($data['totalAmount']);
            $booking->setStatus('waiting paiement');

        foreach ($data['rooms'] as $roomData) {
                $room = $roomRepository->find($roomData['id']);

                if (!$room) {
                    return $this->json(['error' => "Room not found"], 404);
                }

                if ($room->getAvailableBeds() <= 0) {
                    return $this->json(['error' => "Room has no available beds"], 400);
                }

            $booking->addRoom($room);

                foreach ($roomData['beds'] as $bedData) {
                    $bed = $bedRepository->find($bedData['id']);

                    if (!$bed || $bed->getRoom() !== $room) {
                        return $this->json(['error' => "Bed is not found"], 400);
                    }

                    if (!$bed->isAvailable()) {
                        return $this->json(['error' => "Bed is not available"], 400);
                    }


                    $bed->occupy();
                    $room->deleteAvailableBeds();
                    $booking->addBed($bed);

                }

            }


            $manager->persist($booking);
            $manager->flush();

            return $this->json(['success' => true, 'message' => 'Booking confirmed']);


        }
}
