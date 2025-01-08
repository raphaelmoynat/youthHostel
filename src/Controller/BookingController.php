<?php

namespace App\Controller;

use App\Entity\BedReservationPeriod;
use App\Entity\Booking;
use App\Entity\Room;
use App\Repository\BedRepository;
use App\Repository\BookingRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Stripe;
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
    #[Route('/api/staff/bookings', name: 'app_booking')]
    public function index(BookingRepository $bookingRepository): Response
    {
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'Permission denied'], 403);
        }

        $bookings = $bookingRepository->findAll();
        return $this->json($bookings, 200, [], ['groups' => 'bookings']);
    }


    #[Route('/api/create/booking', name: 'create_booking')]
    public function create(Request $request,  BedRepository $bedRepository, RoomRepository $roomRepository, SerializerInterface $serializer, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['error' => 'You must be logged in to create a booking.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);

        $interval = $startDate->diff($endDate);
        $nights = $interval->days;

        $booking = new Booking();
        $booking->setStartDate(new \DateTime($data['startDate']));
        $booking->setEndDate(new \DateTime($data['endDate']));
        $booking->setEmail($data['email']);
        $booking->setFirstName($data['firstName']);
        $booking->setLastName($data['lastName']);
        $booking->setPhoneNumber($data['phoneNumber']);
        $booking->setStatus('waiting paiement');
        $booking->setClient($user);

        $totalAmount = 0;

        foreach ($data['rooms'] as $roomData) {
            $room = $roomRepository->find($roomData['id']);

            if (!$room) {
                return $this->json(['error' => "Room not found"], 404);
            }

            $availableBedsDuringPeriod = $room->getAvailableBedsDuringPeriod($startDate, $endDate);

            if ($availableBedsDuringPeriod < count($roomData['beds'])) {
                return $this->json(['error' => "Not enough beds available in the room for the selected period"], 400);
            }

            $booking->addRoom($room);

            foreach ($roomData['beds'] as $bedData) {
                $bed = $bedRepository->find($bedData['id']);


                if (!$bed->isAvailableDuringPeriod($startDate, $endDate)) {
                    return $this->json(['error' => "Bed {$bed->getId()} is not available during the selected period"], 400);
                }

                if (!$bed || $bed->getRoom() !== $room) {
                    return $this->json(['error' => "Bed is not found"], 400);
                }

                $reservationPeriod = new BedReservationPeriod();
                $reservationPeriod->setBed($bed);
                $reservationPeriod->setStartDate($startDate);
                $reservationPeriod->setEndDate($endDate);

                $bed->addBedReservationPeriod($reservationPeriod);
                $manager->persist($reservationPeriod);

                $room->deleteAvailableBeds();
                $booking->addBed($bed);

                $totalAmount += $bed->getPricePerNight() * $nights;


            }


        }
        if (isset($data['extras'])) {
            $booking->setExtras($data['extras']);
        }

        $totalAmountWithExtras = $booking->calculateTotal() + $totalAmount;
        $booking->setTotalAmount($totalAmountWithExtras);

        $manager->persist($booking);
        $manager->flush();

        Stripe::setApiKey('sk_test_51PCNM106IAn0kHEWABe5CqIL2llQqOwqFQZzgUlyKQGDngtaB34da87a8BuwZ2oTIalfIJ2riteobPqNuwS5emxi00VjWbKlWl');

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $booking->getTotalAmount() * 100,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'reservationId' => $booking->getId(),
                    'firstName' => $booking->getFirstName(),
                    'lastName' => $booking->getLastName(),
                    'email' => $booking->getEmail(),
                    'phoneNumber' => $booking->getPhoneNumber(),
                ],
                'description' => 'Booking for ' . $booking->getFirstName() . ' ' . $booking->getLastName(),
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }



        return $this->json([
            'success' => true,
            'message' => 'Booking created, waiting for payment',
            'paymentIntentId' => $paymentIntent->id,
            'status' => $booking->getStatus(),
            'totalAmount' => $booking->getTotalAmount()
        ]);
    }

    #[Route('/api/staff/confirm/booking/{id}', name: 'confirm_booking')]
    public function confirmBooking($id, BookingRepository $bookingRepository, EntityManagerInterface $manager, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$security->isGranted('ROLE_ADMIN') && !$security->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'You do not have permission to confirm bookings'], 403);
        }

        $booking = $bookingRepository->find($id);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }

        $booking->setStatus('confirmed');
        $manager->persist($booking);
        $manager->flush();

        return $this->json(['success' => true, 'message' => 'Booking confirmed']);
    }



    #[Route('/api/staff/cancel/booking/{id}', name: 'cancel_booking')]
    public function cancelBooking($id, BookingRepository $bookingRepository, EntityManagerInterface $manager, Security $security): JsonResponse
    {

        $user = $security->getUser();

        if (!$security->isGranted('ROLE_ADMIN') && !$security->isGranted('ROLE_STAFF')) {
            return $this->json(['error' => 'You do not have permission to delete bookings'], 403);
        }

        $booking = $bookingRepository->find($id);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }

        foreach ($booking->getBeds() as $bed) {
            foreach ($bed->getBedReservationPeriods() as $reservationPeriod) {
                if ($reservationPeriod->getStartDate() <= $booking->getStartDate() && $reservationPeriod->getEndDate() >= $booking->getEndDate()) {
                    $bed->removeBedReservationPeriod($reservationPeriod);
                    $manager->remove($reservationPeriod);
                }
            }
        }

        $booking->setStatus('cancelled');
        $manager->persist($booking);
        $manager->flush();

        return $this->json(['success' => true, 'message' => 'Booking cancelled and beds are available']);
    }


    #[Route('/api/my-bookings', name: 'my_bookings', methods: ['GET'])]
    public function myBookings(BookingRepository $bookingRepository, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => 'You must be logged in to view your bookings.'], 403);
        }

        $bookings = $bookingRepository->findBy(['client' => $user]);

        return $this->json($bookings, 200, [], ['groups' => 'bookings']);
    }





}
