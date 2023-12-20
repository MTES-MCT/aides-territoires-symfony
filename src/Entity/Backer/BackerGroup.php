<?php

namespace App\Entity\Backer;

use App\Repository\Backer\BackerGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BackerGroupRepository::class)]
class BackerGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'backerGroups')]
    private ?BackerSubcategory $backerSubCategory = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\OneToMany(mappedBy: 'backerGroup', targetEntity: Backer::class)]
    private Collection $backers;

    public function __construct()
    {
        $this->backers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBackerSubCategory(): ?BackerSubcategory
    {
        return $this->backerSubCategory;
    }

    public function setBackerSubCategory(?BackerSubcategory $backerSubCategory): static
    {
        $this->backerSubCategory = $backerSubCategory;

        return $this;
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
            $backer->setBackerGroup($this);
        }

        return $this;
    }

    public function removeBacker(Backer $backer): static
    {
        if ($this->backers->removeElement($backer)) {
            // set the owning side to null (unless already changed)
            if ($backer->getBackerGroup() === $this) {
                $backer->setBackerGroup(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Groupe porteur';
    }
}
