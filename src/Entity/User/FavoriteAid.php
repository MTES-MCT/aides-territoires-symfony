<?php

namespace App\Entity\User;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidSearchTemp;
use App\Repository\User\FavoriteAidRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteAidRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_favorite_aid')]
class FavoriteAid
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favoriteAids')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'favoriteAids')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aid $aid = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?LogAidSearch $logAidSearch = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?LogAidSearchTemp $logAidSearchTemp = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAid(): ?Aid
    {
        return $this->aid;
    }

    public function setAid(?Aid $aid): static
    {
        $this->aid = $aid;

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

    public function getLogAidSearch(): ?LogAidSearch
    {
        return $this->logAidSearch;
    }

    public function setLogAidSearch(?LogAidSearch $logAidSearch): static
    {
        $this->logAidSearch = $logAidSearch;

        return $this;
    }

    public function getLogAidSearchTemp(): ?LogAidSearchTemp
    {
        return $this->logAidSearchTemp;
    }

    public function setLogAidSearchTemp(?LogAidSearchTemp $logAidSearchTemp): static
    {
        $this->logAidSearchTemp = $logAidSearchTemp;

        return $this;
    }
}
