<?php

namespace App\Entity\Cron;

use App\Entity\User\User;
use App\Repository\Cron\CronExportSpreadsheetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CronExportSpreadsheetRepository::class)]
class CronExportSpreadsheet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sqlRequest = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $sqlParams = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $format = null;

    #[ORM\ManyToOne(inversedBy: 'cronExportSpreadsheets')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeEmail = null;

    #[ORM\Column]
    private ?bool $processing = false;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $entityFqcn = null;

    #[ORM\Column]
    private ?bool $error = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSqlRequest(): ?string
    {
        return $this->sqlRequest;
    }

    public function setSqlRequest(string $sqlRequest): static
    {
        $this->sqlRequest = $sqlRequest;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

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

    public function getTimeEmail(): ?\DateTimeInterface
    {
        return $this->timeEmail;
    }

    public function setTimeEmail(?\DateTimeInterface $timeEmail): static
    {
        $this->timeEmail = $timeEmail;

        return $this;
    }

    public function isProcessing(): ?bool
    {
        return $this->processing;
    }

    public function setProcessing(bool $processing): static
    {
        $this->processing = $processing;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getSqlParams(): ?array
    {
        return $this->sqlParams;
    }

    /**
     * @param array<int, array<string, mixed>>|null $sqlParams
     */
    public function setSqlParams(?array $sqlParams): static
    {
        $this->sqlParams = $sqlParams;

        return $this;
    }

    public function getEntityFqcn(): ?string
    {
        return $this->entityFqcn;
    }

    public function setEntityFqcn(string $entityFqcn): static
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    public function isError(): ?bool
    {
        return $this->error;
    }

    public function setError(bool $error): static
    {
        $this->error = $error;

        return $this;
    }
}
