<?php

namespace App\Entity\Reference;

use App\Entity\Aid\Aid;
use App\Repository\Reference\KeywordReferenceSuggestedRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordReferenceSuggestedRepository::class)]
class KeywordReferenceSuggested
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'keywordReferenceSuggesteds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?KeywordReference $keywordReference = null;

    #[ORM\ManyToOne(inversedBy: 'keywordReferenceSuggesteds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aid $aid = null;

    #[ORM\Column]
    private ?int $occurence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeywordReference(): ?KeywordReference
    {
        return $this->keywordReference;
    }

    public function setKeywordReference(?KeywordReference $keywordReference): static
    {
        $this->keywordReference = $keywordReference;

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

    public function getOccurence(): ?int
    {
        return $this->occurence;
    }

    public function setOccurence(int $occurence): static
    {
        $this->occurence = $occurence;

        return $this;
    }
}
