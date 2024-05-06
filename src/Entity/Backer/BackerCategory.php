<?php

namespace App\Entity\Backer;

use App\Repository\Backer\BackerCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BackerCategoryRepository::class)]
class BackerCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column]
    // #[Gedmo\SortablePosition]
    private ?int $position = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\OneToMany(mappedBy: 'backerCategory', targetEntity: BackerSubcategory::class)]
    private Collection $backerSubcategories;

    public function __construct()
    {
        $this->backerSubcategories = new ArrayCollection();
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    /**
     * @return Collection<int, BackerSubcategory>
     */
    public function getBackerSubcategories(): Collection
    {
        return $this->backerSubcategories;
    }

    public function addBackerSubcategory(BackerSubcategory $backerSubcategory): static
    {
        if (!$this->backerSubcategories->contains($backerSubcategory)) {
            $this->backerSubcategories->add($backerSubcategory);
            $backerSubcategory->setBackerCategory($this);
        }

        return $this;
    }

    public function removeBackerSubcategory(BackerSubcategory $backerSubcategory): static
    {
        if ($this->backerSubcategories->removeElement($backerSubcategory) && $backerSubcategory->getBackerCategory() === $this) {
            $backerSubcategory->setBackerCategory(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Categorie porteur';
    }
}
