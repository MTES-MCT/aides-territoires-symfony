<?php

namespace App\Entity\Organization;

use App\Entity\User\User;
use App\Repository\Organization\OrganizationAccessRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: OrganizationAccessRepository::class)]
class OrganizationAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'organizationAccesses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'organizationAccesses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column]
    private ?bool $administrator = false;

    #[ORM\Column]
    private ?bool $editAid = false;

    #[ORM\Column]
    private ?bool $editPortal = false;

    #[ORM\Column]
    private ?bool $editBacker = false;

    #[ORM\Column]
    private ?bool $editProject = false;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function isAdministrator(): ?bool
    {
        return $this->administrator;
    }

    public function setAdministrator(bool $administrator): static
    {
        $this->administrator = $administrator;

        return $this;
    }

    public function isEditAid(): ?bool
    {
        return $this->editAid;
    }

    public function setEditAid(bool $editAid): static
    {
        $this->editAid = $editAid;

        return $this;
    }

    public function isEditPortal(): ?bool
    {
        return $this->editPortal;
    }

    public function setEditPortal(bool $editPortal): static
    {
        $this->editPortal = $editPortal;

        return $this;
    }

    public function isEditBacker(): ?bool
    {
        return $this->editBacker;
    }

    public function setEditBacker(bool $editBacker): static
    {
        $this->editBacker = $editBacker;

        return $this;
    }

    public function isEditProject(): ?bool
    {
        return $this->editProject;
    }

    public function setEditProject(bool $editProject): static
    {
        $this->editProject = $editProject;

        return $this;
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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }
}
