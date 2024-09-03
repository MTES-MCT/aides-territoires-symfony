<?php

namespace App\Entity\Organization;

use App\Repository\Organization\OrganizationTypeGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationTypeGroupRepository::class)]
class OrganizationTypeGroup
{
    const ID_COLLECTIVITES = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\OneToMany(mappedBy: 'organizationTypeGroup', targetEntity: OrganizationType::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $organizationTypes;

    public function __construct()
    {
        $this->organizationTypes = new ArrayCollection();
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

    public function getTimeCreate(): ?\DateTimeInterface
    {
        return $this->timeCreate;
    }

    public function setTimeCreate(\DateTimeInterface $timeCreate): static
    {
        $this->timeCreate = $timeCreate;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

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
     * @return Collection<int, OrganizationType>
     */
    public function getOrganizationTypes(): Collection
    {
        return $this->organizationTypes;
    }

    public function addOrganizationType(OrganizationType $organizationType): static
    {
        if (!$this->organizationTypes->contains($organizationType)) {
            $this->organizationTypes->add($organizationType);
            $organizationType->setOrganizationTypeGroup($this);
        }

        return $this;
    }

    public function removeOrganizationType(OrganizationType $organizationType): static
    {
        if ($this->organizationTypes->removeElement($organizationType) && $organizationType->getOrganizationTypeGroup() === $this) {
            $organizationType->setOrganizationTypeGroup(null);
        }

        return $this;
    }



    public function  __toString(): string
    {
        return $this->name ?? 'Organization Type Group';
    }
}
