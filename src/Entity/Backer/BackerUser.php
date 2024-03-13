<?php

namespace App\Entity\Backer;

use App\Entity\User\User;
use App\Repository\Backer\BackerUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BackerUserRepository::class)]
class BackerUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'backerUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Backer $backer = null;

    #[ORM\ManyToOne(inversedBy: 'backerUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $administrator = false;

    #[ORM\Column]
    private ?bool $editor = false;

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

    public function isAdministrator(): ?bool
    {
        return $this->administrator;
    }

    public function setAdministrator(bool $administrator): static
    {
        $this->administrator = $administrator;

        return $this;
    }

    public function isEditor(): ?bool
    {
        return $this->editor;
    }

    public function setEditor(bool $editor): static
    {
        $this->editor = $editor;

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

    public function __toString(): string
    {
        if ($this->getUser() instanceof User) {
            return $this->getUser()->getEmail();
        } else {
            return '';
        }
    }
}
