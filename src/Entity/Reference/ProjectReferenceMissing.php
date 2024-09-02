<?php

namespace App\Entity\Reference;

use App\Entity\Aid\Aid;
use App\Repository\Reference\ProjectReferenceMissingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(columns: ['name'], name: 'name_prm')]
#[ORM\Entity(repositoryClass: ProjectReferenceMissingRepository::class)]
class ProjectReferenceMissing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Aid>
     */
    #[ORM\ManyToMany(targetEntity: Aid::class, inversedBy: 'projectReferenceMissings')]
    private Collection $aids;

    public function __construct()
    {
        $this->aids = new ArrayCollection();
    }

    public function  __toString(): string
    {
        return $this->name;
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
