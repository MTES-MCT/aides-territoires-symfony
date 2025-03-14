<?php

namespace App\Entity\Site;

use App\Entity\Aid\Aid;
use App\Repository\Site\AbTestVoteRepository;
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AbTestVoteRepository::class)]
#[ORM\Index(columns: ['variation'], name: 'variation_ab_test_vote')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_ab_test_vote')]
#[ORM\Index(columns: ['php_session_id'], name: 'php_session_id_ab_test_vote')]
class AbTestVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abTestVotes')]
    #[JoinColumn(nullable: false)]
    private ?AbTest $abTest = null;

    #[ORM\Column]
    private ?int $vote = null;

    #[ORM\Column(length: 255)]
    private ?string $phpSessionId = null;

    #[ORM\Column(length: 255)]
    private ?string $variation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $data = null;

    #[ORM\ManyToOne]
    #[JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Aid $aid = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'abTestVotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AbTestUser $abTestUser = null;

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

    public function getVote(): ?int
    {
        return $this->vote;
    }

    public function setVote(int $vote): static
    {
        $this->vote = $vote;

        return $this;
    }

    public function getPhpSessionId(): ?string
    {
        return $this->phpSessionId;
    }

    public function setPhpSessionId(string $phpSessionId): static
    {
        $this->phpSessionId = $phpSessionId;

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

    public function getVariation(): ?string
    {
        return $this->variation;
    }

    public function setVariation(string $variation): static
    {
        $this->variation = $variation;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getAbTestUser(): ?AbTestUser
    {
        return $this->abTestUser;
    }

    public function setAbTestUser(?AbTestUser $abTestUser): static
    {
        $this->abTestUser = $abTestUser;

        return $this;
    }
}
