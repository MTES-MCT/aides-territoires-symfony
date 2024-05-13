<?php

namespace App\Entity\Search;

use App\Entity\User\User;
use App\Repository\Search\SearchPageLockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: SearchPageLockRepository::class)]
class SearchPageLock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'searchPageLocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SearchPage $searchPage = null;

    #[ORM\ManyToOne(inversedBy: 'searchPageLocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timeStart = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSearchPage(): ?SearchPage
    {
        return $this->searchPage;
    }

    public function setSearchPage(?SearchPage $searchPage): static
    {
        $this->searchPage = $searchPage;

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

    public function getTimeStart(): ?\DateTimeInterface
    {
        return $this->timeStart;
    }

    public function setTimeStart(\DateTimeInterface $timeStart): static
    {
        $this->timeStart = $timeStart;

        return $this;
    }
}
