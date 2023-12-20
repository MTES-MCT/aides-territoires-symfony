<?php

namespace App\Entity\Eligibility;

use App\Repository\Eligibility\EligibilityTestQuestionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

// TODO voir utilite, faire BO si besoin
#[ORM\Entity(repositoryClass: EligibilityTestQuestionRepository::class)]
class EligibilityTestQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    // #[Gedmo\SortablePosition]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'eligibilityTestQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EligibilityQuestion $eligibilityQuestion = null;

    #[ORM\ManyToOne(inversedBy: 'eligibilityTestQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EligibilityTest $eligibilityTest = null;

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

    public function getEligibilityQuestion(): ?EligibilityQuestion
    {
        return $this->eligibilityQuestion;
    }

    public function setEligibilityQuestion(?EligibilityQuestion $eligibilityQuestion): static
    {
        $this->eligibilityQuestion = $eligibilityQuestion;

        return $this;
    }

    public function getEligibilityTest(): ?EligibilityTest
    {
        return $this->eligibilityTest;
    }

    public function setEligibilityTest(?EligibilityTest $eligibilityTest): static
    {
        $this->eligibilityTest = $eligibilityTest;

        return $this;
    }
}
