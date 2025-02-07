<?php

namespace App\Entity\Site;

use App\Repository\Site\AbTestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
}
