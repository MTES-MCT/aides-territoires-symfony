<?php

namespace App\Entity\Project;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Organization\Organization;
use App\Repository\Project\ProjectValidatedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['project_name'], name: 'project_name_project_validated_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['description'], name: 'description_project_validated_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['project_name', 'description'], name: 'project_name_description_project_validated_fulltext', flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: ProjectValidatedRepository::class)]
class ProjectValidated // NOSONAR too much methods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $projectName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $aidName = null;

    #[ORM\Column(length: 255)]
    private ?string $financerName = null;

    #[ORM\Column(nullable: true)]
    private ?int $budget = null;

    #[ORM\Column(nullable: true)]
    private ?int $amountObtained = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeObtained = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $importUniqueid = null;

    #[ORM\ManyToOne(inversedBy: 'projectValidateds')]
    private ?Aid $aid = null;

    #[ORM\ManyToOne(inversedBy: 'projectValidateds')]
    private ?Backer $financer = null;

    #[ORM\ManyToOne(inversedBy: 'projectValidateds')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'projectValidateds')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private ?Project $project = null;

    private ?float $distance = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;

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

    public function getAidName(): ?string
    {
        return $this->aidName;
    }

    public function setAidName(string $aidName): static
    {
        $this->aidName = $aidName;

        return $this;
    }

    public function getFinancerName(): ?string
    {
        return $this->financerName;
    }

    public function setFinancerName(string $financerName): static
    {
        $this->financerName = $financerName;

        return $this;
    }

    public function getBudget(): ?int
    {
        return $this->budget;
    }

    public function setBudget(?int $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    public function getAmountObtained(): ?int
    {
        return $this->amountObtained;
    }

    public function setAmountObtained(?int $amountObtained): static
    {
        $this->amountObtained = $amountObtained;

        return $this;
    }

    public function getTimeObtained(): ?\DateTimeInterface
    {
        return $this->timeObtained;
    }

    public function setTimeObtained(?\DateTimeInterface $timeObtained): static
    {
        $this->timeObtained = $timeObtained;

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

    public function getImportUniqueid(): ?string
    {
        return $this->importUniqueid;
    }

    public function setImportUniqueid(?string $importUniqueid): static
    {
        $this->importUniqueid = $importUniqueid;

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

    public function getFinancer(): ?Backer
    {
        return $this->financer;
    }

    public function setFinancer(?Backer $financer): static
    {
        $this->financer = $financer;

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }
}
