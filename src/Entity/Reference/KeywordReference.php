<?php

namespace App\Entity\Reference;

use App\Entity\Blog\BlogPromotionPost;
use App\Repository\Reference\KeywordReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordReferenceRepository::class)]
#[ORM\Index(columns: ['name'], name: 'name_kr')]
#[ORM\Index(columns: ['intention'], name: 'intention_kr')]
class KeywordReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $intention = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'keywordReferences')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $keywordReferences;

    #[ORM\OneToMany(mappedBy: 'keywordReferences', targetEntity: BlogPromotionPost::class)]
    private Collection $blogPromotionPosts;

    public function __construct()
    {
        $this->keywordReferences = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
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

    public function isIntention(): ?bool
    {
        return $this->intention;
    }

    public function setIntention(bool $intention): static
    {
        $this->intention = $intention;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getKeywordReferences(): Collection
    {
        return $this->keywordReferences;
    }

    public function addKeywordReference(self $keywordReference): static
    {
        if (!$this->keywordReferences->contains($keywordReference)) {
            $this->keywordReferences->add($keywordReference);
            $keywordReference->setParent($this);
        }

        return $this;
    }

    public function removeKeywordReference(self $keywordReference): static
    {
        if ($this->keywordReferences->removeElement($keywordReference)) {
            // set the owning side to null (unless already changed)
            if ($keywordReference->getParent() === $this) {
                $keywordReference->setParent(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
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
            $blogPromotionPost->setKeywordReferences($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            // set the owning side to null (unless already changed)
            if ($blogPromotionPost->getKeywordReferences() === $this) {
                $blogPromotionPost->setKeywordReferences(null);
            }
        }

        return $this;
    }

}
