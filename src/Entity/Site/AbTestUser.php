<?php

namespace App\Entity\Site;

use App\Entity\User\User;
use App\Repository\Site\AbTestUserRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AbTestUserRepository::class)]
#[ORM\Index(columns: ['variation'], name: 'variation_ab_test_user')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_ab_test_user')]
class AbTestUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $variation = null;

    #[ORM\ManyToOne(inversedBy: 'abTestUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AbTest $abTest = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(length: 255)]
    private ?string $cookieId = null;

    /**
     * @var Collection<int, AbTestVote>
     */
    #[ORM\OneToMany(mappedBy: 'abTestUser', targetEntity: AbTestVote::class, orphanRemoval: true)]
    private Collection $abTestVotes;

    #[ORM\Column]
    private ?bool $refused = false;

    public function __construct()
    {
        $this->abTestVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAbTest(): ?AbTest
    {
        return $this->abTest;
    }

    public function setAbTest(?AbTest $abTest): static
    {
        $this->abTest = $abTest;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getVariation(): ?string
    {
        return $this->variation;
    }

    public function setVariation(string $variation): static
    {
        $this->variation = $variation;

        return $this;
    }

    public function getCookieId(): ?string
    {
        return $this->cookieId;
    }

    public function setCookieId(string $cookieId): static
    {
        $this->cookieId = $cookieId;

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
            $abTestVote->setAbTestUser($this);
        }

        return $this;
    }

    public function removeAbTestVote(AbTestVote $abTestVote): static
    {
        if ($this->abTestVotes->removeElement($abTestVote)) {
            // set the owning side to null (unless already changed)
            if ($abTestVote->getAbTestUser() === $this) {
                $abTestVote->setAbTestUser(null);
            }
        }

        return $this;
    }

    public function isRefused(): ?bool
    {
        return $this->refused;
    }

    public function setRefused(bool $refused): static
    {
        $this->refused = $refused;

        return $this;
    }
}
