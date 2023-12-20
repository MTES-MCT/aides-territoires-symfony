<?php

namespace App\Entity\Bundle;

use App\Entity\Aid\Aid;
use App\Entity\User\User;
use App\Repository\Bundle\BundleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// TODO voir utilite, faire BO si besoin
#[ORM\Entity(repositoryClass: BundleRepository::class)]
class Bundle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 64)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'bundles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Aid::class, inversedBy: 'bundles')]
    private Collection $aids;

    public function __construct()
    {
        $this->aids = new ArrayCollection();
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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

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
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        $this->aids->removeElement($aid);

        return $this;
    }
}
