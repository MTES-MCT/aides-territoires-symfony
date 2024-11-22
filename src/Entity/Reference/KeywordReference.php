<?php

namespace App\Entity\Reference;

use App\Entity\Aid\Aid;
use App\Entity\Blog\BlogPromotionPost;
use App\Repository\Reference\KeywordReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: KeywordReferenceRepository::class)]
#[ORM\Index(columns: ['name'], name: 'name_kr')]
#[ORM\Index(columns: ['intention'], name: 'intention_kr')]
#[ORM\Index(columns: ['name'], name: 'name_kr_fulltext', flags: ['fulltext'])]
class KeywordReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $intention = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'keywordReferences')]
    private ?self $parent = null;

    /**
     * @var Collection<int, KeywordReference>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $keywordReferences;

    /**
     * @var Collection<int, BlogPromotionPost>
     */
    #[ORM\ManyToMany(targetEntity: BlogPromotionPost::class, mappedBy: 'keywordReferences')]
    private Collection $blogPromotionPosts;

    /**
     * @var Collection<int, Aid>
     */
    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'keywordReferences')]
    private Collection $aids;

    /**
     * @var Collection<int, KeywordReferenceSuggested>
     */
    #[ORM\OneToMany(mappedBy: 'keywordReference', targetEntity: KeywordReferenceSuggested::class, orphanRemoval: true)]
    private Collection $keywordReferenceSuggesteds;

    /**
     * @var Collection<int, ProjectReference>
     */
    #[ORM\ManyToMany(targetEntity: ProjectReference::class, mappedBy: 'excludedKeywordReferences')]
    private Collection $excludedProjectReferences;

    /**
     * @var Collection<int, ProjectReference>
     */
    #[ORM\ManyToMany(targetEntity: ProjectReference::class, mappedBy: 'requiredKeywordReferences')]
    private Collection $requiredProjectReferences;

    public function __construct()
    {
        $this->keywordReferences = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->keywordReferenceSuggesteds = new ArrayCollection();
        $this->excludedProjectReferences = new ArrayCollection();
        $this->requiredProjectReferences = new ArrayCollection();
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
        if ($this->keywordReferences->removeElement($keywordReference) && $keywordReference->getParent() === $this) {
            $keywordReference->setParent(null);
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
            $blogPromotionPost->addKeywordReference($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if ($this->blogPromotionPosts->removeElement($blogPromotionPost)) {
            $blogPromotionPost->removeKeywordReference($this);
        }

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
            $aid->addKeywordReference($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeKeywordReference($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, KeywordReferenceSuggested>
     */
    public function getKeywordReferenceSuggesteds(): Collection
    {
        return $this->keywordReferenceSuggesteds;
    }

    public function addKeywordReferenceSuggested(KeywordReferenceSuggested $keywordReferenceSuggested): static
    {
        if (!$this->keywordReferenceSuggesteds->contains($keywordReferenceSuggested)) {
            $this->keywordReferenceSuggesteds->add($keywordReferenceSuggested);
            $keywordReferenceSuggested->setKeywordReference($this);
        }

        return $this;
    }

    public function removeKeywordReferenceSuggested(KeywordReferenceSuggested $keywordReferenceSuggested): static
    {
        if ($this->keywordReferenceSuggesteds->removeElement($keywordReferenceSuggested)) {
            // set the owning side to null (unless already changed)
            if ($keywordReferenceSuggested->getKeywordReference() === $this) {
                $keywordReferenceSuggested->setKeywordReference(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectReference>
     */
    public function getExcludedProjectReferences(): Collection
    {
        return $this->excludedProjectReferences;
    }

    public function addExcludedProjectReference(ProjectReference $excludedProjectReference): static
    {
        if (!$this->excludedProjectReferences->contains($excludedProjectReference)) {
            $this->excludedProjectReferences->add($excludedProjectReference);
            $excludedProjectReference->addExcludedKeywordReference($this);
        }

        return $this;
    }

    public function removeExcludedProjectReference(ProjectReference $excludedProjectReference): static
    {
        if ($this->excludedProjectReferences->removeElement($excludedProjectReference)) {
            $excludedProjectReference->removeExcludedKeywordReference($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectReference>
     */
    public function getRequiredProjectReferences(): Collection
    {
        return $this->requiredProjectReferences;
    }

    public function addRequiredProjectReference(ProjectReference $requiredProjectReference): static
    {
        if (!$this->requiredProjectReferences->contains($requiredProjectReference)) {
            $this->requiredProjectReferences->add($requiredProjectReference);
            $requiredProjectReference->addRequiredKeywordReference($this);
        }

        return $this;
    }

    public function removeRequiredProjectReference(ProjectReference $requiredProjectReference): static
    {
        if ($this->requiredProjectReferences->removeElement($requiredProjectReference)) {
            $requiredProjectReference->removeRequiredKeywordReference($this);
        }

        return $this;
    }
}
