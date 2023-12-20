<?php

namespace App\Entity\Category;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\Log\LogAidSearch;
use App\Entity\Search\SearchPage;
use App\Repository\Category\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    const API_GROUP_LIST = 'category:list';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $shortDescription = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoryTheme $categoryTheme = null;

    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'categories')]
    private Collection $aids;

    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'categories')]
    private Collection $blogPromotionPosts;

    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'categories')]
    private Collection $searchPages;

    #[ORM\ManyToMany(targetEntity: LogAidSearch::class, mappedBy: 'categories')]
    private Collection $logAidSearches;

    public function __construct()
    {
        $this->aids = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->searchPages = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getCategoryTheme(): ?CategoryTheme
    {
        return $this->categoryTheme;
    }

    public function setCategoryTheme(?CategoryTheme $categoryTheme): static
    {
        $this->categoryTheme = $categoryTheme;

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
            $aid->addCategory($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeCategory($this);
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
            $blogPromotionPost->addCategory($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            $blogPromotionPost->removeCategory($this);
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
            $searchPage->addCategory($this);
        }

        return $this;
    }

    public function removeSearchPage(SearchPage $searchPage): static
    {
        if ($this->searchPages->removeElement($searchPage)) {
            $searchPage->removeCategory($this);
        }

        return $this;
    }

    public function getCategoryThemeName()
    {
        return $this->getCategoryTheme()->getName();
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
            $logAidSearch->addCategory($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            $logAidSearch->removeCategory($this);
        }

        return $this;
    }
}
