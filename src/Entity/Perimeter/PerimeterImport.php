<?php

namespace App\Entity\Perimeter;

use App\Entity\User\User;
use App\Repository\Perimeter\PerimeterImportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Index(columns: ['ask_processing'], name: 'ask_processing_perimeter_import')]
#[ORM\Entity(repositoryClass: PerimeterImportRepository::class)]
class PerimeterImport // NOSONAR too much methods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string[]
     */
    #[ORM\Column]
    private array $cityCodes = [];

    #[ORM\Column]
    private ?bool $isImported = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeImported = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'perimeterImports', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Perimeter $adhocPerimeter = null;

    #[ORM\ManyToOne(inversedBy: 'perimeterImports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    private ?int $nbCities;
    private File $file;
    private ?string $adhocPerimeterName = null;

    #[ORM\Column]
    private ?bool $importProcessing = false;

    #[ORM\Column]
    private ?bool $askProcessing = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getCityCodes(): array
    {
        return $this->cityCodes;
    }

    /**
     * @param string[] $cityCodes
     */
    public function setCityCodes(array $cityCodes): static
    {
        $this->cityCodes = $cityCodes;

        return $this;
    }

    public function addCityCode(string $cityCode): static
    {
        $this->cityCodes[] = $cityCode;

        return $this;
    }

    public function isIsImported(): ?bool
    {
        return $this->isImported;
    }

    public function setIsImported(bool $isImported): static
    {
        $this->isImported = $isImported;

        return $this;
    }

    public function getTimeImported(): ?\DateTimeInterface
    {
        return $this->timeImported;
    }

    public function setTimeImported(?\DateTimeInterface $timeImported): static
    {
        $this->timeImported = $timeImported;

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

    public function getAdhocPerimeter(): ?Perimeter
    {
        return $this->adhocPerimeter;
    }

    public function setAdhocPerimeter(?Perimeter $adhocPerimeter): static
    {
        $this->adhocPerimeter = $adhocPerimeter;

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

    public function getNbCities(): ?int
    {
        try {
            $this->nbCities = count($this->cityCodes);
        } catch (\Exception $e) {
            $this->nbCities = null;
        }

        return $this->nbCities;
    }

    public function setNbCities(?int $nbCities): static
    {
        $this->nbCities = $nbCities;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getAdhocPerimeterName(): ?string
    {
        return $this->adhocPerimeterName;
    }

    public function setAdhocPerimeterName(?string $adhocPerimeterName): static
    {
        $this->adhocPerimeterName = $adhocPerimeterName;

        return $this;
    }

    public function isImportProcessing(): ?bool
    {
        return $this->importProcessing;
    }

    public function setImportProcessing(bool $importProcessing): static
    {
        $this->importProcessing = $importProcessing;

        return $this;
    }

    public function isAskProcessing(): ?bool
    {
        return $this->askProcessing;
    }

    public function setAskProcessing(bool $askProcessing): static
    {
        $this->askProcessing = $askProcessing;

        return $this;
    }
}
