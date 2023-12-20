<?php

namespace App\Entity\Log;

use App\Entity\Aid\Aid;
use App\Entity\Eligibility\EligibilityTest;
use App\Repository\Log\LogAidEligibilityTestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LogAidEligibilityTestRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_laet')]
#[ORM\Index(columns: ['source'], name: 'source_laet')]
#[ORM\Index(columns: ['answer_success'], name: 'answer_success_laet')]
class LogAidEligibilityTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $answerSuccess = null;

    #[ORM\Column(nullable: true)]
    private ?array $answerDetails = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $querystring = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\ManyToOne(inversedBy: 'logAidEligibilityTests')]
    private ?Aid $aid = null;

    #[ORM\ManyToOne(inversedBy: 'logAidEligibilityTests')]
    private ?EligibilityTest $eligibilityTest = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isAnswerSuccess(): ?bool
    {
        return $this->answerSuccess;
    }

    public function setAnswerSuccess(bool $answerSuccess): static
    {
        $this->answerSuccess = $answerSuccess;

        return $this;
    }

    public function getAnswerDetails(): ?array
    {
        return $this->answerDetails;
    }

    public function setAnswerDetails(?array $answerDetails): static
    {
        $this->answerDetails = $answerDetails;

        return $this;
    }

    public function getQuerystring(): ?string
    {
        return $this->querystring;
    }

    public function setQuerystring(?string $querystring): static
    {
        $this->querystring = $querystring;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

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

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->dateCreate;
    }

    public function setDateCreate(\DateTimeInterface $dateCreate): static
    {
        $this->dateCreate = $dateCreate;

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
