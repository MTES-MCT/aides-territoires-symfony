<?php

namespace App\Entity\Eligibility;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidEligibilityTest;
use App\Entity\User\User;
use App\Repository\Eligibility\EligibilityTestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

// TODO voir utilite, faire BO si besoin
#[ORM\Entity(repositoryClass: EligibilityTestRepository::class)]
class EligibilityTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introduction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conclusion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'eligibilityTests')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conclusionFailure = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conclusionSuccess = null;

    #[ORM\OneToMany(mappedBy: 'eligibilityTest', targetEntity: EligibilityTestQuestion::class, orphanRemoval: true)]
    private Collection $eligibilityTestQuestions;

    #[ORM\OneToMany(mappedBy: 'eligibilityTest', targetEntity: Aid::class)]
    private Collection $aids;

    #[ORM\OneToMany(mappedBy: 'eligibilityTest', targetEntity: LogAidEligibilityTest::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidEligibilityTests;

    public function __construct()
    {
        $this->eligibilityTestQuestions = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->logAidEligibilityTests = new ArrayCollection();
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

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): static
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getConclusion(): ?string
    {
        return $this->conclusion;
    }

    public function setConclusion(?string $conclusion): static
    {
        $this->conclusion = $conclusion;

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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getConclusionFailure(): ?string
    {
        return $this->conclusionFailure;
    }

    public function setConclusionFailure(?string $conclusionFailure): static
    {
        $this->conclusionFailure = $conclusionFailure;

        return $this;
    }

    public function getConclusionSuccess(): ?string
    {
        return $this->conclusionSuccess;
    }

    public function setConclusionSuccess(?string $conclusionSuccess): static
    {
        $this->conclusionSuccess = $conclusionSuccess;

        return $this;
    }

    /**
     * @return Collection<int, EligibilityTestQuestion>
     */
    public function getEligibilityTestQuestions(): Collection
    {
        return $this->eligibilityTestQuestions;
    }

    public function addEligibilityTestQuestion(EligibilityTestQuestion $eligibilityTestQuestion): static
    {
        if (!$this->eligibilityTestQuestions->contains($eligibilityTestQuestion)) {
            $this->eligibilityTestQuestions->add($eligibilityTestQuestion);
            $eligibilityTestQuestion->setEligibilityTest($this);
        }

        return $this;
    }

    public function removeEligibilityTestQuestion(EligibilityTestQuestion $eligibilityTestQuestion): static
    {
        if ($this->eligibilityTestQuestions->removeElement($eligibilityTestQuestion)) {
            // set the owning side to null (unless already changed)
            if ($eligibilityTestQuestion->getEligibilityTest() === $this) {
                $eligibilityTestQuestion->setEligibilityTest(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Aid>
     */
    public function getAids(): Collection
    {
        return $this->aids;
    }

    public function addAid(Aid $aid): static
    {
        if (!$this->aids->contains($aid)) {
            $this->aids->add($aid);
            $aid->setEligibilityTest($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            // set the owning side to null (unless already changed)
            if ($aid->getEligibilityTest() === $this) {
                $aid->setEligibilityTest(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidEligibilityTest>
     */
    public function getLogAidEligibilityTests(): Collection
    {
        return $this->logAidEligibilityTests;
    }

    public function addLogAidEligibilityTest(LogAidEligibilityTest $logAidEligibilityTest): static
    {
        if (!$this->logAidEligibilityTests->contains($logAidEligibilityTest)) {
            $this->logAidEligibilityTests->add($logAidEligibilityTest);
            $logAidEligibilityTest->setEligibilityTest($this);
        }

        return $this;
    }

    public function removeLogAidEligibilityTest(LogAidEligibilityTest $logAidEligibilityTest): static
    {
        if ($this->logAidEligibilityTests->removeElement($logAidEligibilityTest)) {
            // set the owning side to null (unless already changed)
            if ($logAidEligibilityTest->getEligibilityTest() === $this) {
                $logAidEligibilityTest->setEligibilityTest(null);
            }
        }

        return $this;
    }


    public function  __toString(): string
    {
        return $this->name ?? 'EligibilityTest';   
    }
}
