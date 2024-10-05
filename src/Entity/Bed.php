<?php

namespace App\Entity;

use App\Repository\BedRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BedRepository::class)]
class Bed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("roomsjson")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'beds', cascade: ['persist', 'remove'])]
    private ?Room $room = null;

    #[ORM\Column]
    #[Groups("roomsjson")]
    private ?int $number = null;

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

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }
}
