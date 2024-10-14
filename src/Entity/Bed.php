<?php

namespace App\Entity;

use App\Repository\BedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BedRepository::class)]
class Bed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["roomsjson", "bedjson", "bookings"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'beds', cascade: ['persist', 'remove'])]
    #[Groups(["bedjson"])]
    private ?Room $room = null;


    /**
     * @var Collection<int, Booking>
     */
    #[ORM\ManyToMany(targetEntity: Booking::class, mappedBy: 'beds')]
    private Collection $bookings;



    /**
     * @var Collection<int, BedReservationPeriod>
     */
    #[ORM\OneToMany(targetEntity: BedReservationPeriod::class, mappedBy: 'bed')]
    private Collection $bedReservationPeriods;

    #[ORM\Column]
    #[Groups("roomsjson")]
    private ?bool $isCurrentlyOccupied = false;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->bedReservationPeriods = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }


    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->addBed($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            $booking->removeBed($this);
        }

        return $this;
    }



    /**
     * @return Collection<int, BedReservationPeriod>
     */
    public function getBedReservationPeriods(): Collection
    {
        return $this->bedReservationPeriods;
    }

    public function addBedReservationPeriod(BedReservationPeriod $bedReservationPeriod): static
    {
        if (!$this->bedReservationPeriods->contains($bedReservationPeriod)) {
            $this->bedReservationPeriods->add($bedReservationPeriod);
            $bedReservationPeriod->setBed($this);
        }

        return $this;
    }

    public function removeBedReservationPeriod(BedReservationPeriod $bedReservationPeriod): static
    {
        if ($this->bedReservationPeriods->removeElement($bedReservationPeriod)) {
            // set the owning side to null (unless already changed)
            if ($bedReservationPeriod->getBed() === $this) {
                $bedReservationPeriod->setBed(null);
            }
        }

        return $this;
    }

    public function isAvailableDuringPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): bool
    {
        foreach ($this->bedReservationPeriods as $period) {
            if (
                ($startDate < $period->getEndDate() && $endDate > $period->getStartDate())
            ) {
                return false;
            }
        }
        return true;
    }

    public function isCurrentlyOccupied(): bool
    {
        $now = new \DateTime();

        foreach ($this->bedReservationPeriods as $reservationPeriod) {
            if ($reservationPeriod->getStartDate() <= $now && $reservationPeriod->getEndDate() >= $now) {
                return true;
            }
        }

        return false;
    }

}
