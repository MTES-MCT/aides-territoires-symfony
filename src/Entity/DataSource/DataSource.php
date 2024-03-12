<?php

namespace App\Entity\DataSource;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Repository\DataSource\DataSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: DataSourceRepository::class)]
class DataSource
{
    const SLUG_LICENCE_UNKNOWN = 'unknown';
    const SLUG_LICENCE_OPENLICENCE20 = 'openlicence20';
    const LICENCES = [
        ['slug' => self::SLUG_LICENCE_UNKNOWN, 'name' => 'Inconnue'],
        ['slug' => self::SLUG_LICENCE_OPENLICENCE20, 'name' => 'Licence ouverte 2.0']
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $importDetails = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importApiUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importDataUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $importLicence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contactBacker = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeLastAccess = null;

    #[ORM\ManyToOne(inversedBy: 'dataSources')]
    private ?Backer $backer = null;

    #[ORM\ManyToOne(inversedBy: 'dataSourceContactTeams')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $contactTeam = null;

    #[ORM\ManyToOne(inversedBy: 'dataSources')]
    private ?Perimeter $perimeter = null;

    #[ORM\ManyToOne(inversedBy: 'dataSourceAidAuthors')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $aidAuthor = null;

    #[ORM\OneToMany(mappedBy: 'importDataSource', targetEntity: Aid::class)]
    private Collection $aids;

    private int $nbAids = 0;

    #[ORM\Column]
    private ?bool $active = true;

    public function __construct()
    {
        $this->aids = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImportDetails(): ?string
    {
        return $this->importDetails;
    }

    public function setImportDetails(?string $importDetails): static
    {
        $this->importDetails = $importDetails;

        return $this;
    }

    public function getImportApiUrl(): ?string
    {
        return $this->importApiUrl;
    }

    public function setImportApiUrl(?string $importApiUrl): static
    {
        $this->importApiUrl = $importApiUrl;

        return $this;
    }

    public function getImportDataUrl(): ?string
    {
        return $this->importDataUrl;
    }

    public function setImportDataUrl(?string $importDataUrl): static
    {
        $this->importDataUrl = $importDataUrl;

        return $this;
    }

    public function getImportLicence(): ?string
    {
        return $this->importLicence;
    }

    public function setImportLicence(?string $importLicence): static
    {
        $this->importLicence = $importLicence;

        return $this;
    }

    public function getContactBacker(): ?string
    {
        return $this->contactBacker;
    }

    public function setContactBacker(?string $contactBacker): static
    {
        $this->contactBacker = $contactBacker;

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

    public function getTimeLastAccess(): ?\DateTimeInterface
    {
        return $this->timeLastAccess;
    }

    public function setTimeLastAccess(?\DateTimeInterface $timeLastAccess): static
    {
        $this->timeLastAccess = $timeLastAccess;

        return $this;
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

    public function getContactTeam(): ?User
    {
        return $this->contactTeam;
    }

    public function setContactTeam(?User $contactTeam): static
    {
        $this->contactTeam = $contactTeam;

        return $this;
    }

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

        return $this;
    }

    public function getAidAuthor(): ?User
    {
        return $this->aidAuthor;
    }

    public function setAidAuthor(?User $aidAuthor): static
    {
        $this->aidAuthor = $aidAuthor;

        return $this;
    }

    /**
     * @return Collection<int, Aid>
     */
    public function getAids(): Collection
    {
        return $this->aids;
    }

    public function addAid(Aid $aid): static
    {
        if (!$this->aids->contains($aid)) {
            $this->aids->add($aid);
            $aid->setImportDataSource($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            // set the owning side to null (unless already changed)
            if ($aid->getImportDataSource() === $this) {
                $aid->setImportDataSource(null);
            }
        }

        return $this;
    }

    public function getNbAids(): int
    {
        try {
            return count($this->getAids());
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function setNbAids(int $nb): static
    {
        $this->nbAids = $nb;
        return $this;
    }




    public function  __toString(): string
    {
        return $this->name ?? 'DataSource';
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
