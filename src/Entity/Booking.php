<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("booking:read")]
    private ?int $id = null;

    /**
     * @var Collection<int, Room>
     */
    #[ORM\ManyToMany(targetEntity: Room::class, inversedBy: 'bookings', cascade: ['persist'])]
    #[Groups("booking:read")]
    private Collection $rooms;

    /**
     * @var Collection<int, Bed>
     */
    #[ORM\ManyToMany(targetEntity: Bed::class, inversedBy: 'bookings', cascade: ['persist'])]
    #[Groups("booking:read")]
    private Collection $beds;


    #[ORM\Column]
    #[Groups("booking:read")]
    private ?float $totalAmount = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("booking:read")]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups("booking:read")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups("booking:read")]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("booking:read")]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("booking:read")]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("booking:read")]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("booking:read")]
    private ?string $phoneNumber = null;

    private $paymentIntentId;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    #[Groups("booking:read")]
    private ?array $extras = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[Groups("booking:read")]
    private ?User $client = null;


    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->beds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
        }

        return $this;
    }

    public function removeRoom(Room $room): static
    {
        $this->rooms->removeElement($room);

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
        }

        return $this;
    }

    public function removeBed(Bed $bed): static
    {
        $this->beds->removeElement($bed);

        return $this;
    }


    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function setPaymentIntentId(?string $paymentIntentId): self
    {
        $this->paymentIntentId = $paymentIntentId;

        return $this;
    }

    public function calculateTotal(): float
    {
        $total = $this->totalAmount  ?? 0;;

        if (isset($this->extras['towels'])) {
            $total += $this->extras['towels'] * 6;
        }

        if (isset($this->extras['luggage_service'])) {
            $total += $this->extras['luggage_service'] * 3;
        }

        if (isset($this->extras['breakfast']) && $this->extras['breakfast'] === true) {
            $numberOfBeds = count($this->getBeds());
            $nights = $this->getStartDate()->diff($this->getEndDate())->days;

            $total += $numberOfBeds * $nights * 6;
        }

        return $total;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }
}
