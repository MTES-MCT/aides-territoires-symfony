<?php

namespace App\Entity\Alert;

use App\Repository\Alert\AlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Index(columns: ['email'], name: 'email_alert')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_alert')]
#[ORM\Index(columns: ['date_latest_alert'], name: 'date_latest_alert_alert')]
#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert // NOSONAR too much methods
{
    const FREQUENCIES = [
        ['slug' => 'daily', 'name' => 'Quotidiennement'],
        ['slug' => 'weekly', 'name' => 'Hebdomadairement'],
    ];
    const FREQUENCY_DAILY_SLUG = 'daily';
    const FREQUENCY_WEEKLY_SLUG = 'weekly';

    const SOURCE_AIDES_TERRITOIRES = 'aides-territoires';

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\Length(max: 32)]
    #[ORM\Column(length: 32)]
    private ?string $alertFrequency = null;

    #[ORM\Column]
    private ?bool $validated = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeLatestAlert = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLatestAlert = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeValidated = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getQuerystring(): ?string
    {
        return $this->querystring;
    }

    public function setQuerystring(?string $querystring): static
    {
        $this->querystring = $querystring;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAlertFrequency(): ?string
    {
        return $this->alertFrequency;
    }

    public function setAlertFrequency(string $alertFrequency): static
    {
        $this->alertFrequency = $alertFrequency;

        return $this;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): static
    {
        $this->validated = $validated;

        return $this;
    }

    public function getTimeLatestAlert(): ?\DateTimeInterface
    {
        return $this->timeLatestAlert;
    }

    public function setTimeLatestAlert(?\DateTimeInterface $timeLatestAlert): static
    {
        $this->timeLatestAlert = $timeLatestAlert;

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

    public function getTimeValidated(): ?\DateTimeInterface
    {
        return $this->timeValidated;
    }

    public function setTimeValidated(?\DateTimeInterface $timeValidated): static
    {
        $this->timeValidated = $timeValidated;

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

    public function getDateLatestAlert(): ?\DateTimeInterface
    {
        return $this->dateLatestAlert;
    }

    public function setDateLatestAlert(?\DateTimeInterface $dateLatestAlert): static
    {
        $this->dateLatestAlert = $dateLatestAlert;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(?\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }
}
