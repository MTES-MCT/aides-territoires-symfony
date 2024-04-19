<?php

namespace App\Entity\Aid;

use App\Entity\Project\Project;
use App\Entity\User\User;
use App\Repository\Aid\AidProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AidProjectRepository::class)]
class AidProject
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

    #[ORM\ManyToOne(inversedBy: 'aidProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aid $aid = null;

    #[ORM\ManyToOne(inversedBy: 'aidProjects')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $creator = null;

    #[ORM\ManyToOne(inversedBy: 'aidProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column]
    private ?bool $aidDenied = false;

    #[ORM\Column]
    private ?bool $aidObtained = false;

    #[ORM\Column]
    private ?bool $aidPaid = false;

    #[ORM\Column]
    private ?bool $aidRequested = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeDenied = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeObtained = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timePaid = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeRequested = null;

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

    public function isAidDenied(): ?bool
    {
        return $this->aidDenied;
    }

    public function setAidDenied(bool $aidDenied): static
    {
        $this->aidDenied = $aidDenied;

        return $this;
    }

    public function isAidObtained(): ?bool
    {
        return $this->aidObtained;
    }

    public function setAidObtained(bool $aidObtained): static
    {
        $this->aidObtained = $aidObtained;

        return $this;
    }

    public function isAidPaid(): ?bool
    {
        return $this->aidPaid;
    }

    public function setAidPaid(bool $aidPaid): static
    {
        $this->aidPaid = $aidPaid;

        return $this;
    }

    public function isAidRequested(): ?bool
    {
        return $this->aidRequested;
    }

    public function setAidRequested(bool $aidRequested): static
    {
        $this->aidRequested = $aidRequested;

        return $this;
    }

    public function getTimeDenied(): ?\DateTimeInterface
    {
        return $this->timeDenied;
    }

    public function setTimeDenied(?\DateTimeInterface $timeDenied): static
    {
        $this->timeDenied = $timeDenied;

        return $this;
    }

    public function getTimeObtained(): ?\DateTimeInterface
    {
        return $this->timeObtained;
    }

    public function setTimeObtained(?\DateTimeInterface $timeObtained): static
    {
        $this->timeObtained = $timeObtained;

        return $this;
    }

    public function getTimePaid(): ?\DateTimeInterface
    {
        return $this->timePaid;
    }

    public function setTimePaid(?\DateTimeInterface $timePaid): static
    {
        $this->timePaid = $timePaid;

        return $this;
    }

    public function getTimeRequested(): ?\DateTimeInterface
    {
        return $this->timeRequested;
    }

    public function setTimeRequested(?\DateTimeInterface $timeRequested): static
    {
        $this->timeRequested = $timeRequested;

        return $this;
    }

    public function __toString(): string
    {
        $name = '';
        if ($this->getAid() instanceof Aid) {
            $name .= '(aide) '.$this->getAid()->__toString();
        }
        if ($this->getProject()) {
            $name .= ' | (projet) '.$this->getProject()->__toString();
        }
        if ($name == '') {
            $name = 'Aide projet';
        }
        return $name;
    }
}
