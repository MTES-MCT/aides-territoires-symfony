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
use App\Filter\AtSearchFilter;
use App\Filter\Backer\HasFinancedAidsFilter;
use App\Filter\Backer\HasPublishedFinancedAidsFilter;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

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
            'description' => '<p>Rechercher par nom.</p><p>Note : il est possible d\'avoir des résultats pertinents avec seulement le début du nom.</p>',
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
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: BackerRepository::class)]
class Backer
{
    const API_DESCRIPTION = 'Lister tous les porteurs d\'aides';
    const API_GROUP_LIST = 'backer:list';
    const FOLDER = 'backers';
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $isCorporate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalLink = null;

    #[ORM\Column]
    private ?bool $isSpotlighted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[Vich\UploadableField(mapping: 'backerLogo', fileNameProperty: 'logo')]
    private ?File $logoFile = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[Assert\Length(null, null, 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[Groups([self::API_GROUP_LIST])]
    #[ORM\ManyToOne(inversedBy: 'backers')]
    private ?Perimeter $perimeter = null;

    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: Organization::class)]
    private Collection $organizations;

    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: DataSource::class)]
    private Collection $dataSources;

    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: AidFinancer::class, orphanRemoval: true)]
    private Collection $aidFinancers;

    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: AidInstructor::class, orphanRemoval: true)]
    private Collection $aidInstructors;

    #[ORM\OneToMany(mappedBy: 'financer', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'backers')]
    private Collection $blogPromotionPosts;

    #[ORM\ManyToOne(inversedBy: 'backers')]
    private ?BackerGroup $backerGroup = null;

    #[ORM\ManyToMany(targetEntity: LogAidSearch::class, mappedBy: 'backers')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidSearches;

    #[ORM\OneToMany(mappedBy: 'backer', targetEntity: LogBackerView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logBackerViews;
    
    /**
     * Champs non en base
     */

    private ArrayCollection $categories;

    private ArrayCollection $programs;
    private ?ArrayCollection $aidsThematics;
    
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
        if (trim($logo) !== '') {
            $this->logo = self::FOLDER.'/'.$logo;
        } else {
            $this->logo = null;
        }

        return $this;
    }

    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    public function getLogoFile(): ?File
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
        if ($this->organizations->removeElement($organization)) {
            // set the owning side to null (unless already changed)
            if ($organization->getBacker() === $this) {
                $organization->setBacker(null);
            }
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
        if ($this->dataSources->removeElement($dataSource)) {
            // set the owning side to null (unless already changed)
            if ($dataSource->getBacker() === $this) {
                $dataSource->setBacker(null);
            }
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
        if ($this->aidFinancers->removeElement($aidFinancer)) {
            // set the owning side to null (unless already changed)
            if ($aidFinancer->getBacker() === $this) {
                $aidFinancer->setBacker(null);
            }
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
        if ($this->aidInstructors->removeElement($aidInstructor)) {
            // set the owning side to null (unless already changed)
            if ($aidInstructor->getBacker() === $this) {
                $aidInstructor->setBacker(null);
            }
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
        if ($this->projectValidateds->removeElement($projectValidated)) {
            // set the owning side to null (unless already changed)
            if ($projectValidated->getFinancer() === $this) {
                $projectValidated->setFinancer(null);
            }
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

    private ?array $aidsLive = [];
    public function getAidsLive() : ?array
    {
        return $this->aidsLive;
    }
    public function setAidsLive(?array $aids) : static {
        $this->aidsLive = $aids;
        return $this;
    }

    private ?array $aidsFinancial = [];
    public function getAidsFinancial() : ?array
    {
        if (count($this->aidsFinancial) > 0) {
            return $this->aidsFinancial;
        }

        $aidsFinancial = [];
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getAidTypes() as $aidType) {
                if ($aidType->getAidTypeGroup()->getSlug() == AidTypeGroup::SLUG_FINANCIAL) {
                    $aidsFinancial[] = $aid;
                }
            }
        }

        $this->setAidsFinancial($aidsFinancial);
        return $this->aidsFinancial;
    }
    public function setAidsFinancial(?array $aids) : static
    {
        $this->aidsFinancial = $aids;
        return $this;
    }

    private ?array $aidsTechnical = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;
    public function getAidsTechnical() : ?array
    {
        if (count($this->aidsTechnical) > 0) {
            return $this->aidsTechnical;
        }
        $aidsTechnical = [];
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getAidTypes() as $aidType) {
                if ($aidType->getAidTypeGroup()->getSlug() == AidTypeGroup::SLUG_TECHNICAL) {
                    $aidsTechnical[] = $aid;
                }
            }
        }

        $this->setAidsTechnical($aidsTechnical);
        return $this->aidsTechnical;
    }
    public function setAidsTechnical(?array $aids) : static
    {
        $this->aidsTechnical = $aids;
        return $this;
    }

    public function getAidsByAidTypeSlug($aidTypeSlug = null) : array
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
    

    


    public function getAidsThematics() : ?ArrayCollection
    {
        $thematics = new ArrayCollection();
        foreach ($this->getAidsLive() as $aid) {
            foreach ($aid->getCategories() as $category) {
                if (!$thematics->contains($category->getCategoryTheme())) {
                    $thematics->add($category->getCategoryTheme());
                }
            }
        }

        $iterator = $thematics->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getSlug() < $b->getSlug()) ? -1 : 1;
        });
        $thematics = new ArrayCollection(iterator_to_array($iterator));

        return $thematics;
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
        if ($this->logBackerViews->removeElement($logBackerView)) {
            // set the owning side to null (unless already changed)
            if ($logBackerView->getBacker() === $this) {
                $logBackerView->setBacker(null);
            }
        }

        return $this;
    }




    public function getCategories(): ArrayCollection
    {
        $categories = new ArrayCollection();
        foreach ($this->getAidFinancers() as $aidFinancer) {
            if ($aidFinancer->getAid()) {
                foreach ($aidFinancer->getAid()->getCategories() as $category) {
                    if (!$categories->contains($category)) {
                        $categories->add($category);
                    }
                }
            }
        }

        foreach ($this->getAidInstructors() as $aidInstructor) {
            if ($aidInstructor->getAid()) {
                foreach ($aidInstructor->getAid()->getCategories() as $category) {
                    if (!$categories->contains($category)) {
                        $categories->add($category);
                    }
                }
            }
        }

        return $categories;
    }

    public function getPrograms(): ArrayCollection
    {
        $programs = new ArrayCollection();

        foreach ($this->getAidFinancers() as $aidFinancer) {
            if ($aidFinancer->getAid()) {
                foreach ($aidFinancer->getAid()->getPrograms() as $program) {
                    if (!$programs->contains($program)) {
                        $programs->add($program);
                    }
                }
            }
        }

        foreach ($this->getAidInstructors() as $aidInstructor) {
            if ($aidInstructor->getAid()) {
                foreach ($aidInstructor->getAid()->getPrograms() as $program) {
                    if (!$programs->contains($program)) {
                        $programs->add($program);
                    }
                }
            }
        }

        return $programs;
    }

    public function  __toString(): string
    {
        return $this->getName() ?? 'Backer';
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
}
