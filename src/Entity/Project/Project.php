<?php

namespace App\Entity\Project;

use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Log\LogPublicProjectView;
use App\Entity\Organization\Organization;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Repository\Project\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Index(columns: ['status'], name: 'status_project')]
#[ORM\Index(columns: ['is_public'], name: 'is_public_project')]
#[ORM\Index(columns: ['name'], name: 'name_project_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['description'], name: 'description_project_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['name', 'description'], name: 'name_description_project_fulltext', flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    const FOLDER = 'projects';
    
    const STATUS = [
        ['slug' => 'draft', 'name' => 'Brouillon'],
        ['slug' => 'reviewable', 'name' => 'En revue'],
        ['slug' => 'published', 'name' => 'Publié'],
        ['slug' => 'deleted', 'name' => 'Supprimé']
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEWABLE = 'reviewable';
    const STATUS_PUBLISHED = 'published';
    const STATUS_DELETED = 'deleted';

    const CONTRACT_LINK = [
        ['slug' => 'ACV1', 'name' => 'Action Coeur de Ville 1'],
        ['slug' => 'ACV2', 'name' => 'Action Coeur de Ville 2'],
        ['slug' => 'AMI', 'name' => 'Expérimentations et bonnes pratiques locales des collectivités en faveur de l\'emploi des femmes en zone rurale'],
        ['slug' => 'CRTE', 'name' => 'CRTE'],
        ['slug' => 'PCAET', 'name' => 'PCAET'],
        ['slug' => 'PVD', 'name' => 'Petites Villes de Demain']
    ];

    const CONTRACT_LINK_BY_SLUG = [
        'ACV1' => 'Action Coeur de Ville 1',
        'ACV2'=> 'Action Coeur de Ville 2',
        'AMI' => 'Expérimentations et bonnes pratiques locales des collectivités en faveur de l\'emploi des femmes en zone rurale',
        'CRTE' => 'CRTE',
        'PCAET' => 'PCAET',
        'PVD' => 'Petites Villes de Demain'
    ];

    const PROJECT_STEPS = [
        ['slug' => 'considered', 'name' => 'En réflexion'],
        ['slug' => 'ongoing', 'name' => 'En cours'],
        ['slug' => 'finished', 'name' => 'Réalisé']
    ];
    // donne une facon de recuperer les donnees de la constantes
    const PROJECT_STEPS_BY_SLUG = [
        'considered' => 'En réflexion',
        'ongoing' => 'En cours',
        'finished' => 'Réalisé'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keyWords = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $contractLink = null;

    #[ORM\Column]
    private ?bool $isPublic = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $privateDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectTypesSuggestion = null;

    #[ORM\Column(length: 10)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $budget = 0;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $otherProjectOwner = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $step = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $image = null;

    private $imageFile = null;

    private bool $deleteImage = false;

    private ?UploadedFile $imageUploadedFile;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private ?Organization $organization = null;

    #[ORM\ManyToMany(targetEntity: KeywordSynonymlist::class, inversedBy: 'projects')]
    private Collection $keywordSynonymlists;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: AidProject::class, cascade:['persist'], orphanRemoval: true)]
    private Collection $aidProjects;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: AidSuggestedAidProject::class, orphanRemoval: true)]
    private Collection $aidSuggestedAidProjects;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: LogPublicProjectView::class)]
    private Collection $logPublicProjectViews;

    #[ORM\Column(nullable: true, name: 'referent_not_found')]
    private ?bool $referentNotFound = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?ProjectReference $projectReference = null;

    private ?int $nbAids;
    private ?float $distance = null;

    public function __construct()
    {
        $this->keywordSynonymlists = new ArrayCollection();
        $this->projectValidateds = new ArrayCollection();
        $this->aidProjects = new ArrayCollection();
        $this->aidSuggestedAidProjects = new ArrayCollection();
        $this->logPublicProjectViews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return html_entity_decode(strip_tags($this->name));
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

        return $this;
    }

    public function getKeyWords(): ?string
    {
        return $this->keyWords;
    }

    public function setKeyWords(?string $keyWords): static
    {
        $this->keyWords = $keyWords;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getContractLink(): ?string
    {
        return $this->contractLink;
    }

    public function setContractLink(?string $contractLink): static
    {
        $this->contractLink = $contractLink;

        return $this;
    }

    public function isIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getPrivateDescription(): ?string
    {
        return $this->privateDescription;
    }

    public function setPrivateDescription(?string $privateDescription): static
    {
        $this->privateDescription = $privateDescription;

        return $this;
    }

    public function getProjectTypesSuggestion(): ?string
    {
        return $this->projectTypesSuggestion;
    }

    public function setProjectTypesSuggestion(?string $projectTypesSuggestion): static
    {
        $this->projectTypesSuggestion = $projectTypesSuggestion;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getOtherProjectOwner(): ?string
    {
        return $this->otherProjectOwner;
    }

    public function setOtherProjectOwner(?string $otherProjectOwner): static
    {
        $this->otherProjectOwner = $otherProjectOwner;

        return $this;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setStep(?string $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    #[Ignore]
    public function setImageFile($imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    #[Ignore]
    public function getImageFile()
    {
        return $this->imageFile;
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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return Collection<int, KeywordSynonymlist>
     */
    public function getKeywordSynonymlists(): Collection
    {
        return $this->keywordSynonymlists;
    }

    public function addKeywordSynonymlist(KeywordSynonymlist $keywordSynonymlist): static
    {
        if (!$this->keywordSynonymlists->contains($keywordSynonymlist)) {
            $this->keywordSynonymlists->add($keywordSynonymlist);
        }

        return $this;
    }

    public function removeKeywordSynonymlist(KeywordSynonymlist $keywordSynonymlist): static
    {
        $this->keywordSynonymlists->removeElement($keywordSynonymlist);

        return $this;
    }

    /**
     * @return Collection<int, ProjectValidated>
     */
    public function getProjectValidateds(): Collection
    {
        return $this->projectValidateds;
    }

    public function addProjectValidated(ProjectValidated $projectValidated): static
    {
        if (!$this->projectValidateds->contains($projectValidated)) {
            $this->projectValidateds->add($projectValidated);
            $projectValidated->setProject($this);
        }

        return $this;
    }

    public function removeProjectValidated(ProjectValidated $projectValidated): static
    {
        if ($this->projectValidateds->removeElement($projectValidated)) {
            // set the owning side to null (unless already changed)
            if ($projectValidated->getProject() === $this) {
                $projectValidated->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AidProject>
     */
    public function getAidProjects(): Collection
    {
        return $this->aidProjects;
    }

    public function addAidProject(AidProject $aidProject): static
    {
        if (!$this->aidProjects->contains($aidProject)) {
            $this->aidProjects->add($aidProject);
            $aidProject->setProject($this);
        }

        return $this;
    }

    public function removeAidProject(AidProject $aidProject): static
    {
        if ($this->aidProjects->removeElement($aidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidProject->getProject() === $this) {
                $aidProject->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AidSuggestedAidProject>
     */
    public function getAidSuggestedAidProjects(): Collection
    {
        return $this->aidSuggestedAidProjects;
    }

    public function addAidSuggestedAidProject(AidSuggestedAidProject $aidSuggestedAidProject): static
    {
        if (!$this->aidSuggestedAidProjects->contains($aidSuggestedAidProject)) {
            $this->aidSuggestedAidProjects->add($aidSuggestedAidProject);
            $aidSuggestedAidProject->setProject($this);
        }

        return $this;
    }

    public function removeAidSuggestedAidProject(AidSuggestedAidProject $aidSuggestedAidProject): static
    {
        if ($this->aidSuggestedAidProjects->removeElement($aidSuggestedAidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidSuggestedAidProject->getProject() === $this) {
                $aidSuggestedAidProject->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogPublicProjectView>
     */
    public function getLogPublicProjectViews(): Collection
    {
        return $this->logPublicProjectViews;
    }

    public function addLogPublicProjectView(LogPublicProjectView $logPublicProjectView): static
    {
        if (!$this->logPublicProjectViews->contains($logPublicProjectView)) {
            $this->logPublicProjectViews->add($logPublicProjectView);
            $logPublicProjectView->setProject($this);
        }

        return $this;
    }

    public function removeLogPublicProjectView(LogPublicProjectView $logPublicProjectView): static
    {
        if ($this->logPublicProjectViews->removeElement($logPublicProjectView)) {
            // set the owning side to null (unless already changed)
            if ($logPublicProjectView->getProject() === $this) {
                $logPublicProjectView->setProject(null);
            }
        }

        return $this;
    }

    public function isReferentNotFound(): ?bool
    {
        return $this->referentNotFound;
    }

    public function setReferentNotFound(?bool $referentNotFound): static
    {
        $this->referentNotFound = $referentNotFound;

        return $this;
    }

    public function getImageUploadedFile(): ?UploadedFile
    {
        return $this->imageUploadedFile;
    }

    public function setImageUploadedFile(?UploadedFile $imageUploadedFile): void
    {
        $this->imageUploadedFile = $imageUploadedFile;

        if ($imageUploadedFile !== null) {
            // It's important to update the updatedAt field to trigger the lifecycle events
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    

    public function getNbAids(): ?int
    {
        try {
            return count($this->getAidProjects());
        } catch (\Exception $e) {
            return null;
        }
    }


    public function getProjectReference(): ?ProjectReference
    {
        return $this->projectReference;
    }

    public function setProjectReference(?ProjectReference $projectReference): static
    {
        $this->projectReference = $projectReference;

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

    public function __toString(): string
    {
        return $this->name ?? 'Projet';
    }

    public function getDeleteImage(): ?bool
    {
        return $this->deleteImage;
    }

    public function setDeleteImage(?bool $deleteImage): static
    {
        $this->deleteImage = $deleteImage;

        return $this;
    }
}
