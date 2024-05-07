<?php

namespace App\Entity\DataExport;

use App\Entity\User\User;
use App\Repository\DataExport\DataExportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DataExportRepository::class)]
class DataExport
{
    const FOLDER = 'data-export';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $exportedFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\ManyToOne(inversedBy: 'dataExports')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    private ?string $urlExportedFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExportedFile(): ?string
    {
        return $this->exportedFile;
    }

    public function setExportedFile(string $exportedFile): static
    {
        $this->exportedFile = $exportedFile;

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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }
    
    public function getUrlExportedFile() : ?string
    {
        return $this->urlExportedFile;
    }

    public function setUrlExportedFile(string $urlExportedFile) : static
    {
        $this->urlExportedFile = $urlExportedFile;
        return $this;
    }
}
