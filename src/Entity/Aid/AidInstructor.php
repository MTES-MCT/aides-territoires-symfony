<?php

namespace App\Entity\Aid;

use App\Entity\Backer\Backer;
use App\Repository\Aid\AidInstructorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AidInstructorRepository::class)]
class AidInstructor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'aidInstructors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aid $aid = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\ManyToOne(inversedBy: 'aidInstructors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Backer $backer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    public function getBacker(): ?Backer
    {
        return $this->backer;
    }

    public function setBacker(?Backer $backer): static
    {
        $this->backer = $backer;

        return $this;
    }

    public function __toString(): string
    {
        $name = '';
        if ($this->getBacker()) {
            $name .= $this->getBacker()->getName();
        }

        if ($name == '') {
            $name = 'AidInstructor';
        }

        return $name;
    }
}
