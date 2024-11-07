<?php

namespace App\Entity\Aid;

use App\Repository\Aid\AidTypeGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AidTypeGroupRepository::class)]
#[ORM\Index(columns: ['slug'], name: 'slug_aid_type_group')]
class AidTypeGroup
{
    public const API_GROUP_LIST = 'aid_type_group:list';


    public const SLUG_FINANCIAL = 'financial-group';
    public const SLUG_TECHNICAL = 'technical-group';

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
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, self::API_GROUP_LIST])]
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

    /**
     * @var Collection<int, AidType>
     */
    #[ORM\OneToMany(mappedBy: 'aidTypeGroup', targetEntity: AidType::class)]
    private Collection $aidTypes;

    public function __construct()
    {
        $this->aidTypes = new ArrayCollection();
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

    /**
     * @return Collection<int, AidType>
     */
    public function getAidTypes(): Collection
    {
        return $this->aidTypes;
    }

    public function addAidType(AidType $aidType): static
    {
        if (!$this->aidTypes->contains($aidType)) {
            $this->aidTypes->add($aidType);
            $aidType->setAidTypeGroup($this);
        }

        return $this;
    }

    public function removeAidType(AidType $aidType): static
    {
        if ($this->aidTypes->removeElement($aidType) && $aidType->getAidTypeGroup() === $this) {
            $aidType->setAidTypeGroup(null);
        }

        return $this;
    }
}
