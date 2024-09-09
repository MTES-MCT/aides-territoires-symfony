<?php

namespace App\Entity\Log;

use App\Entity\Aid\Aid;
use App\Entity\Organization\Organization;
use App\Entity\User\User;
use App\Repository\Log\LogAidViewTempRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogAidViewTempRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lavt')]
class LogAidViewTemp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Aid $aid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuerystring(): ?string
    {
        return $this->querystring;
    }

    public function setQuerystring(?string $querystring): static
    {
        $this->querystring = $querystring;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

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
