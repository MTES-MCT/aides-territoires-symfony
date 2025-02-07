<?php

namespace App\Entity\Site;

use App\Entity\User\User;
use App\Repository\Site\AbTestUserRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbTestUserRepository::class)]
#[ORM\Index(columns: ['version'], name: 'version_ab_test_user')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_ab_test_user')]
class AbTestUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abTestUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AbTest $abTest = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(length: 50)]
    private ?string $version = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAbTest(): ?AbTest
    {
        return $this->abTest;
    }

    public function setAbTest(?AbTest $abTest): static
    {
        $this->abTest = $abTest;

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

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }
}
