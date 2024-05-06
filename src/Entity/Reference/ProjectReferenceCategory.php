<?php

namespace App\Entity\Reference;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Reference\ProjectReferenceCategoryController;
use App\Repository\Reference\ProjectReferenceCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Projets référents',
    operations: [
        new GetCollection(
            uriTemplate: '/project-reference-categories/',
            controller: ProjectReferenceCategoryController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
            ),
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationClientItemsPerPage: true
        )
    ]
)]
#[ORM\Entity(repositoryClass: ProjectReferenceCategoryRepository::class)]
class ProjectReferenceCategory
{
    const API_GROUP_LIST = 'project_reference_category:list';
    const API_GROUP_ITEM = 'project_reference_category:item';
    const API_DESCRIPTION = 'Lister toutes les catégories de projet référent';

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM, ProjectReference::API_GROUP_LIST, ProjectReference::API_GROUP_ITEM])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM, ProjectReference::API_GROUP_LIST, ProjectReference::API_GROUP_ITEM])]
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
        if ($this->projectReferences->removeElement($projectReference) && $projectReference->getProjectReferenceCategory() === $this) {
            $projectReference->setProjectReferenceCategory(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
