<?php

namespace App\Entity\Log;

use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Repository\Log\LogBackerEditRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogBackerEditRepository::class)]
class LogBackerEdit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logBackerEdits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Backer $backer = null;

    #[ORM\ManyToOne(inversedBy: 'logBackerEdits')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'logBackerEdits')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timecreate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBacker(): ?Backer
    {
        return $this->backer;
    }

    public function setBacker(?Backer $backer): static
    {
        $this->backer = $backer;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getTimecreate(): ?\DateTimeInterface
    {
        return $this->timecreate;
    }

    public function setTimecreate(\DateTimeInterface $timecreate): static
    {
        $this->timecreate = $timecreate;

        return $this;
    }
}
