<?php

namespace App\Controller;

use App\Entity\BedReservationPeriod;
use App\Entity\Booking;
use App\Entity\Room;
use App\Repository\BedRepository;
use App\Repository\BookingRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);
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

            }

        }
        if (isset($data['extras'])) {
            $booking->setExtras($data['extras']);
        }

        $totalAmountWithExtras = $booking->calculateTotal();
        $booking->setTotalAmount($totalAmountWithExtras);

        Stripe::setApiKey('sk_test_51PCNM106IAn0kHEWABe5CqIL2llQqOwqFQZzgUlyKQGDngtaB34da87a8BuwZ2oTIalfIJ2riteobPqNuwS5emxi00VjWbKlWl'); // Remplace par ta clé secrète

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $booking->getTotalAmount() * 100,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        $manager->persist($booking);
        $manager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Booking created, waiting for payment',
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }

    #[Route('/api/confirm/booking/{id}', name: 'confirm_booking')]
    public function confirmBooking($id, BookingRepository $bookingRepository, EntityManagerInterface $manager): JsonResponse
    {
        $booking = $bookingRepository->find($id);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }

        $booking->setStatus('confirmed');
        $manager->persist($booking);
        $manager->flush();

        return $this->json(['success' => true, 'message' => 'Booking confirmed']);
    }

}
