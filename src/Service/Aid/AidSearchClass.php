<?php

namespace App\Service\Aid;

use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerGroup;
use App\Entity\Category\Category;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\ProjectReference;
use Doctrine\Common\Collections\ArrayCollection;

class AidSearchClass // NOSONAR too much methods
{
    private ?OrganizationType $organization_type_slug;
    /**
     * @var ?ArrayCollection|OrganizationType[]
     */
    private ?ArrayCollection $audiences;
    private ?Perimeter $perimeter_id;
    private ?string $keyword;
    /**
     * @var ?ArrayCollection|Category[]
     */
    private $category_ids;
    private ?bool $newIntegration;
    private ?string $orderBy;
    /**
     * @var ?ArrayCollection|AidType[]
     */
    private $aid_type_ids;

    /**
     * @var ?ArrayCollection|Backer[]
     */
    private $backer_ids;
    private ?BackerGroup $backer_group_id;

    private ?\DateTime $apply_before;
    private ?\DateTime $published_after;

    /**
     * @var ?ArrayCollection|Program[]
     */
    private $programs;
    /**
     * @var ?ArrayCollection|AidStep[]
     */
    private $aid_step_ids;
    /**
     * @var ?ArrayCollection|AidDestination[]
     */
    private $aid_destination_ids;
    private ?bool $is_charged;
    private ?string $european_aid_slug;
    private ?bool $call_for_projects_only;

    private ?ProjectReference $projectReference;
    private ?AidRecurrence $aidRecurrence;

    public function __construct()
    {
        $this->organization_type_slug = null;
        $this->audiences = null;
        $this->perimeter_id = null;
        $this->keyword = null;
        $this->category_ids = null;
        $this->newIntegration = null;
        $this->aid_type_ids = null;
        $this->orderBy = null;
        $this->backer_ids = null;
        $this->backer_group_id = null;
        $this->apply_before = null;
        $this->published_after = null;
        $this->programs = null;
        $this->aid_step_ids = null;
        $this->aid_destination_ids = null;
        $this->is_charged = null;
        $this->european_aid_slug = null;
        $this->call_for_projects_only = null;
        $this->projectReference = null;
        $this->aidRecurrence = null;
    }

    public function getOrganizationTypeSlug(): ?OrganizationType
    {
        return $this->organization_type_slug;
    }

    public function setOrganizationTypeSlug(?OrganizationType $organizationType): void
    {
        $this->organization_type_slug = $organizationType;
    }

    public function getAudiences(): ?ArrayCollection
    {
        return $this->audiences;
    }

    public function setAudiences(?ArrayCollection $audiences): void
    {
        $this->audiences = $audiences;
    }

    public function addAudience(OrganizationType $audience): void
    {
        if (!$this->audiences) {
            $this->audiences = new ArrayCollection();
        }
        if (!$this->audiences->contains($audience)) {
            $this->audiences->add($audience);
        }
    }

    public function getPerimeterId(): ?Perimeter
    {
        return $this->perimeter_id;
    }

    public function setPerimeterId(?Perimeter $perimeter): void
    {
        $this->perimeter_id = $perimeter;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getCategoryIds(): ?ArrayCollection
    {
        return $this->category_ids;
    }

    public function setCategoryIds(?ArrayCollection $categoryIds): void
    {
        $this->category_ids = $categoryIds;
    }

    public function addCategoryId(Category $category): void
    {
        if (!$this->category_ids) {
            $this->category_ids = new ArrayCollection();
        }
        if (!$this->category_ids->contains($category)) {
            $this->category_ids->add($category);
        }
    }

    public function isNewIntegration(): ?bool
    {
        return $this->newIntegration;
    }

    public function setNewIntegration(?bool $newIntegration): void
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

    public function getAidTypeIds(): ?ArrayCollection
    {
        return $this->aid_type_ids;
    }

    public function setAidTypeIds(?ArrayCollection $aidTypeIds): void
    {
        $this->aid_type_ids = $aidTypeIds;
    }

    public function addAidTypeId(AidType $aidType): void
    {
        if (!$this->aid_type_ids) {
            $this->aid_type_ids = new ArrayCollection();
        }
        if (!$this->aid_type_ids->contains($aidType)) {
            $this->aid_type_ids->add($aidType);
        }
    }

    public function getBackerIds(): ?ArrayCollection
    {
        return $this->backer_ids;
    }
    
    public function setBackerIds(?ArrayCollection $backerIds): void
    {
        $this->backer_ids = $backerIds;
    }

    public function addBackerId(Backer $backer): void
    {
        if (!$this->backer_ids) {
            $this->backer_ids = new ArrayCollection();
        }
        if (!$this->backer_ids->contains($backer)) {
            $this->backer_ids->add($backer);
        }
    }

    public function getBackerGroupId(): ?BackerGroup
    {
        return $this->backer_group_id;
    }

    public function setBackerGroupId(?BackerGroup $backerGroup): void
    {
        $this->backer_group_id = $backerGroup;
    }

    public function getApplyBefore(): ?\DateTime
    {
        return $this->apply_before;
    }

    public function setApplyBefore(?\DateTime $applyBefore): void
    {
        $this->apply_before = $applyBefore;
    }

    public function getPublishedAfter(): ?\DateTime
    {
        return $this->published_after;
    }

    public function setPublishedAfter(?\DateTime $publishedAfter): void
    {
        $this->published_after = $publishedAfter;
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

    public function getAidStepIds(): ?ArrayCollection
    {
        return $this->aid_step_ids;
    }

    public function setAidStepIds(?ArrayCollection $aidStepIds): void
    {
        $this->aid_step_ids = $aidStepIds;
    }

    public function addAidStepId(AidStep $aidStep): void
    {
        if (!$this->aid_step_ids) {
            $this->aid_step_ids = new ArrayCollection();
        }
        if (!$this->aid_step_ids->contains($aidStep)) {
            $this->aid_step_ids->add($aidStep);
        }
    }

    public function getAidDestinationIds(): ?ArrayCollection
    {
        return $this->aid_destination_ids;
    }

    public function setAidDestinationIds(?ArrayCollection $aidDestinationIds): void
    {
        $this->aid_destination_ids = $aidDestinationIds;
    }

    public function addAidDestinationId(AidDestination $aidDestination): void
    {
        if (!$this->aid_destination_ids) {
            $this->aid_destination_ids = new ArrayCollection();
        }
        if (!$this->aid_destination_ids->contains($aidDestination)) {
            $this->aid_destination_ids->add($aidDestination);
        }
    }

    public function getIsCharged(): ?bool
    {
        return $this->is_charged;
    }

    public function setIsCharged(?bool $isCharged): void
    {
        $this->is_charged = $isCharged;
    }

    public function getEuropeanAidSlug(): ?string
    {
        return $this->european_aid_slug;
    }

    public function setEuropeanAidSlug(?string $europeanAidSlug): void
    {
        $this->european_aid_slug = $europeanAidSlug;
    }

    public function getCallForProjectsOnly(): ?bool
    {
        return $this->call_for_projects_only;
    }

    public function setCallForProjectsOnly(?bool $callForProjectsOnly): void
    {
        $this->call_for_projects_only = $callForProjectsOnly;
    }

    public function getProjectReference(): ?ProjectReference
    {
        return $this->projectReference;
    }

    public function setProjectReference(?ProjectReference $projectReference): void
    {
        $this->projectReference = $projectReference;
    }

    public function getAidRecurrence(): ?AidRecurrence
    {
        return $this->aidRecurrence;
    }

    public function setAidRecurrence(?AidRecurrence $aidRecurrence): void
    {
        $this->aidRecurrence = $aidRecurrence;
    }
}
