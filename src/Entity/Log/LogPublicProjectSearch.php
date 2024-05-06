<?php

namespace App\Entity\Log;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Organization\Organization;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Repository\Log\LogPublicProjectSearchRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogPublicProjectSearchRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_lpps')]
class LogPublicProjectSearch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[ORM\Column]
    private ?int $resultsCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectSearches')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectSearches')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?Perimeter $perimeter = null;

    #[ORM\ManyToOne(inversedBy: 'logPublicProjectSearches')]
    #[ORM\JoinColumn(onDelete:DoctrineConstants::SET_NULL)]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: KeywordSynonymlist::class, inversedBy: 'logPublicProjectSearches')]
    private Collection $keywordSynonymlists;

    public function __construct()
    {
        $this->keywordSynonymlists = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuerystring(): ?string
    {
        return $this->querystring;
    }

    public function setQuerystring(?string $querystring): static
    {
        $this->querystring = $querystring;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

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

    public function getResultsCount(): ?int
    {
        return $this->resultsCount;
    }

    public function setResultsCount(int $resultsCount): static
    {
        $this->resultsCount = $resultsCount;

        return $this;
    }

    /**
     * @return Collection<int, KeywordSynonymlist>
     */
    public function getKeywordSynonymlists(): Collection
    {
        return $this->keywordSynonymlists;
    }

    public function addKeywordSynonymlist(KeywordSynonymlist $keywordSynonymlist): static
    {
        if (!$this->keywordSynonymlists->contains($keywordSynonymlist)) {
            $this->keywordSynonymlists->add($keywordSynonymlist);
        }

        return $this;
    }

    public function removeKeywordSynonymlist(KeywordSynonymlist $keywordSynonymlist): static
    {
        $this->keywordSynonymlists->removeElement($keywordSynonymlist);

        return $this;
    }
}
