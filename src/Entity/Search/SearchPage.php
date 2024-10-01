<?php

namespace App\Entity\Search;

use App\Entity\Aid\Aid;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Page\Page;
use App\Entity\User\User;
use App\Repository\Search\SearchPageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Index(columns: ['slug'], name: 'slug_search_page')]
#[ORM\Entity(repositoryClass: SearchPageRepository::class)]
class SearchPage // NOSONAR too much methods
{
    const FOLDER = 'minisites';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $searchQuerystring = null;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $color1 = null;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $color2 = null;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $color3 = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    private $logoFile = null;

    private bool $deleteLogo = false;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10)]
    private ?string $color4 = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoLink = null;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $color5 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $moreContent = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaImage = null;

    private $metaImageFile = null;

    private bool $deleteMetaImage = false;

    #[ORM\Column]
    private ?bool $showAudienceField = null;

    #[ORM\Column]
    private ?bool $showCategoriesField = null;

    #[ORM\Column]
    private ?bool $showPerimeterField = null;

    #[ORM\Column]
    private ?bool $showMobilizationStepField = null;

    #[ORM\ManyToMany(targetEntity: OrganizationType::class, inversedBy: 'searchPages')]
    private Collection $organizationTypes;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortTitle = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column]
    private ?bool $showAidTypeField = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column]
    private ?bool $showBackersField = null;

    #[ORM\ManyToOne(inversedBy: 'searchPages')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $administrator = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tabTitle = null;

    #[ORM\Column]
    private ?bool $showTextField = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactLink = null;

    #[ORM\Column]
    private ?bool $subdomainEnabled = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'searchPages')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Aid::class, inversedBy: 'excludedSearchPages')]
    #[ORM\JoinTable(name: 'search_aid_excluded')]
    private Collection $excludedAids;

    #[Assert\Count(
        max: 9
    )]
    #[ORM\ManyToMany(targetEntity: Aid::class, inversedBy: 'highlightedSearchPages')]
    #[ORM\JoinTable(name: 'search_aid_highlighted')]
    private Collection $highlightedAids;

    #[ORM\OneToMany(mappedBy: 'searchPage', targetEntity: Page::class, cascade: ['persist'])]
    private Collection $pages;

    private int $nbAids = 0;

    private int $nbAidsLive = 0;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $searchPageRedirect = null;

    #[ORM\OneToMany(mappedBy: 'searchPage', targetEntity: SearchPageLock::class, orphanRemoval: true)]
    private Collection $searchPageLocks;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'editorSearchPages')]
    private Collection $editors;

    public function __construct()
    {
        $this->organizationTypes = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->excludedAids = new ArrayCollection();
        $this->highlightedAids = new ArrayCollection();
        $this->pages = new ArrayCollection();
        $this->searchPageLocks = new ArrayCollection();
        $this->editors = new ArrayCollection();
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

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

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

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSearchQuerystring(): ?string
    {
        return $this->searchQuerystring;
    }

    public function setSearchQuerystring(string $searchQuerystring): static
    {
        $this->searchQuerystring = $searchQuerystring;

        return $this;
    }

    public function getColor1(): ?string
    {
        return $this->color1;
    }

    public function setColor1(?string $color1): static
    {
        $this->color1 = $color1;

        return $this;
    }

    public function getColor2(): ?string
    {
        return $this->color2;
    }

    public function setColor2(?string $color2): static
    {
        $this->color2 = $color2;

        return $this;
    }

    public function getColor3(): ?string
    {
        return $this->color3;
    }

    public function setColor3(?string $color3): static
    {
        $this->color3 = $color3;

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

    public function setLogoFile($logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    public function getLogoFile()
    {
        return $this->logoFile;
    }

    public function getColor4(): ?string
    {
        return $this->color4;
    }

    public function setColor4(string $color4): static
    {
        $this->color4 = $color4;

        return $this;
    }

    public function getLogoLink(): ?string
    {
        return $this->logoLink;
    }

    public function setLogoLink(?string $logoLink): static
    {
        $this->logoLink = $logoLink;

        return $this;
    }

    public function getColor5(): ?string
    {
        return $this->color5;
    }

    public function setColor5(?string $color5): static
    {
        $this->color5 = $color5;

        return $this;
    }

    public function getMoreContent(): ?string
    {
        return $this->moreContent;
    }

    public function setMoreContent(?string $moreContent): static
    {
        $this->moreContent = $moreContent;

        return $this;
    }

    public function getMetaImage(): ?string
    {
        return $this->metaImage;
    }

    public function setMetaImage(?string $metaImage): static
    {
        $this->metaImage = $metaImage;
        return $this;
    }

    public function setMetaImageFile($metaImageFile = null): void
    {
        $this->metaImageFile = $metaImageFile;

        if (null !== $metaImageFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    public function getMetaImageFile()
    {
        return $this->metaImageFile;
    }

    public function isShowAudienceField(): ?bool
    {
        return $this->showAudienceField;
    }

    public function setShowAudienceField(bool $showAudienceField): static
    {
        $this->showAudienceField = $showAudienceField;

        return $this;
    }

    public function isShowCategoriesField(): ?bool
    {
        return $this->showCategoriesField;
    }

    public function setShowCategoriesField(bool $showCategoriesField): static
    {
        $this->showCategoriesField = $showCategoriesField;

        return $this;
    }

    public function isShowPerimeterField(): ?bool
    {
        return $this->showPerimeterField;
    }

    public function setShowPerimeterField(bool $showPerimeterField): static
    {
        $this->showPerimeterField = $showPerimeterField;

        return $this;
    }

    public function isShowMobilizationStepField(): ?bool
    {
        return $this->showMobilizationStepField;
    }

    public function setShowMobilizationStepField(bool $showMobilizationStepField): static
    {
        $this->showMobilizationStepField = $showMobilizationStepField;

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

    public function getShortTitle(): ?string
    {
        return $this->shortTitle;
    }

    public function setShortTitle(?string $shortTitle): static
    {
        $this->shortTitle = $shortTitle;

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

    public function isShowAidTypeField(): ?bool
    {
        return $this->showAidTypeField;
    }

    public function setShowAidTypeField(bool $showAidTypeField): static
    {
        $this->showAidTypeField = $showAidTypeField;

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

    public function isShowBackersField(): ?bool
    {
        return $this->showBackersField;
    }

    public function setShowBackersField(bool $showBackersField): static
    {
        $this->showBackersField = $showBackersField;

        return $this;
    }

    public function getAdministrator(): ?User
    {
        return $this->administrator;
    }

    public function setAdministrator(?User $administrator): static
    {
        $this->administrator = $administrator;

        return $this;
    }

    public function getTabTitle(): ?string
    {
        return $this->tabTitle;
    }

    public function setTabTitle(?string $tabTitle): static
    {
        $this->tabTitle = $tabTitle;

        return $this;
    }

    public function isShowTextField(): ?bool
    {
        return $this->showTextField;
    }

    public function setShowTextField(bool $showTextField): static
    {
        $this->showTextField = $showTextField;

        return $this;
    }

    public function getContactLink(): ?string
    {
        return $this->contactLink;
    }

    public function setContactLink(?string $contactLink): static
    {
        $this->contactLink = $contactLink;

        return $this;
    }

    public function isSubdomainEnabled(): ?bool
    {
        return $this->subdomainEnabled;
    }

    public function setSubdomainEnabled(bool $subdomainEnabled): static
    {
        $this->subdomainEnabled = $subdomainEnabled;

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
     * @return Collection<int, Aid>
     */
    public function getExcludedAids(): Collection
    {
        return $this->excludedAids;
    }

    public function addExcludedAid(Aid $excludedAid): static
    {
        if (!$this->excludedAids->contains($excludedAid)) {
            $this->excludedAids->add($excludedAid);
        }

        return $this;
    }

    public function removeExcludedAid(Aid $excludedAid): static
    {
        $this->excludedAids->removeElement($excludedAid);

        return $this;
    }

    /**
     * @return Collection<int, Aid>
     */
    public function getHighlightedAids(): Collection
    {
        return $this->highlightedAids;
    }

    public function addHighlightedAid(Aid $highlightedAid): static
    {
        if (!$this->highlightedAids->contains($highlightedAid)) {
            $this->highlightedAids->add($highlightedAid);
        }

        return $this;
    }

    public function removeHighlightedAid(Aid $highlightedAid): static
    {
        $this->highlightedAids->removeElement($highlightedAid);

        return $this;
    }

    /**
     * @return Collection<int, Page>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(Page $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->setSearchPage($this);
        }

        return $this;
    }

    public function removePage(Page $page): static
    {
        if ($this->pages->removeElement($page) && $page->getSearchPage() === $this) {
            $page->setSearchPage(null);
        }

        return $this;
    }

    public function getNbAids(): int
    {
        return $this->nbAids;
    }

    public function setNbAids(int $nbAids): static
    {
        $this->nbAids = $nbAids;
        return $this;
    }

    public function getNbAidsLive(): int
    {
        return $this->nbAidsLive;
    }
    public function setNbAidsLive(int $nbAidsLive): static
    {
        $this->nbAidsLive = $nbAidsLive;
        return $this;
    }


    public function __toString(): string
    {
        return $this->name ?? 'SearchPage';
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

    public function getDeleteMetaImage(): ?bool
    {
        return $this->deleteMetaImage;
    }

    public function setDeleteMetaImage(?bool $deleteMetaImage): static
    {
        $this->deleteMetaImage = $deleteMetaImage;

        return $this;
    }

    public function getSearchPageRedirect(): ?self
    {
        return $this->searchPageRedirect;
    }

    public function setSearchPageRedirect(?self $searchPageRedirect): static
    {
        $this->searchPageRedirect = $searchPageRedirect;

        return $this;
    }

    /**
     * @return Collection<int, SearchPageLock>
     */
    public function getSearchPageLocks(): Collection
    {
        return $this->searchPageLocks;
    }

    public function addSearchPageLock(SearchPageLock $searchPageLock): static
    {
        if (!$this->searchPageLocks->contains($searchPageLock)) {
            $this->searchPageLocks->add($searchPageLock);
            $searchPageLock->setSearchPage($this);
        }

        return $this;
    }

    public function removeSearchPageLock(SearchPageLock $searchPageLock): static
    {
        if ($this->searchPageLocks->removeElement($searchPageLock)) {
            // set the owning side to null (unless already changed)
            if ($searchPageLock->getSearchPage() === $this) {
                $searchPageLock->setSearchPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getEditors(): Collection
    {
        return $this->editors;
    }

    public function addEditor(User $editor): static
    {
        if (!$this->editors->contains($editor)) {
            $this->editors->add($editor);
        }

        return $this;
    }

    public function removeEditor(User $editor): static
    {
        $this->editors->removeElement($editor);

        return $this;
    }
}
