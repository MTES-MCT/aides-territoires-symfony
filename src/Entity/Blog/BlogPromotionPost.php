<?php

namespace App\Entity\Blog;

use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Log\LogBlogPromotionPostClick;
use App\Entity\Log\LogBlogPromotionPostDisplay;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\KeywordReference;
use App\Repository\Blog\BlogPromotionPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogPromotionPostRepository::class)]
#[ORM\Index(columns: ['status'], name: 'status_blog_promotion_post')]
#[ORM\Index(columns: ['slug'], name: 'slug_blog_promotion_post')]
class BlogPromotionPost // NOSONAR too much methods
{
    public const FOLDER = 'promotion';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEWABLE = 'reviewable';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DELETED = 'deleted';
    public const STATUSES = [
        ['slug' => self::STATUS_DRAFT, 'name' => 'Brouillon'],
        ['slug' => self::STATUS_REVIEWABLE, 'name' => 'En revue'],
        ['slug' => self::STATUS_PUBLISHED, 'name' => 'Publié'],
        ['slug' => self::STATUS_DELETED, 'name' => 'Supprimé']
    ];


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
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortText = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $buttonLink = null;

    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120)]
    private ?string $buttonTitle = null;

    #[Assert\Length(max: 16)]
    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\ManyToOne(inversedBy: 'blogPromotionPosts')]
    private ?Perimeter $perimeter = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    private ?string $imageFile = null;

    private bool $deleteImage = false;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageAltText = null;

    #[ORM\Column]
    private ?bool $externalLink = null;

    /**
     * @var Collection<int, OrganizationType>
     */
    #[ORM\ManyToMany(targetEntity: OrganizationType::class, inversedBy: 'blogPromotionPosts')]
    private Collection $organizationTypes;

    /**
     * @var Collection<int, Backer>
     */
    #[ORM\ManyToMany(targetEntity: Backer::class, inversedBy: 'blogPromotionPosts')]
    private Collection $backers;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'blogPromotionPosts')]
    private Collection $categories;

    /**
     * @var Collection<int, Program>
     */
    #[ORM\ManyToMany(targetEntity: Program::class, inversedBy: 'blogPromotionPosts')]
    private Collection $programs;

    /**
     * @var Collection<int, LogBlogPromotionPostClick>
     */
    #[ORM\OneToMany(mappedBy: 'blogPromotionPost', targetEntity: LogBlogPromotionPostClick::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logBlogPromotionPostClicks;

    /**
     * @var Collection<int, LogBlogPromotionPostDisplay>
     */
    #[ORM\OneToMany(mappedBy: 'blogPromotionPost', targetEntity: LogBlogPromotionPostDisplay::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logBlogPromotionPostDisplays;

    /**
     * @var Collection<int, KeywordReference>
     */
    #[ORM\ManyToMany(targetEntity: KeywordReference::class, inversedBy: 'blogPromotionPosts')]
    private Collection $keywordReferences;

    public function __construct()
    {
        $this->organizationTypes = new ArrayCollection();
        $this->backers = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->programs = new ArrayCollection();
        $this->logBlogPromotionPostClicks = new ArrayCollection();
        $this->logBlogPromotionPostDisplays = new ArrayCollection();
        $this->keywordReferences = new ArrayCollection();
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

    public function getShortText(): ?string
    {
        return $this->shortText;
    }

    public function setShortText(?string $shortText): static
    {
        $this->shortText = $shortText;

        return $this;
    }

    public function getButtonLink(): ?string
    {
        return $this->buttonLink;
    }

    public function setButtonLink(string $buttonLink): static
    {
        $this->buttonLink = $buttonLink;

        return $this;
    }

    public function getButtonTitle(): ?string
    {
        return $this->buttonTitle;
    }

    public function setButtonTitle(string $buttonTitle): static
    {
        $this->buttonTitle = $buttonTitle;

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

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): static
    {
        $this->timeCreate = $timeCreate;

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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

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

    public function setImageFile(?string $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        }
    }

    public function getImageFile(): ?string
    {
        return $this->imageFile;
    }

    public function getImageAltText(): ?string
    {
        return $this->imageAltText;
    }

    public function setImageAltText(?string $imageAltText): static
    {
        $this->imageAltText = $imageAltText;

        return $this;
    }

    public function isExternalLink(): ?bool
    {
        return $this->externalLink;
    }

    public function setExternalLink(bool $externalLink): static
    {
        $this->externalLink = $externalLink;

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
     * @return Collection<int, LogBlogPromotionPostClick>
     */
    public function getLogBlogPromotionPostClicks(): Collection
    {
        return $this->logBlogPromotionPostClicks;
    }

    public function addLogBlogPromotionPostClick(LogBlogPromotionPostClick $logBlogPromotionPostClick): static
    {
        if (!$this->logBlogPromotionPostClicks->contains($logBlogPromotionPostClick)) {
            $this->logBlogPromotionPostClicks->add($logBlogPromotionPostClick);
            $logBlogPromotionPostClick->setBlogPromotionPost($this);
        }

        return $this;
    }

    public function removeLogBlogPromotionPostClick(LogBlogPromotionPostClick $logBlogPromotionPostClick): static
    {
        if (
            $this->logBlogPromotionPostClicks->removeElement($logBlogPromotionPostClick)
            && $logBlogPromotionPostClick->getBlogPromotionPost() === $this
        ) {
            $logBlogPromotionPostClick->setBlogPromotionPost(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBlogPromotionPostDisplay>
     */
    public function getLogBlogPromotionPostDisplays(): Collection
    {
        return $this->logBlogPromotionPostDisplays;
    }

    public function addLogBlogPromotionPostDisplay(LogBlogPromotionPostDisplay $logBlogPromotionPostDisplay): static
    {
        if (!$this->logBlogPromotionPostDisplays->contains($logBlogPromotionPostDisplay)) {
            $this->logBlogPromotionPostDisplays->add($logBlogPromotionPostDisplay);
            $logBlogPromotionPostDisplay->setBlogPromotionPost($this);
        }

        return $this;
    }

    public function removeLogBlogPromotionPostDisplay(LogBlogPromotionPostDisplay $logBlogPromotionPostDisplay): static
    {
        if (
            $this->logBlogPromotionPostDisplays->removeElement($logBlogPromotionPostDisplay)
            && $logBlogPromotionPostDisplay->getBlogPromotionPost() === $this
        ) {
            $logBlogPromotionPostDisplay->setBlogPromotionPost(null);
        }

        return $this;
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

    /**
     * @return Collection<int, KeywordReference>
     */
    public function getKeywordReferences(): Collection
    {
        return $this->keywordReferences;
    }

    public function addKeywordReference(KeywordReference $keywordReference): static
    {
        if (!$this->keywordReferences->contains($keywordReference)) {
            $this->keywordReferences->add($keywordReference);
        }

        return $this;
    }

    public function removeKeywordReference(KeywordReference $keywordReference): static
    {
        $this->keywordReferences->removeElement($keywordReference);

        return $this;
    }
}
