<?php

namespace App\Entity\Log;

use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\User\User;
use App\Repository\Log\LogAidSearchRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogAidSearchRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_las')]
#[ORM\Index(columns: ['source'], name: 'source_las')]
#[ORM\Index(columns: ['search'], name: 'source_las')]
class LogAidSearch // NOSONAR too much methods
{
    const SOURCE_API = 'api';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[ORM\Column]
    private ?int $resultsCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $search = null;

    #[ORM\ManyToOne(inversedBy: 'logAidSearches')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Perimeter $perimeter = null;

    #[ORM\ManyToOne(inversedBy: 'logAidSearches')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'logAidSearches')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: OrganizationType::class, inversedBy: 'logAidSearches')]
    private Collection $organizationTypes;

    #[ORM\ManyToMany(targetEntity: Backer::class, inversedBy: 'logAidSearches')]
    private Collection $backers;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'logAidSearches')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Program::class, inversedBy: 'logAidSearches')]
    private Collection $programs;

    #[ORM\ManyToMany(targetEntity: CategoryTheme::class, inversedBy: 'logAidSearches')]
    private Collection $themes;

    public function __construct()
    {
        $this->organizationTypes = new ArrayCollection();
        $this->backers = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->programs = new ArrayCollection();
        $this->themes = new ArrayCollection();
    }

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

    public function getResultsCount(): ?int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): static
    {
        $this->resultsCount = $resultsCount;

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

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): static
    {
        $this->search = $search;

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

    /**
     * @return Collection<int, OrganizationType>
     */
    public function getOrganizationTypes(): Collection
    {
        return $this->organizationTypes;
    }

    public function addOrganizationType(OrganizationType $organizationType): static
    {
        if (!$this->organizationTypes->contains($organizationType)) {
            $this->organizationTypes->add($organizationType);
        }

        return $this;
    }

    public function removeOrganizationType(OrganizationType $organizationType): static
    {
        $this->organizationTypes->removeElement($organizationType);

        return $this;
    }

    /**
     * @return Collection<int, Backer>
     */
    public function getBackers(): Collection
    {
        return $this->backers;
    }

    public function addBacker(Backer $backer): static
    {
        if (!$this->backers->contains($backer)) {
            $this->backers->add($backer);
        }

        return $this;
    }

    public function removeBacker(Backer $backer): static
    {
        $this->backers->removeElement($backer);

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Program>
     */
    public function getPrograms(): Collection
    {
        return $this->programs;
    }

    public function addProgram(Program $program): static
    {
        if (!$this->programs->contains($program)) {
            $this->programs->add($program);
        }

        return $this;
    }

    public function removeProgram(Program $program): static
    {
        $this->programs->removeElement($program);

        return $this;
    }

    /**
     * @return Collection<int, CategoryTheme>
     */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(CategoryTheme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
        }

        return $this;
    }

    public function removeTheme(CategoryTheme $theme): static
    {
        $this->themes->removeElement($theme);

        return $this;
    }
}
