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

    #[ORM\OneToOne(mappedBy: 'staffMember', cascade: ['persist', 'remove'])]
    private ?User $member = null;

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

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): static
    {
        // unset the owning side of the relation if necessary
        if ($member === null && $this->member !== null) {
            $this->member->setStaffMember(null);
        }

        // set the owning side of the relation if necessary
        if ($member !== null && $member->getStaffMember() !== $this) {
            $member->setStaffMember($this);
        }

        $this->member = $member;

        return $this;
    }

}
