<?php

namespace App\Entity\Blog;

use App\Entity\Log\LogBlogPostView;
use App\Entity\User\User;
use App\Repository\Blog\BlogPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
#[ORM\Index(columns: ['status'], name: 'status_blog')]
#[ORM\Index(columns: ['slug'], name: 'slug_blog')]
class BlogPost // NOSONAR too much methods
{
    public const FOLDER = 'blog';

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
    private ?string $hat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    private ?string $logoFile = null;

    private bool $deleteLogo = false;

    #[Assert\Length(max: 16)]
    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\ManyToOne(inversedBy: 'blogPosts')]
    private ?BlogPostCategory $blogPostCategory = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePublished = null;

    #[ORM\Column(nullable: true)]
    private ?int $oldId = null;

    /**
     * @var Collection<int, LogBlogPostView>
     */
    #[ORM\OneToMany(mappedBy: 'blogPost', targetEntity: LogBlogPostView::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private Collection $logBlogPostViews;

    public function __construct()
    {
        $this->logBlogPostViews = new ArrayCollection();
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

    public function getHat(): ?string
    {
        return $this->hat;
    }

    public function setHat(string $hat): static
    {
        $this->hat = $hat;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getBlogPostCategory(): ?BlogPostCategory
    {
        return $this->blogPostCategory;
    }

    public function setBlogPostCategory(?BlogPostCategory $blogPostCategory): static
    {
        $this->blogPostCategory = $blogPostCategory;

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

    public function getDatePublished(): ?\DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(?\DateTimeInterface $datePublished): static
    {
        $this->datePublished = $datePublished;

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
     * @return Collection<int, LogBlogPostView>
     */
    public function getLogBlogPostViews(): Collection
    {
        return $this->logBlogPostViews;
    }

    public function addLogBlogPostView(LogBlogPostView $logBlogPostView): static
    {
        if (!$this->logBlogPostViews->contains($logBlogPostView)) {
            $this->logBlogPostViews->add($logBlogPostView);
            $logBlogPostView->setBlogPost($this);
        }

        return $this;
    }

    public function removeLogBlogPostView(LogBlogPostView $logBlogPostView): static
    {
        if ($this->logBlogPostViews->removeElement($logBlogPostView) && $logBlogPostView->getBlogPost() === $this) {
            $logBlogPostView->setBlogPost(null);
        }

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
}
