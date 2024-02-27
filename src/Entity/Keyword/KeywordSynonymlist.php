<?php

namespace App\Entity\Keyword;

use App\Entity\Log\LogPublicProjectSearch;
use App\Entity\Project\Project;
use App\Repository\Keyword\KeywordSynonymlistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\Api\Keyword\KeywordSynonymlistController;

#[ApiResource(
    shortName: 'synonymlists',
    operations: [
        new GetCollection(
            uriTemplate: '/synonymlists/',
            controller: KeywordSynonymlistController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
        ),
    ],
)]
#[ORM\Entity(repositoryClass: KeywordSynonymlistRepository::class)]
#[ORM\Index(columns: ['name'], name: 'name_keywordsynonymlist')]
class KeywordSynonymlist
{
    const API_GROUP_LIST = 'keywordsynonymlist:list';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 70)]
    private ?string $name = null;

    #[ORM\Column(length: 70)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, length: 65535)]
    private ?string $keywordsList = null;

    #[ORM\Column(nullable: true)]
    private ?int $oldId = null;

    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'keywordSynonymlists')]
    private Collection $projects;

    #[ORM\ManyToMany(targetEntity: LogPublicProjectSearch::class, mappedBy: 'keywordSynonymlists')]
    private Collection $logPublicProjectSearches;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->logPublicProjectSearches = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getKeywordsList(): ?string
    {
        return $this->keywordsList;
    }

    public function setKeywordsList(?string $keywordsList): static
    {
        $this->keywordsList = $keywordsList;

        return $this;
    }

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): static
    {
        $this->oldId = $oldId;

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
            $project->addKeywordSynonymlist($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            $project->removeKeywordSynonymlist($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogPublicProjectSearch>
     */
    public function getLogPublicProjectSearches(): Collection
    {
        return $this->logPublicProjectSearches;
    }

    public function addLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if (!$this->logPublicProjectSearches->contains($logPublicProjectSearch)) {
            $this->logPublicProjectSearches->add($logPublicProjectSearch);
            $logPublicProjectSearch->addKeywordSynonymlist($this);
        }

        return $this;
    }

    public function removeLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if ($this->logPublicProjectSearches->removeElement($logPublicProjectSearch)) {
            $logPublicProjectSearch->removeKeywordSynonymlist($this);
        }

        return $this;
    }


    public function __toString(): string
    {
        return $this->name ?? 'KeywordSynoymList';
    }
}
