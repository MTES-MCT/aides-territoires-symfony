<?php

namespace App\Entity\Aid;

use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Repository\Aid\AidSuggestedAidProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AidSuggestedAidProjectRepository::class)]
class AidSuggestedAidProject
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

    #[ORM\ManyToOne(inversedBy: 'aidSuggestedAidProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aid $aid = null;

    #[ORM\ManyToOne(inversedBy: 'aidSuggestedAidProjects')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $creator = null;

    #[ORM\ManyToOne(inversedBy: 'aidSuggestedAidProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeAssociated = null;

    #[ORM\Column]
    private ?bool $isAssociated = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeRejected = null;

    #[ORM\Column]
    private ?bool $isRejected = false;

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

    public function getAid(): ?Aid
    {
        return $this->aid;
    }

    public function setAid(?Aid $aid): static
    {
        $this->aid = $aid;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

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

    public function getTimeAssociated(): ?\DateTimeInterface
    {
        return $this->timeAssociated;
    }

    public function setTimeAssociated(?\DateTimeInterface $timeAssociated): static
    {
        $this->timeAssociated = $timeAssociated;

        return $this;
    }

    public function isIsAssociated(): ?bool
    {
        return $this->isAssociated;
    }

    public function setIsAssociated(bool $isAssociated): static
    {
        $this->isAssociated = $isAssociated;

        return $this;
    }

    public function getTimeRejected(): ?\DateTimeInterface
    {
        return $this->timeRejected;
    }

    public function setTimeRejected(?\DateTimeInterface $timeRejected): static
    {
        $this->timeRejected = $timeRejected;

        return $this;
    }

    public function isIsRejected(): ?bool
    {
        return $this->isRejected;
    }

    public function setIsRejected(bool $isRejected): static
    {
        $this->isRejected = $isRejected;

        return $this;
    }
}
