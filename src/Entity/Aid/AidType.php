<?php

namespace App\Entity\Aid;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\Aid\AidTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Aid\AidTypeController;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Aid',
    operations: [
        new GetCollection(
            uriTemplate: '/aids/types/',
            controller: AidTypeController::class,
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
            ),
        ),
    ],
)]
#[ORM\Entity(repositoryClass: AidTypeRepository::class)]
class AidType
{
    public const API_GROUP_LIST = 'aid_type:list';
    public const API_DESCRIPTION = 'Lister tous les choix de types d\'aides';

    public const TYPE_FINANCIAL_SLUGS = ['grant', 'loan', 'recoverable-advance', 'other', 'cee'];
    public const TYPE_TECHNICAL_SLUG = ['technical-engineering', 'financial-engineering', 'legal-engineering'];

    public const SLUG_GRANT = 'grant';
    public const SLUG_LOAN = 'loan';
    public const SLUG_RECOVERABLE_ADVANCE = 'recoverable-advance';
    public const SLUG_CEE = 'cee';
    public const SLUG_OTHER = 'other';

    public const SLUG_TECHNICAL_ENGINEERING = 'technical-engineering';
    public const SLUG_FINANCIAL_ENGINEERING = 'financial-engineering';
    public const SLUG_LEGAL_ENGINEERING = 'legal-engineering';



    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
    #[ORM\ManyToOne(inversedBy: 'aidTypes')]
    private ?AidTypeGroup $aidTypeGroup = null;

    #[ORM\ManyToMany(targetEntity: Aid::class, mappedBy: 'aidTypes')]
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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    public function getAidTypeGroup(): ?AidTypeGroup
    {
        return $this->aidTypeGroup;
    }

    public function setAidTypeGroup(?AidTypeGroup $aidTypeGroup): static
    {
        $this->aidTypeGroup = $aidTypeGroup;

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
            $aid->addAidType($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            $aid->removeAidType($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getName() ?? '';
    }
}
