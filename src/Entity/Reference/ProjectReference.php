<?php

namespace App\Entity\Reference;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Reference\ProjectReferenceController;
use App\Entity\Aid\Aid;
use App\Entity\Project\Project;
use App\Filter\Reference\ProjectReferenceCategoryFilter;
use App\Filter\Reference\ProjectReferenceTextFilter;
use App\Repository\Reference\ProjectReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Projets référents',
    operations: [
        new GetCollection(
            uriTemplate: '/project-references/',
            controller: ProjectReferenceController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
            ),
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationClientItemsPerPage: true
        )
    ]
)]
#[ApiFilter(ProjectReferenceTextFilter::class)]
#[ApiFilter(ProjectReferenceCategoryFilter::class)]
#[ORM\Index(columns: ['name'], name: 'name_pr_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['slug'], name: 'slug_pr')]
#[ORM\Entity(repositoryClass: ProjectReferenceRepository::class)]
class ProjectReference
{
    const API_GROUP_LIST = 'project_reference:list';
    const API_GROUP_ITEM = 'project_reference:item';
    const API_DESCRIPTION = 'Lister tous les projets référents';

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM, Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM, Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM, Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToOne(inversedBy: 'projectReferences')]
    private ?ProjectReferenceCategory $projectReferenceCategory = null;

    #[ORM\OneToMany(mappedBy: 'projectReference', targetEntity: Project::class)]
    private Collection $projects;

    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'projectReferences')]
    private Collection $aids;

    private Collection $aidsLive;

    #[ORM\ManyToMany(targetEntity: KeywordReference::class, inversedBy: 'excludedProjectReferences')]
    #[ORM\JoinTable(name: 'project_reference_excluded_keyword_reference')]
    private Collection $excludedKeywordReferences;

    #[ORM\Column(nullable: true)]
    private ?int $nbSearchResult = null;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->aidsLive = new ArrayCollection();
        $this->excludedKeywordReferences = new ArrayCollection();
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

    public function getProjectReferenceCategory(): ?ProjectReferenceCategory
    {
        return $this->projectReferenceCategory;
    }

    public function setProjectReferenceCategory(?ProjectReferenceCategory $projectReferenceCategory): static
    {
        $this->projectReferenceCategory = $projectReferenceCategory;

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setProjectReference($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project) && $project->getProjectReference() === $this) {
            $project->setProjectReference(null);
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function  __toString(): string
    {
        return $this->getName() ?? '';
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
            $aid->addProjectReference($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeProjectReference($this);
        }

        return $this;
    }

    public function getAidsLive(): Collection
    {
        $this->aidsLive = $this->aids->filter(fn(Aid $aid) => $aid->isLive());
        return $this->aidsLive;
    }

    /**
     * @return Collection<int, KeywordReference>
     */
    public function getExcludedKeywordReferences(): Collection
    {
        return $this->excludedKeywordReferences;
    }

    public function addExcludedKeywordReference(KeywordReference $excludedKeywordReference): static
    {
        if (!$this->excludedKeywordReferences->contains($excludedKeywordReference)) {
            $this->excludedKeywordReferences->add($excludedKeywordReference);
        }

        return $this;
    }

    public function removeExcludedKeywordReference(KeywordReference $excludedKeywordReference): static
    {
        $this->excludedKeywordReferences->removeElement($excludedKeywordReference);

        return $this;
    }

    public function getNbSearchResult(): ?int
    {
        return $this->nbSearchResult;
    }

    public function setNbSearchResult(?int $nbSearchResult): static
    {
        $this->nbSearchResult = $nbSearchResult;

        return $this;
    }
}
