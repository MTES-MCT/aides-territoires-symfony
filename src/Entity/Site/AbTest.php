<?php

namespace App\Entity\Site;

use App\Repository\Site\AbTestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AbTestRepository::class)]
#[ORM\Index(columns: ['name'], name: 'name_ab_test')]
class AbTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $ratio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(nullable: true)]
    private ?int $hourStart = null;

    #[ORM\Column(nullable: true)]
    private ?int $hourEnd = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    /**
     * @var Collection<int, AbTestUser>
     */
    #[ORM\OneToMany(mappedBy: 'abTest', targetEntity: AbTestUser::class, orphanRemoval: true)]
    private Collection $abTestUsers;

    /**
     * @var Collection<int, AbTestVote>
     */
    #[ORM\OneToMany(mappedBy: 'abTest', targetEntity: AbTestVote::class, orphanRemoval: true)]
    private Collection $abTestVotes;

    public function __construct()
    {
        $this->abTestUsers = new ArrayCollection();
        $this->abTestVotes = new ArrayCollection();
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

    /**
     * @return Collection<int, AbTestVote>
     */
    public function getAbTestVotes(): Collection
    {
        return $this->abTestVotes;
    }

    public function addAbTestVote(AbTestVote $abTestVote): static
    {
        if (!$this->abTestVotes->contains($abTestVote)) {
            $this->abTestVotes->add($abTestVote);
            $abTestVote->setAbTest($this);
        }

        return $this;
    }

    public function removeAbTestVote(AbTestVote $abTestVote): static
    {
        if ($this->abTestVotes->removeElement($abTestVote)) {
            // set the owning side to null (unless already changed)
            if ($abTestVote->getAbTest() === $this) {
                $abTestVote->setAbTest(null);
            }
        }

        return $this;
    }

    public function getHourStart(): ?int
    {
        return $this->hourStart;
    }

    public function setHourStart(?int $hourStart): static
    {
        $this->hourStart = $hourStart;

        return $this;
    }

    public function getHourEnd(): ?int
    {
        return $this->hourEnd;
    }

    public function setHourEnd(?int $hourEnd): static
    {
        $this->hourEnd = $hourEnd;

        return $this;
    }

    public function getRatio(): ?int
    {
        return $this->ratio;
    }

    public function setRatio(int $ratio): static
    {
        $this->ratio = $ratio;

        return $this;
    }
}
