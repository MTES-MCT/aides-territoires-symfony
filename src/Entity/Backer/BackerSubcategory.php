<?php

namespace App\Entity\Backer;

use App\Repository\Backer\BackerSubcategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BackerSubcategoryRepository::class)]
class BackerSubcategory
{
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
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\ManyToOne(inversedBy: 'backerSubcategories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BackerCategory $backerCategory = null;

    #[ORM\OneToMany(mappedBy: 'backerSubCategory', targetEntity: BackerGroup::class)]
    private Collection $backerGroups;

    public function __construct()
    {
        $this->backerGroups = new ArrayCollection();
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

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): static
    {
        $this->timeCreate = $timeCreate;

        return $this;
    }

    public function getBackerCategory(): ?BackerCategory
    {
        return $this->backerCategory;
    }

    public function setBackerCategory(?BackerCategory $backerCategory): static
    {
        $this->backerCategory = $backerCategory;

        return $this;
    }

    /**
     * @return Collection<int, BackerGroup>
     */
    public function getBackerGroups(): Collection
    {
        return $this->backerGroups;
    }

    public function addBackerGroup(BackerGroup $backerGroup): static
    {
        if (!$this->backerGroups->contains($backerGroup)) {
            $this->backerGroups->add($backerGroup);
            $backerGroup->setBackerSubCategory($this);
        }

        return $this;
    }

    public function removeBackerGroup(BackerGroup $backerGroup): static
    {
        if ($this->backerGroups->removeElement($backerGroup) && $backerGroup->getBackerSubCategory() === $this) {
            $backerGroup->setBackerSubCategory(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Sous-Categorie porteur';
    }
}
