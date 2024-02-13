<?php

namespace App\Entity;

use App\Repository\LexikMaintenanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/***
 * Entite créer uniquement pour gérer le problème de clé primaire sur Scalingo
 */
#[ORM\Entity(repositoryClass: LexikMaintenanceRepository::class)]
class LexikMaintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ttl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTtl(): ?\DateTimeInterface
    {
        return $this->ttl;
    }

    public function setTtl(?\DateTimeInterface $ttl): static
    {
        $this->ttl = $ttl;

        return $this;
    }
}
