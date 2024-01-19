<?php

namespace App\Entity\Reference;

use App\Repository\Reference\ProjectReferenceCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectReferenceCategoryRepository::class)]
class ProjectReferenceCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'projectReferenceCategory', targetEntity: ProjectReference::class)]
    private Collection $projectReferences;

    public function __construct()
    {
        $this->projectReferences = new ArrayCollection();
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
     * @return Collection<int, ProjectReference>
     */
    public function getProjectReferences(): Collection
    {
        return $this->projectReferences;
    }

    public function addProjectReference(ProjectReference $projectReference): static
    {
        if (!$this->projectReferences->contains($projectReference)) {
            $this->projectReferences->add($projectReference);
            $projectReference->setProjectReferenceCategory($this);
        }

        return $this;
    }

    public function removeProjectReference(ProjectReference $projectReference): static
    {
        if ($this->projectReferences->removeElement($projectReference)) {
            // set the owning side to null (unless already changed)
            if ($projectReference->getProjectReferenceCategory() === $this) {
                $projectReference->setProjectReferenceCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
