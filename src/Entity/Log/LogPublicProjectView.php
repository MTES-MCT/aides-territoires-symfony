<?php

namespace App\Entity\Log;

use App\Entity\Organization\Organization;
use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Repository\Log\LogPublicProjectViewRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogPublicProjectViewRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lppv')]
class LogPublicProjectView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectViews')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectViews')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectViews')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?User $user = null;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
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
}
