<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["roomsjson", "bedjson"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["roomsjson", "bedjson"])]
    private ?string $name = null;

    /**
     * @var Collection<int, Bed>
     */
    #[ORM\OneToMany(targetEntity: Bed::class, mappedBy: 'room', cascade: ['persist', 'remove'])]
    #[Groups("roomsjson")]
    private Collection $beds;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\ManyToMany(targetEntity: Booking::class, mappedBy: 'rooms')]
    private Collection $bookings;

    /**
     * @var Collection<int, Booking>
     */

    public function __construct()
    {
        $this->beds = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Bed>
     */
    public function getBeds(): Collection
    {
        return $this->beds;
    }

    public function addBed(Bed $bed): static
    {
        if (!$this->beds->contains($bed)) {
            $this->beds->add($bed);
            $bed->setRoom($this);
        }

        return $this;
    }

    public function removeBed(Bed $bed): static
    {
        if ($this->beds->removeElement($bed)) {
            // set the owning side to null (unless already changed)
            if ($bed->getRoom() === $this) {
                $bed->setRoom(null);
            }
        }

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
            $booking->addRoom($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            $booking->removeRoom($this);
        }

        return $this;
    }

}
