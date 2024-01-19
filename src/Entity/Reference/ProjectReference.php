<?php

namespace App\Entity\Reference;

use App\Entity\Project\Project;
use App\Repository\Reference\ProjectReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Index(columns: ['name'], name: 'name_pr_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['slug'], name: 'slug_pr')]
#[ORM\Entity(repositoryClass: ProjectReferenceRepository::class)]
class ProjectReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'projectReferences')]
    private ?ProjectReferenceCategory $projectReferenceCategory = null;

    #[ORM\OneToMany(mappedBy: 'projectReference', targetEntity: Project::class)]
    private Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
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

    public function getProjectReferenceCategory(): ?ProjectReferenceCategory
    {
        return $this->projectReferenceCategory;
    }

    public function setProjectReferenceCategory(?ProjectReferenceCategory $projectReferenceCategory): static
    {
        $this->projectReferenceCategory = $projectReferenceCategory;

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setProjectReference($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getProjectReference() === $this) {
                $project->setProjectReference(null);
            }
        }

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

    public function  __toString(): string
    {
        return $this->getName() ?? '';
    }
}
