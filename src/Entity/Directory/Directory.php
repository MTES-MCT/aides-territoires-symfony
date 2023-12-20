<?php

namespace App\Entity\Directory;

use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Repository\Directory\DirectoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// TODO voir utilite, faire BO si besoin
#[ORM\Entity(repositoryClass: DirectoryRepository::class)]
class Directory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 150)]
    private ?string $firstname = null;

    #[ORM\Column(length: 150)]
    private ?string $lastname = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $mobile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $time_create = null;

    #[ORM\ManyToOne(inversedBy: 'directories')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'directories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->time_create;
    }

    public function setTimeCreate(\DateTimeInterface $time_create): static
    {
        $this->time_create = $time_create;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

   
}
