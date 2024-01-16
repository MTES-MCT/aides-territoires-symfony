<?php

namespace App\Service\Aid;

use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Backer\Backer;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use Doctrine\Common\Collections\ArrayCollection;

class AidSearchClass
{
    private ?OrganizationType $organizationType;
    private ?Perimeter $searchPerimeter;
    private ?string $keyword;
        /**
     * @var ?ArrayCollection|Category[]
     */
    private $categorysearch;
    private bool $newIntegration;
    private ?string $orderBy;
    /**
     * @var ?ArrayCollection|AidType[]
     */
    private $aidTypes;
    
    /**
     * @var ?ArrayCollection|AidType[]
     */
    private $backers;
    private ?\DateTime $applyBefore;
    /**
     * @var ?ArrayCollection|Program[]
     */
    private $programs;
    /**
     * @var ?ArrayCollection|AidStep[]
     */
    private $aidSteps;
        /**
     * @var ?ArrayCollection|AidDestination[]
     */
    private $aidDestinations;
    private ?bool $isCharged;
    private ?string $europeanAid;
    private ?bool $isCallForProject;

    public function  __construct()
    {
        $this->organizationType = null;
        $this->searchPerimeter = null;
        $this->keyword = null;
        $this->categorysearch = null;
        $this->aidTypes = null;
        $this->orderBy = null;
        $this->backers = null;
        $this->applyBefore = null;
        $this->programs = null;
        $this->aidSteps = null;
        $this->aidDestinations = null;
        $this->isCharged = null;
        $this->europeanAid = null;
        $this->isCallForProject = null;
    }

    public function getOrganizationType(): ?OrganizationType
    {
        return $this->organizationType;
    }

    public function setOrganizationType(?OrganizationType $organizationType): void
    {
        $this->organizationType = $organizationType;
    }

    public function getSearchPerimeter(): ?Perimeter
    {
        return $this->searchPerimeter;
    }

    public function setSearchPerimeter(?Perimeter $searchPerimeter): void
    {
        $this->searchPerimeter = $searchPerimeter;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getCategorySearch(): ?ArrayCollection
    {
        return $this->categorysearch;
    }

    public function setCategorySearch(?ArrayCollection $categories): void
    {
        $this->categorysearch = $categories;
    }

    public function addCategorySearch(Category $category): void
    {
        if (!$this->categorysearch) {
            $this->categorysearch = new ArrayCollection();
        }
        if (!$this->categorysearch->contains($category)) {
            $this->categorysearch->add($category);
        }
    }

    public function isNewIntegration(): bool
    {
        return $this->newIntegration;
    }

    public function setNewIntegration(bool $newIntegration): void
    {
        $this->newIntegration = $newIntegration;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(?string $orderBy): void
    {
        $this->orderBy = $orderBy;
    }

    public function getAidTypes(): ?ArrayCollection
    {
        return $this->aidTypes;
    }

    public function setAidTypes(?ArrayCollection $aidTypes): void
    {
        $this->aidTypes = $aidTypes;
    }

    public function addAidType(AidType $aidType): void
    {
        if (!$this->aidTypes) {
            $this->aidTypes = new ArrayCollection();
        }
        if (!$this->aidTypes->contains($aidType)) {
            $this->aidTypes->add($aidType);
        }
    }

    public function getBackers(): ?ArrayCollection
    {
        return $this->backers;
    }

    public function setBackers(?ArrayCollection $backers): void
    {
        $this->backers = $backers;
    }

    public function addBacker(Backer $backer): void
    {
        if (!$this->backers) {
            $this->backers = new ArrayCollection();
        }
        if (!$this->backers->contains($backer)) {
            $this->backers->add($backer);
        }
    }

    public function getApplyBefore(): ?\DateTime
    {
        return $this->applyBefore;
    }

    public function setApplyBefore(?\DateTime $applyBefore): void
    {
        $this->applyBefore = $applyBefore;
    }

    public function getPrograms(): ?ArrayCollection
    {
        return $this->programs;
    }

    public function setPrograms(?ArrayCollection $programs): void
    {
        $this->programs = $programs;
    }

    public function addProgram(Program $program): void
    {
        if (!$this->programs) {
            $this->programs = new ArrayCollection();
        }
        if (!$this->programs->contains($program)) {
            $this->programs->add($program);
        }
    }

    public function getAidSteps(): ?ArrayCollection
    {
        return $this->aidSteps;
    }

    public function setAidSteps(?ArrayCollection $aidSteps): void
    {
        $this->aidSteps = $aidSteps;
    }

    public function addAidStep(AidStep $aidStep): void
    {
        if (!$this->aidSteps) {
            $this->aidSteps = new ArrayCollection();
        }
        if (!$this->aidSteps->contains($aidStep)) {
            $this->aidSteps->add($aidStep);
        }
    }

    public function getAidDestinations(): ?ArrayCollection
    {
        return $this->aidDestinations;
    }

    public function setAidDestinations(?ArrayCollection $aidDestinations): void
    {
        $this->aidDestinations = $aidDestinations;
    }

    public function addAidDestination($aidDestination): void
    {
        if (!$this->aidDestinations) {
            $this->aidDestinations = new ArrayCollection();
        }
        if (!$this->aidDestinations->contains($aidDestination)) {
            $this->aidDestinations->add($aidDestination);
        }
    }

    public function getIsCharged(): ?bool
    {
        return $this->isCharged;
    }

    public function setIsCharged(?bool $isCharged): void
    {
        $this->isCharged = $isCharged;
    }

    public function getEuropeanAid(): ?string
    {
        return $this->europeanAid;
    }

    public function setEuropeanAid(?string $europeanAid): void
    {
        $this->europeanAid = $europeanAid;
    }
    
    public function getIsCallForProject(): ?bool
    {
        return $this->isCallForProject;
    }

    public function setIsCallForProject(?bool $isCallForProject): void
    {
        $this->isCallForProject = $isCallForProject;
    }
}