<?php

namespace App\Entity\Site;

use App\Repository\Site\AbTestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AbTestRepository::class)]
class AbTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, AbTestUser>
     */
    #[ORM\OneToMany(mappedBy: 'abTest', targetEntity: AbTestUser::class, orphanRemoval: true)]
    private Collection $abTestUsers;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnd = null;

    public function __construct()
    {
        $this->abTestUsers = new ArrayCollection();
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
     * @return Collection<int, AbTestUser>
     */
    public function getAbTestUsers(): Collection
    {
        return $this->abTestUsers;
    }

    public function addAbTestUser(AbTestUser $abTestUser): static
    {
        if (!$this->abTestUsers->contains($abTestUser)) {
            $this->abTestUsers->add($abTestUser);
            $abTestUser->setAbTest($this);
        }

        return $this;
    }

    public function removeAbTestUser(AbTestUser $abTestUser): static
    {
        if ($this->abTestUsers->removeElement($abTestUser)) {
            // set the owning side to null (unless already changed)
            if ($abTestUser->getAbTest() === $this) {
                $abTestUser->setAbTest(null);
            }
        }

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

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeInterface $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }
}
