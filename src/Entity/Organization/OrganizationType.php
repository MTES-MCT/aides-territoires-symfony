<?php

namespace App\Entity\Organization;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
use App\Entity\Search\SearchPage;
use App\Repository\Organization\OrganizationTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Aid\AidAudienceController;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationTypeRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_orgt')]
#[ORM\Index(columns: ['slug'], name: 'slug_orgt')]
#[ApiResource(
    shortName: 'Aid',
    operations: [
        new GetCollection(
            priority: 500,
            uriTemplate: '/aids/audiences/',
            controller: AidAudienceController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
            ),
        ),
    ],
)]
class OrganizationType // NOSONAR too much methods
{
    const API_GROUP_LIST = 'organization_type:list';
    const API_DESCRIPTION = 'Lister tous les choix de bénéficiaires';

    const SLUG_COMMUNE = 'commune';
    const SLUG_EPCI = 'epci';
    const SLUG_DEPARTMENT = 'department';
    const SLUG_REGION = 'region';
    const SLUG_SPECIAL = 'special';
    const SLUG_PUBLIC_ORG = 'public-org';
    const SLUG_PUBLIC_CIES = 'public-cies';
    const SLUG_ASSOCIATION = 'association';
    const SLUG_PRIVATE_SECTOR = 'private-sector';
    const SLUG_PRIVATE_PERSON = 'private-person';
    const SLUG_FARMER = 'farmer';
    const SLUG_RESEARCHER = 'researcher';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[Groups([self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[MaxDepth(1)]
    #[ORM\ManyToOne(inversedBy: 'organizationTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OrganizationTypeGroup $organizationTypeGroup = null;

    #[ORM\OneToMany(mappedBy: 'organizationType', targetEntity: Organization::class)]
    private Collection $organizations;

    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'aidAudiences')]
    private Collection $aids;

    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'organizationTypes')]
    private Collection $blogPromotionPosts;

    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'organizationTypes')]
    private Collection $searchPages;

    #[ORM\ManyToMany(targetEntity: LogAidView::class, mappedBy: 'organizationTypes')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logAidViews;

    #[ORM\ManyToMany(targetEntity: LogAidSearch::class, mappedBy: 'organizationTypes')]
    private Collection $logAidSearches;

    public function __construct()
    {
        $this->organizations = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->searchPages = new ArrayCollection();
        $this->logAidViews = new ArrayCollection();
        $this->logAidSearches = new ArrayCollection();
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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    public function getOrganizationTypeGroup(): ?OrganizationTypeGroup
    {
        return $this->organizationTypeGroup;
    }

    public function setOrganizationTypeGroup(?OrganizationTypeGroup $organizationTypeGroup): static
    {
        $this->organizationTypeGroup = $organizationTypeGroup;

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
            $organization->setOrganizationType($this);
        }

        return $this;
    }

    public function removeOrganization(Organization $organization): static
    {
        if ($this->organizations->removeElement($organization) && $organization->getOrganizationType() === $this) {
            $organization->setOrganizationType(null);
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
            $aid->addAidAudience($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeAidAudience($this);
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
            $blogPromotionPost->addOrganizationType($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            $blogPromotionPost->removeOrganizationType($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchPage>
     */
    public function getSearchPages(): Collection
    {
        return $this->searchPages;
    }

    public function addSearchPage(SearchPage $searchPage): static
    {
        if (!$this->searchPages->contains($searchPage)) {
            $this->searchPages->add($searchPage);
            $searchPage->addOrganizationType($this);
        }

        return $this;
    }

    public function removeSearchPage(SearchPage $searchPage): static
    {
        if ($this->searchPages->removeElement($searchPage)) {
            $searchPage->removeOrganizationType($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidView>
     */
    public function getLogAidViews(): Collection
    {
        return $this->logAidViews;
    }

    public function addLogAidView(LogAidView $logAidView): static
    {
        if (!$this->logAidViews->contains($logAidView)) {
            $this->logAidViews->add($logAidView);
            $logAidView->addOrganizationType($this);
        }

        return $this;
    }

    public function removeLogAidView(LogAidView $logAidView): static
    {
        if ($this->logAidViews->removeElement($logAidView)) {
            $logAidView->removeOrganizationType($this);
        }

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
            $logAidSearch->addOrganizationType($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            $logAidSearch->removeOrganizationType($this);
        }

        return $this;
    }



    public function __toString(): string
    {
        return $this->name ?? 'Organization Type';
    }
}
