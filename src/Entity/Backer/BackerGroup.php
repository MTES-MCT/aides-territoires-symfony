<?php

namespace App\Entity\Backer;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Backer\BackerGroupController;
use App\Repository\Backer\BackerGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'backer_group',
    operations: [
        new GetCollection(
            uriTemplate: '/backer-groups/',
            controller: BackerGroupController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
                tags: [Backer::API_TAG],
            ),
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true
        ),
    ]
)]
#[ORM\Entity(repositoryClass: BackerGroupRepository::class)]
class BackerGroup
{
    public const API_DESCRIPTION = 'Lister tous les groupes de porteurs d\'aides';
    public const API_GROUP_LIST = 'backer_group:list';

    #[Groups([self::API_GROUP_LIST])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'backerGroups')]
    private ?BackerSubcategory $backerSubCategory = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[Groups([self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[Groups([self::API_GROUP_LIST])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeCreate = null;

    /**
     * @var Collection<int, Backer>
     */
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
        if ($this->backers->removeElement($backer) && $backer->getBackerGroup() === $this) {
            $backer->setBackerGroup(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Groupe porteur';
    }
}
