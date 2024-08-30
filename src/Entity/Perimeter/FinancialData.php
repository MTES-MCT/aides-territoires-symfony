<?php

namespace App\Entity\Perimeter;

use App\Repository\Perimeter\FinancialDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FinancialDataRepository::class)]
class FinancialData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 5)]
    #[ORM\Column(length: 5)]
    private ?string $inseeCode = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[ORM\Column]
    private ?int $year = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[ORM\Column]
    private ?int $populationStrata = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $aggregate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $mainBudgetAmount = null;

    #[ORM\Column(nullable: true)]
    private ?int $displayOrder = null;

    #[ORM\ManyToOne(inversedBy: 'financialData')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Perimeter $perimeter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInseeCode(): ?string
    {
        return $this->inseeCode;
    }

    public function setInseeCode(string $inseeCode): static
    {
        $this->inseeCode = $inseeCode;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getPopulationStrata(): ?int
    {
        return $this->populationStrata;
    }

    public function setPopulationStrata(int $populationStrata): static
    {
        $this->populationStrata = $populationStrata;

        return $this;
    }

    public function getAggregate(): ?string
    {
        return $this->aggregate;
    }

    public function setAggregate(string $aggregate): static
    {
        $this->aggregate = $aggregate;

        return $this;
    }

    public function getMainBudgetAmount(): ?string
    {
        return $this->mainBudgetAmount;
    }

    public function setMainBudgetAmount(string $mainBudgetAmount): static
    {
        $this->mainBudgetAmount = $mainBudgetAmount;

        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(?int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

        return $this;
    }
}
