<?php

namespace App\Entity;

use App\Repository\StaffMemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StaffMemberRepository::class)]
class StaffMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("staff:detail")]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("staff:detail")]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("staff:detail")]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups("staff:detail")]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
