<?php

namespace App\Entity\User;

use App\Entity\Aid\Aid;
use App\Repository\User\FavoriteAidRepository;
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
}
