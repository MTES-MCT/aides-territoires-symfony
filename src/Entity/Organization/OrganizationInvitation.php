<?php

namespace App\Entity\Organization;

use App\Entity\User\User;
use App\Repository\Organization\OrganizationInvitationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Index(columns: ['date_create'], name: 'date_create_oi')]
#[ORM\Index(columns: ['date_accept'], name: 'date_accept_oi')]
#[ORM\Entity(repositoryClass: OrganizationInvitationRepository::class)]
class OrganizationInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeAccept = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAccept = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeRefuse = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRefuse = null;
    
    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'organizationInvitations')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'organizationGuests')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $guest = null;

    #[ORM\ManyToOne(inversedBy: 'organizationInvitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): static
    {
        $this->timeCreate = $timeCreate;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getTimeAccept(): ?\DateTimeInterface
    {
        return $this->timeAccept;
    }

    public function setTimeAccept(?\DateTimeInterface $timeAccept): static
    {
        $this->timeAccept = $timeAccept;

        return $this;
    }

    public function getDateAccept(): ?\DateTimeInterface
    {
        return $this->dateAccept;
    }

    public function setDateAccept(?\DateTimeInterface $dateAccept): static
    {
        $this->dateAccept = $dateAccept;

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

    public function getGuest(): ?User
    {
        return $this->guest;
    }

    public function setGuest(?User $guest): static
    {
        $this->guest = $guest;

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

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTimeRefuse(): ?\DateTimeInterface
    {
        return $this->timeRefuse;
    }

    public function setTimeRefuse(?\DateTimeInterface $timeRefuse): static
    {
        $this->timeRefuse = $timeRefuse;

        return $this;
    }

    public function getDateRefuse(): ?\DateTimeInterface
    {
        return $this->dateRefuse;
    }

    public function setDateRefuse(?\DateTimeInterface $dateRefuse): static
    {
        $this->dateRefuse = $dateRefuse;

        return $this;
    }
}
