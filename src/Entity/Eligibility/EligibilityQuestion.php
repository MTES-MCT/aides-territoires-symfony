<?php

namespace App\Entity\Eligibility;

use App\Entity\User\User;
use App\Repository\Eligibility\EligibilityQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: EligibilityQuestionRepository::class)]
class EligibilityQuestion // NOSONAR too much methods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $answerChoiceA = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $answerChoiceB = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $answerChoiceC = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $answerChoiceD = null;

    #[ORM\Column(length: 50)]
    private ?string $answerCorrect = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'eligibilityQuestions')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\OneToMany(mappedBy: 'eligibilityQuestion', targetEntity: EligibilityTestQuestion::class, orphanRemoval: true)]
    private Collection $eligibilityTestQuestions;

    public function __construct()
    {
        $this->eligibilityTestQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAnswerChoiceA(): ?string
    {
        return $this->answerChoiceA;
    }

    public function setAnswerChoiceA(string $answerChoiceA): static
    {
        $this->answerChoiceA = $answerChoiceA;

        return $this;
    }

    public function getAnswerChoiceB(): ?string
    {
        return $this->answerChoiceB;
    }

    public function setAnswerChoiceB(?string $answerChoiceB): static
    {
        $this->answerChoiceB = $answerChoiceB;

        return $this;
    }

    public function getAnswerChoiceC(): ?string
    {
        return $this->answerChoiceC;
    }

    public function setAnswerChoiceC(?string $answerChoiceC): static
    {
        $this->answerChoiceC = $answerChoiceC;

        return $this;
    }

    public function getAnswerChoiceD(): ?string
    {
        return $this->answerChoiceD;
    }

    public function setAnswerChoiceD(?string $answerChoiceD): static
    {
        $this->answerChoiceD = $answerChoiceD;

        return $this;
    }

    public function getAnswerCorrect(): ?string
    {
        return $this->answerCorrect;
    }

    public function setAnswerCorrect(string $answerCorrect): static
    {
        $this->answerCorrect = $answerCorrect;

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
            $eligibilityTestQuestion->setEligibilityQuestion($this);
        }

        return $this;
    }

    public function removeEligibilityTestQuestion(EligibilityTestQuestion $eligibilityTestQuestion): static
    {
        if ($this->eligibilityTestQuestions->removeElement($eligibilityTestQuestion) && $eligibilityTestQuestion->getEligibilityQuestion() === $this) {
            $eligibilityTestQuestion->setEligibilityQuestion(null);
        }

        return $this;
    }
}
