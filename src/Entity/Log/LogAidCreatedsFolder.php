<?php

namespace App\Entity\Log;

use App\Entity\Aid\Aid;
use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Repository\Log\LogAidCreatedsFolderRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: LogAidCreatedsFolderRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_laccf')]
class LogAidCreatedsFolder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $dsFolderUrl = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $dsFolderId = null;

    #[ORM\Column]
    private ?int $dsFolderNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logAidCreatedsFolders')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Aid $aid = null;

    #[ORM\ManyToOne(inversedBy: 'logAidCreatedsFolders')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'logAidCreatedsFolders')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDsFolderUrl(): ?string
    {
        return $this->dsFolderUrl;
    }

    public function setDsFolderUrl(string $dsFolderUrl): static
    {
        $this->dsFolderUrl = $dsFolderUrl;

        return $this;
    }

    public function getDsFolderId(): ?string
    {
        return $this->dsFolderId;
    }

    public function setDsFolderId(string $dsFolderId): static
    {
        $this->dsFolderId = $dsFolderId;

        return $this;
    }

    public function getDsFolderNumber(): ?int
    {
        return $this->dsFolderNumber;
    }

    public function setDsFolderNumber(int $dsFolderNumber): static
    {
        $this->dsFolderNumber = $dsFolderNumber;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

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
