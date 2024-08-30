<?php

namespace App\Entity\Program;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogProgramView;
use App\Entity\Page\FaqQuestionAnswser;
use App\Entity\Program\PageTab;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Program\ProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Program\ProgramController;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Index(columns: ['slug'], name: 'slug_program')]
#[ORM\Index(columns: ['is_spotlighted'], name: 'is_spotlighted_program')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/programs/',
            controller: ProgramController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister tous les programmes d\'aides',
            ),
        ),
    ],
)]
#[ORM\Entity(repositoryClass: ProgramRepository::class)]
class Program // NOSONAR too much methods
{
    const FOLDER = 'programs';
    
    const API_GROUP_LIST = 'program:list';

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
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $logo = null;

    private $logoFile = null;

    private bool $deleteLogo = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\ManyToOne(inversedBy: 'programs')]
    private ?Perimeter $perimeter = null;

    #[ORM\Column]
    private ?bool $isSpotlighted = null;

    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'programs')]
    private Collection $aids;

    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'programs')]
    private Collection $blogPromotionPosts;

    #[ORM\OneToMany(mappedBy: 'program', targetEntity: FaqQuestionAnswser::class)]
    #[ORM\OrderBy(["id"=>"ASC"])]
    private Collection $faqQuestionAnswsers;

    #[ORM\OneToMany(mappedBy: 'program', targetEntity: PageTab::class)]
    private Collection $pageTabs;

    #[ORM\ManyToMany(targetEntity: LogAidSearch::class, mappedBy: 'programs')]
    private Collection $logAidSearches;

    #[ORM\OneToMany(mappedBy: 'program', targetEntity: LogProgramView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logProgramViews;


    private ?int $nbAids;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeUpdate = null;

    public function __construct()
    {
        $this->aids = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->faqQuestionAnswsers = new ArrayCollection();
        $this->pageTabs = new ArrayCollection();
        $this->logAidSearches = new ArrayCollection();
        $this->logProgramViews = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

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

    public function isIsSpotlighted(): ?bool
    {
        return $this->isSpotlighted;
    }

    public function setIsSpotlighted(bool $isSpotlighted): static
    {
        $this->isSpotlighted = $isSpotlighted;

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
            $aid->addProgram($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeProgram($this);
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
            $blogPromotionPost->addProgram($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            $blogPromotionPost->removeProgram($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, FaqQuestionAnswser>
     */
    public function getFaqQuestionAnswsers(): Collection
    {
        return $this->faqQuestionAnswsers;
    }

    public function addFaqQuestionAnswser(FaqQuestionAnswser $faqQuestionAnswser): static
    {
        if (!$this->faqQuestionAnswsers->contains($faqQuestionAnswser)) {
            $this->faqQuestionAnswsers->add($faqQuestionAnswser);
            $faqQuestionAnswser->setProgram($this);
        }

        return $this;
    }

    public function removeFaqQuestionAnswser(FaqQuestionAnswser $faqQuestionAnswser): static
    {
        if ($this->faqQuestionAnswsers->removeElement($faqQuestionAnswser) && $faqQuestionAnswser->getProgram() === $this) {
            $faqQuestionAnswser->setProgram(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PageTab>
     */
    public function getPageTabs(): Collection
    {
        return $this->pageTabs;
    }

    public function addPageTab(PageTab $pageTab): static
    {
        if (!$this->pageTabs->contains($pageTab)) {
            $this->pageTabs->add($pageTab);
            $pageTab->setProgram($this);
        }

        return $this;
    }

    public function removePageTab(PageTab $pageTab): static
    {
        if ($this->pageTabs->removeElement($pageTab) && $pageTab->getProgram() === $this) {
            $pageTab->setProgram(null);
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
            $logAidSearch->addProgram($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            $logAidSearch->removeProgram($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogProgramView>
     */
    public function getLogProgramViews(): Collection
    {
        return $this->logProgramViews;
    }

    public function addLogProgramView(LogProgramView $logProgramView): static
    {
        if (!$this->logProgramViews->contains($logProgramView)) {
            $this->logProgramViews->add($logProgramView);
            $logProgramView->setProgram($this);
        }

        return $this;
    }

    public function removeLogProgramView(LogProgramView $logProgramView): static
    {
        if ($this->logProgramViews->removeElement($logProgramView) && $logProgramView->getProgram() === $this) {
            $logProgramView->setProgram(null);
        }

        return $this;
    }



    public function  __toString(): string
    {
        return $this->name ?? null;
    }

    public function getNbAids() : ?int {
        try {
            return count($this->aids);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setNbAids(?int $nbAids): static
    {
        $this->nbAids = $nbAids;
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
