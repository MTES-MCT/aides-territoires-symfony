<?php

namespace App\Entity\Backer;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\Api\Backer\BackerController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\DataSource\DataSource;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogBackerEdit;
use App\Entity\Log\LogBackerView;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\ProjectValidated;
use App\Repository\Backer\BackerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\OpenApi\Model;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Entity\Program\Program;
use App\Filter\Backer\BackerGroupIdFilter;
use App\Filter\AtSearchFilter;
use App\Filter\Backer\HasFinancedAidsFilter;
use App\Filter\Backer\HasPublishedFinancedAidsFilter;

#[ORM\Index(columns: ['is_spotlighted'], name: 'is_spotlighted_backer')]
#[ORM\Index(columns: ['active'], name: 'active_backer')]
#[ORM\Index(columns: ['slug'], name: 'slug_backer')]
#[ORM\Index(columns: ['is_corporate'], name: 'is_corporate_backer')]
#[ApiResource(
    // shortName: 'Porteurs',
    operations: [
        new GetCollection(
            uriTemplate: '/backers/',
            controller: BackerController::class,
            normalizationContext: ['groups' => Backer::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
            ),
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true
        ),
    ]
)]
#[ApiFilter(
    AtSearchFilter::class,
    properties: ['name' => 'partial'],
    arguments: [
        'swaggerDescription' => [
            'name' => 'q',
            'description' => '<p>Rechercher par nom.</p><p>Note : il est possible d\'avoir des résultats '
                . 'pertinents avec seulement le début du nom.</p>',
            'openapi' => [
                'examples' => [
                    ['value' => 'ademe', 'summary' => 'ademe'],
                    ['value' => 'conseil régional', 'summary' => 'conseil régional'],
                    ['value' => 'agenc', 'summary' => 'agenc'],
                ],
                'example' => 'commune'
            ]
        ]
    ]
)]
#[ApiFilter(HasFinancedAidsFilter::class)]
#[ApiFilter(HasPublishedFinancedAidsFilter::class)]
#[ApiFilter(BackerGroupIdFilter::class)]
#[ORM\Entity(repositoryClass: BackerRepository::class)]
class Backer // NOSONAR too much methods
{
    public const API_DESCRIPTION = 'Lister tous les porteurs d\'aides';
    public const API_GROUP_LIST = 'backer:list';
    public const FOLDER = 'backers';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $isCorporate = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalLink = null;

    #[ORM\Column]
    private ?bool $isSpotlighted = false;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    private ?string $logoFile = null;

    private bool $deleteLogo = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[Groups([self::API_GROUP_LIST])]
    #[ORM\ManyToOne(inversedBy: 'backers')]
    private ?Perimeter $perimeter = null;

    /**
     * @var Collection<int, Organization>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: Organization::class, cascade: ['persist'])]
    private Collection $organizations;

    /**
     * @var Collection<int, DataSource>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: DataSource::class)]
    private Collection $dataSources;

    /**
     * @var Collection<int, AidFinancer>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: AidFinancer::class, orphanRemoval: true)]
    private Collection $aidFinancers;

    /**
     * @var Collection<int, AidInstructor>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: AidInstructor::class, orphanRemoval: true)]
    private Collection $aidInstructors;

    /**
     * @var Collection<int, ProjectValidated>
     */
    #[ORM\OneToMany(mappedBy: 'financer', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    /**
     * @var Collection<int, BlogPromotionPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'backers')]
    private Collection $blogPromotionPosts;

    #[ORM\ManyToOne(inversedBy: 'backers')]
    private ?BackerGroup $backerGroup = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    /**
     * @var Collection<int, LogAidSearch>
     */
    #[ORM\ManyToMany(targetEntity: LogAidSearch::class, mappedBy: 'backers')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logAidSearches;

    /**
     * @var Collection<int, LogBackerView>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: LogBackerView::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logBackerViews;

    #[ORM\Column]
    private ?bool $active = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $backerType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $projectsExamples = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalOperation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $usefulLinks = null;

    /**
     * Champs non en base
     */

     /** @var ArrayCollection<int, Category> */
    private ArrayCollection $categories;

    /** @var ArrayCollection<int, Program> */
    private ArrayCollection $programs;
    /** @var ArrayCollection<int, CategoryTheme> */
    private ?ArrayCollection $aidsThematics;
    /** @var array<string, int> */
    private array $nbAidsByTypeGroupSlug = [];
    /** @var array<string, int> */
    private array $nbAidsByTypeSlug = [];

    private ?LogBackerEdit $lastLogBackerEdit = null;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
        $this->dataSources = new ArrayCollection();
        $this->aidFinancers = new ArrayCollection();
        $this->aidInstructors = new ArrayCollection();
        $this->projectValidateds = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->logAidSearches = new ArrayCollection();
        $this->logBackerViews = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->programs = new ArrayCollection();
        $this->backerLocks = new ArrayCollection();
        $this->backerAskAssociates = new ArrayCollection();
        $this->logBackerEdits = new ArrayCollection();
        $this->aidsThematics = new ArrayCollection();
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

    public function isIsCorporate(): ?bool
    {
        return $this->isCorporate;
    }

    public function setIsCorporate(bool $isCorporate): static
    {
        $this->isCorporate = $isCorporate;

        return $this;
    }

    public function getExternalLink(): ?string
    {
        return $this->externalLink;
    }

    public function setExternalLink(?string $externalLink): static
    {
        $this->externalLink = $externalLink;

        return $this;
    }

    public function isIsSpotlighted(): ?bool
    {
        return $this->isSpotlighted;
    }

    public function setIsSpotlighted(bool $isSpotlighted): static
    {
        $this->isSpotlighted = $isSpotlighted;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function setLogoFile(?string $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    public function getLogoFile(): ?string
    {
        return $this->logoFile;
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

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

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

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization): static
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
            $organization->setBacker($this);
        }

        return $this;
    }

    public function removeOrganization(Organization $organization): static
    {
        if ($this->organizations->removeElement($organization) && $organization->getBacker() === $this) {
            $organization->setBacker(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DataSource>
     */
    public function getDataSources(): Collection
    {
        return $this->dataSources;
    }

    public function addDataSource(DataSource $dataSource): static
    {
        if (!$this->dataSources->contains($dataSource)) {
            $this->dataSources->add($dataSource);
            $dataSource->setBacker($this);
        }

        return $this;
    }

    public function removeDataSource(DataSource $dataSource): static
    {
        if ($this->dataSources->removeElement($dataSource) && $dataSource->getBacker() === $this) {
            $dataSource->setBacker(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, AidFinancer>
     */
    public function getAidFinancers(): Collection
    {
        return $this->aidFinancers;
    }

    public function addAidFinancer(AidFinancer $aidFinancer): static
    {
        if (!$this->aidFinancers->contains($aidFinancer)) {
            $this->aidFinancers->add($aidFinancer);
            $aidFinancer->setBacker($this);
        }

        return $this;
    }

    public function removeAidFinancer(AidFinancer $aidFinancer): static
    {
        if ($this->aidFinancers->removeElement($aidFinancer) && $aidFinancer->getBacker() === $this) {
            $aidFinancer->setBacker(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, AidInstructor>
     */
    public function getAidInstructors(): Collection
    {
        return $this->aidInstructors;
    }

    public function addAidInstructor(AidInstructor $aidInstructor): static
    {
        if (!$this->aidInstructors->contains($aidInstructor)) {
            $this->aidInstructors->add($aidInstructor);
            $aidInstructor->setBacker($this);
        }

        return $this;
    }

    public function removeAidInstructor(AidInstructor $aidInstructor): static
    {
        if ($this->aidInstructors->removeElement($aidInstructor) && $aidInstructor->getBacker() === $this) {
            $aidInstructor->setBacker(null);
        }

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
            $projectValidated->setFinancer($this);
        }

        return $this;
    }

    public function removeProjectValidated(ProjectValidated $projectValidated): static
    {
        if ($this->projectValidateds->removeElement($projectValidated) && $projectValidated->getFinancer() === $this) {
            $projectValidated->setFinancer(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, BlogPromotionPost>
     */
    public function getBlogPromotionPosts(): Collection
    {
        return $this->blogPromotionPosts;
    }

    public function addBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if (!$this->blogPromotionPosts->contains($blogPromotionPost)) {
            $this->blogPromotionPosts->add($blogPromotionPost);
            $blogPromotionPost->addBacker($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            $blogPromotionPost->removeBacker($this);
        }

        return $this;
    }

    public function getBackerGroup(): ?BackerGroup
    {
        return $this->backerGroup;
    }

    public function setBackerGroup(?BackerGroup $backerGroup): static
    {
        $this->backerGroup = $backerGroup;

        return $this;
    }


    /************************
     * SPECIFIC
     */

    /**
     * @var array<int, Aid>|null
     */
    private ?array $aidsLive = [];

    /**
     * @return array<int, Aid>|null
     */
    public function getAidsLive(): ?array
    {
        return $this->aidsLive;
    }

    /**
     * @param array<int, Aid>|null $aids
     * @return static
     */
    public function setAidsLive(?array $aids): static
    {
        $this->aidsLive = $aids;
        return $this;
    }

    /**
     * @var array<int, Aid>|null
     */
    private ?array $aidsFinancial = [];

    /**
     * @return array<int, Aid>|null
     */
    public function getAidsFinancial(): ?array
    {
        if (!empty($this->aidsFinancial)) {
            return $this->aidsFinancial;
        }

        /** @var ArrayCollection<int, Aid> */
        $aidsFinancial = new ArrayCollection();
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getAidTypes() as $aidType) {
                if (
                    $aidType->getAidTypeGroup()->getSlug() == AidTypeGroup::SLUG_FINANCIAL
                    && !$aidsFinancial->contains($aid)
                ) {
                    $aidsFinancial->add($aid);
                }
            }
        }

        $this->setAidsFinancial($aidsFinancial->toArray());
        return $this->aidsFinancial;
    }

    /**
     * @param array<int, Aid>|null $aids
     * @return static
     */
    public function setAidsFinancial(?array $aids): static
    {
        $this->aidsFinancial = $aids;
        return $this;
    }

    /** @var array<int, Aid>|null */
    private ?array $aidsTechnical = [];

    #[ORM\Column(nullable: true)]
    private ?int $nbAids = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbAidsLive = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbAidsLiveFinancial = null;
    #[ORM\Column(nullable: true)]
    private ?int $nbAidsLiveTechnical = null;

    /**
     * @var Collection<int, BackerLock>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: BackerLock::class, orphanRemoval: true)]
    private Collection $backerLocks;

    /**
     * @var Collection<int, BackerAskAssociate>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: BackerAskAssociate::class, orphanRemoval: true)]
    private Collection $backerAskAssociates;

    /**
     * @var Collection<int, LogBackerEdit>
     */
    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: LogBackerEdit::class, orphanRemoval: true)]
    #[ORM\OrderBy(['timecreate' => 'DESC'])]
    private Collection $logBackerEdits;

    /**
     * @return array<int, Aid>|null
     */
    public function getAidsTechnical(): ?array
    {
        if (!empty($this->aidsTechnical)) {
            return $this->aidsTechnical;
        }
        /** @var ArrayCollection<int, Aid> $aidsTechnical */
        $aidsTechnical = new ArrayCollection();
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getAidTypes() as $aidType) {
                if (
                    $aidType->getAidTypeGroup()->getSlug() == AidTypeGroup::SLUG_TECHNICAL
                    && !$aidsTechnical->contains($aid)
                ) {
                    $aidsTechnical->add($aid);
                }
            }
        }

        $this->setAidsTechnical($aidsTechnical->toArray());
        return $this->aidsTechnical;
    }

    /**
     * @param array<int, Aid>|null $aids
     * @return static
     */
    public function setAidsTechnical(?array $aids): static
    {
        $this->aidsTechnical = $aids;
        return $this;
    }

    /**
     * @param string|null $aidTypeSlug
     * @return array<int, Aid>
     */
    public function getAidsByAidTypeSlug(?string $aidTypeSlug = null): array
    {
        if (!$aidTypeSlug) {
            return [];
        }

        $aids = [];
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getAidTypes() as $aidType) {
                if ($aidType->getSlug() == $aidTypeSlug) {
                    $aids[] = $aid;
                }
            }
        }

        return $aids;
    }

    /**
     * @return ArrayCollection<int, CategoryTheme>|null
     */
    public function getAidsThematics(): ?ArrayCollection
    {
        // vérifie si on a déjà calculé les thématiques
        if ($this->aidsThematics) {
            return $this->aidsThematics;
        }
        /** @var ArrayCollection<int, CategoryTheme> $thematics */
        $thematics = new ArrayCollection();
        /** @var Aid $aid */
        foreach ($this->getAidsLive() as $aid) {
            /**@var Category $category */
            foreach ($aid->getCategories() as $category) {
                /** @var CategoryTheme $categoryTheme */
                $categoryTheme = $category->getCategoryTheme();
                if (!$thematics->contains($categoryTheme)) {
                    $thematics->add($categoryTheme);
                }
            }
        }

        /** @var mixed $iterator */
        $iterator = $thematics->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getSlug() < $b->getSlug()) ? -1 : 1;
        });
        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * @param ArrayCollection<int, CategoryTheme>|null $aidsThematics
     * @return static
     */
    public function setAidsThematics(?ArrayCollection $aidsThematics): static
    {
        $this->aidsThematics = $aidsThematics;
        return $this;
    }

    /**
     * @return Collection<int, LogAidSearch>
     */
    public function getLogAidSearches(): Collection
    {
        return $this->logAidSearches;
    }

    public function addLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if (!$this->logAidSearches->contains($logAidSearch)) {
            $this->logAidSearches->add($logAidSearch);
            $logAidSearch->addBacker($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            $logAidSearch->removeBacker($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBackerView>
     */
    public function getLogBackerViews(): Collection
    {
        return $this->logBackerViews;
    }

    public function addLogBackerView(LogBackerView $logBackerView): static
    {
        if (!$this->logBackerViews->contains($logBackerView)) {
            $this->logBackerViews->add($logBackerView);
            $logBackerView->setBacker($this);
        }

        return $this;
    }

    public function removeLogBackerView(LogBackerView $logBackerView): static
    {
        if ($this->logBackerViews->removeElement($logBackerView) && $logBackerView->getBacker() === $this) {
            $logBackerView->setBacker(null);
        }

        return $this;
    }

    /**
     * @return ArrayCollection<int, Category>
     */
    public function getCategories(): ArrayCollection
    {
        $categories = new ArrayCollection();

        $this->addAidFinancerCategories($categories);
        $this->addAidInstructorCategories($categories);

        $this->categories = $categories;
        return $this->categories;
    }

    /**
     * @param ArrayCollection<int, Category> $categories
     * @return void
     */
    private function addAidFinancerCategories(ArrayCollection $categories): void
    {
        foreach ($this->getAidFinancers() as $aidFinancer) {
            if ($aidFinancer->getAid()) {
                $aidCategories = new ArrayCollection($aidFinancer->getAid()->getCategories()->toArray());
                $this->addCategories($aidCategories, $categories);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Category> $categories
     * @return void
     */
    private function addAidInstructorCategories(ArrayCollection $categories): void
    {
        foreach ($this->getAidInstructors() as $aidInstructor) {
            if ($aidInstructor->getAid()) {
                $aidCategories = new ArrayCollection($aidInstructor->getAid()->getCategories()->toArray());
                $this->addCategories($aidCategories, $categories);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Category> $newCategories
     * @param ArrayCollection<int, Category> $categories
     * @return void
     */
    private function addCategories(ArrayCollection $newCategories, ArrayCollection $categories): void
    {
        foreach ($newCategories as $category) {
            if (!$categories->contains($category)) {
                $categories->add($category);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Category> $categories
     * @return void
     */
    public function setCategories(ArrayCollection $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return ArrayCollection<int, Program>
     */
    public function getPrograms(): ArrayCollection
    {
        $programs = new ArrayCollection();

        $this->addAidFinancerPrograms($programs);
        $this->addAidInstructorPrograms($programs);

        $this->programs = $programs;
        return $this->programs;
    }

    /**
     * @param ArrayCollection<int, Program> $programs
     * @return void
     */
    private function addAidFinancerPrograms(ArrayCollection $programs): void
    {
        foreach ($this->getAidFinancers() as $aidFinancer) {
            if ($aidFinancer->getAid()) {
                $aidFinancerPrograms = new ArrayCollection($aidFinancer->getAid()->getPrograms()->toArray());
                $this->addPrograms($aidFinancerPrograms, $programs);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Program> $programs
     * @return void
     */
    private function addAidInstructorPrograms(ArrayCollection $programs): void
    {
        foreach ($this->getAidInstructors() as $aidInstructor) {
            if ($aidInstructor->getAid()) {
                $aidInstructorPrograms = new ArrayCollection($aidInstructor->getAid()->getPrograms()->toArray());
                $this->addPrograms($aidInstructorPrograms, $programs);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Program> $newPrograms
     * @param ArrayCollection<int, Program> $programs
     * @return void
     */
    private function addPrograms(ArrayCollection $newPrograms, ArrayCollection $programs): void
    {
        foreach ($newPrograms as $program) {
            if (!$programs->contains($program)) {
                $programs->add($program);
            }
        }
    }

    /**
     * @param ArrayCollection<int, Program> $programs
     * @return void
     */
    public function setPrograms(ArrayCollection $programs): void
    {
        $this->programs = $programs;
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

    public function getDeleteLogo(): ?bool
    {
        return $this->deleteLogo;
    }

    public function setDeleteLogo(?bool $deleteLogo): static
    {
        $this->deleteLogo = $deleteLogo;

        return $this;
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

    public function getNbAids(): ?int
    {
        return $this->nbAids;
    }

    public function setNbAids(?int $nbAids): static
    {
        $this->nbAids = $nbAids;

        return $this;
    }

    public function getBackerType(): ?string
    {
        return $this->backerType;
    }

    public function setBackerType(?string $backerType): static
    {
        $this->backerType = $backerType;

        return $this;
    }

    public function getProjectsExamples(): ?string
    {
        return $this->projectsExamples;
    }

    public function setProjectsExamples(?string $projectsExamples): static
    {
        $this->projectsExamples = $projectsExamples;

        return $this;
    }

    public function getInternalOperation(): ?string
    {
        return $this->internalOperation;
    }

    public function setInternalOperation(?string $internalOperation): static
    {
        $this->internalOperation = $internalOperation;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getUsefulLinks(): ?string
    {
        return $this->usefulLinks;
    }

    public function setUsefulLinks(?string $usefulLinks): static
    {
        $this->usefulLinks = $usefulLinks;

        return $this;
    }

    public function getNbAidsLive(): ?int
    {
        return $this->nbAidsLive;
    }

    public function setNbAidsLive(?int $nbAidsLive): static
    {
        $this->nbAidsLive = $nbAidsLive;

        return $this;
    }

    public function getNbAidsLiveFinancial(): ?int
    {
        return $this->nbAidsLiveFinancial;
    }

    public function setNbAidsLiveFinancial(?int $nbAidsLiveFinancial): static
    {
        $this->nbAidsLiveFinancial = $nbAidsLiveFinancial;

        return $this;
    }

    public function getNbAidsLiveTechnical(): ?int
    {
        return $this->nbAidsLiveTechnical;
    }

    public function setNbAidsLiveTechnical(?int $nbAidsLiveTechnical): static
    {
        $this->nbAidsLiveTechnical = $nbAidsLiveTechnical;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName() ?? 'Backer';
    }

    /**
     * @return Collection<int, BackerLock>
     */
    public function getBackerLocks(): Collection
    {
        return $this->backerLocks;
    }

    public function addBackerLock(BackerLock $backerLock): static
    {
        if (!$this->backerLocks->contains($backerLock)) {
            $this->backerLocks->add($backerLock);
            $backerLock->setBacker($this);
        }

        return $this;
    }

    public function removeBackerLock(BackerLock $backerLock): static
    {
        if ($this->backerLocks->removeElement($backerLock)) {
            // set the owning side to null (unless already changed)
            if ($backerLock->getBacker() === $this) {
                $backerLock->setBacker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BackerAskAssociate>
     */
    public function getBackerAskAssociates(): Collection
    {
        return $this->backerAskAssociates;
    }

    public function addBackerAskAssociate(BackerAskAssociate $backerAskAssociate): static
    {
        if (!$this->backerAskAssociates->contains($backerAskAssociate)) {
            $this->backerAskAssociates->add($backerAskAssociate);
            $backerAskAssociate->setBacker($this);
        }

        return $this;
    }

    public function removeBackerAskAssociate(BackerAskAssociate $backerAskAssociate): static
    {
        if ($this->backerAskAssociates->removeElement($backerAskAssociate)) {
            // set the owning side to null (unless already changed)
            if ($backerAskAssociate->getBacker() === $this) {
                $backerAskAssociate->setBacker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBackerEdit>
     */
    public function getLogBackerEdits(): Collection
    {
        return $this->logBackerEdits;
    }

    public function addLogBackerEdit(LogBackerEdit $logBackerEdit): static
    {
        if (!$this->logBackerEdits->contains($logBackerEdit)) {
            $this->logBackerEdits->add($logBackerEdit);
            $logBackerEdit->setBacker($this);
        }

        return $this;
    }

    public function removeLogBackerEdit(LogBackerEdit $logBackerEdit): static
    {
        if ($this->logBackerEdits->removeElement($logBackerEdit)) {
            // set the owning side to null (unless already changed)
            if ($logBackerEdit->getBacker() === $this) {
                $logBackerEdit->setBacker(null);
            }
        }

        return $this;
    }

    public function getLastLogBackerEdit(): ?LogBackerEdit
    {
        $this->lastLogBackerEdit = $this->logBackerEdits->first() ? $this->logBackerEdits->first() : null;
        return $this->lastLogBackerEdit;
    }

    public function setLastLogBackerEdit(?LogBackerEdit $lastLogBackerEdit): static
    {
        $this->lastLogBackerEdit = $lastLogBackerEdit;
        return $this;
    }

    /**
     * @return array<string, int>
     */
    public function getNbAidsByTypeGroupSlug(): array
    {
        return $this->nbAidsByTypeGroupSlug;
    }

    /**
     * @param array<string, int> $nbAidsByTypeGroupSlug
     * @return static
     */
    public function setNbAidsByTypeGroupSlug(array $nbAidsByTypeGroupSlug): static
    {
        $this->nbAidsByTypeGroupSlug = $nbAidsByTypeGroupSlug;
        return $this;
    }

    /**
     * @return array<string, int>
     */
    public function getNbAidsByTypeSlug(): array
    {
        return $this->nbAidsByTypeSlug;
    }

    /**
     * @param array<string, int> $nbAidsByTypeSlug
     * @return static
     */
    public function setNbAidsByTypeSlug(array $nbAidsByTypeSlug): static
    {
        $this->nbAidsByTypeSlug = $nbAidsByTypeSlug;
        return $this;
    }
}
