<?php

namespace App\Entity\Organization;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Directory\Directory;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
use App\Entity\Log\LogBackerView;
use App\Entity\Log\LogBlogPostView;
use App\Entity\Log\LogProgramView;
use App\Entity\Log\LogProjectValidatedSearch;
use App\Entity\Log\LogPublicProjectSearch;
use App\Entity\Log\LogPublicProjectView;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectValidated;
use App\Entity\User\User;
use App\Repository\Organization\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Index(columns: ['is_imported'], name: 'organization_is_imported')]
#[ORM\Index(columns: ['intercommunality_type'], name: 'intercommunality_type_organization')]
class Organization
{
    const INTERCOMMUNALITY_TYPES = [
        ['slug' => 'CC', 'name' => 'Communauté de communes (CC)'],
        ['slug' => 'CA', 'name' => 'Communauté d’agglomération (CA)'],
        ['slug' => 'CU', 'name' => 'Communauté urbaine (CU)'],
        ['slug' => 'METRO', 'name' => 'Métropole'],
        ['slug' => 'GAL', 'name' => 'Groupe d’action locale (GAL)'],
        ['slug' => 'PNR', 'name' => 'Parc naturel régional (PNR)'],
        ['slug' => 'PETR', 'name' => 'Pays et pôles d’équilibre territorial et rural (PETR)'],
        ['slug' => 'SM', 'name' => 'Syndicat mixte et syndicat de commune'],
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'organizations')]
    private ?OrganizationType $organizationType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cityName = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $sirenCode = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siretCode = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $apeCode = null;

    #[ORM\Column(nullable: true)]
    private ?int $inhabitantsNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $votersNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $corporatesNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $associationsNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $municipalRoads = null;

    #[ORM\Column(nullable: true)]
    private ?int $departmentalRoads = null;

    #[ORM\Column(nullable: true)]
    private ?int $tramRoads = null;

    #[ORM\Column(nullable: true)]
    private ?int $lamppostNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $libraryNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $medialibraryNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $theaterNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $museumNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $kindergartenNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $primarySchoolNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $middleSchoolNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $highSchoolNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $universityNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $swimmingPoolNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $placeOfWorshipNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $cemeteryNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'organizations')]
    private ?Perimeter $perimeter = null;

    #[ORM\ManyToOne(inversedBy: 'organizationDepartments')]
    private ?Perimeter $perimeterDepartment = null;

    #[ORM\ManyToOne(inversedBy: 'organizationRegions')]
    private ?Perimeter $perimeterRegion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $importedTime = null;

    #[ORM\Column]
    private ?bool $isImported = false;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $intercommunalityType = null;

    #[ORM\Column(nullable: true)]
    private ?int $bridgeNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $cinemaNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $coveredSportingComplexNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $footballFieldNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $forestNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $nurseryNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $otherOutsideStructureNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $protectedMonumentNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $recCenterNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $runningTrackNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $shopsNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $tennisCourtNumber = null;

    #[ORM\ManyToOne(inversedBy: 'organizations')]
    private ?Backer $backer = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $densityTypology = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $inseeCode = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $populationStrata = null;

    #[ORM\ManyToMany(targetEntity: Project::class, fetch: 'LAZY')]
    private Collection $favoriteProjects;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Project::class)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'organizations')]
    private Collection $beneficiairies;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Directory::class, orphanRemoval: true)]
    private Collection $directories;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: OrganizationInvitation::class, orphanRemoval: true)]
    private Collection $organizationInvitations;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Aid::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $aids;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidViews;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidCreatedsFolder::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidCreatedsFolders;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidSearch::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidSearches;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogBackerView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logBackerViews;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogBlogPostView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logBlogPostViews;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogProgramView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logProgramViews;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogPublicProjectSearch::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logPublicProjectSearches;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogPublicProjectView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logPublicProjectViews;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogProjectValidatedSearch::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logProjectValidatedSearches;

    public function __construct()
    {
        $this->favoriteProjects = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->projectValidateds = new ArrayCollection();
        $this->beneficiairies = new ArrayCollection();
        $this->directories = new ArrayCollection();
        $this->logAidViews = new ArrayCollection();
        $this->logAidCreatedsFolders = new ArrayCollection();
        $this->logAidSearches = new ArrayCollection();
        $this->logBackerViews = new ArrayCollection();
        $this->logBlogPostViews = new ArrayCollection();
        $this->logProgramViews = new ArrayCollection();
        $this->logPublicProjectSearches = new ArrayCollection();
        $this->logPublicProjectViews = new ArrayCollection();
        $this->logProjectValidatedSearches = new ArrayCollection();
        $this->organizationInvitations = new ArrayCollection();
        $this->aids = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getOrganizationType(): ?OrganizationType
    {
        return $this->organizationType;
    }

    public function setOrganizationType(?OrganizationType $organizationType): static
    {
        $this->organizationType = $organizationType;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): static
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getSirenCode(): ?string
    {
        return $this->sirenCode;
    }

    public function setSirenCode(?string $sirenCode): static
    {
        $this->sirenCode = $sirenCode;

        return $this;
    }

    public function getSiretCode(): ?string
    {
        return $this->siretCode;
    }

    public function setSiretCode(?string $siretCode): static
    {
        $this->siretCode = $siretCode;

        return $this;
    }

    public function getApeCode(): ?string
    {
        return $this->apeCode;
    }

    public function setApeCode(?string $apeCode): static
    {
        $this->apeCode = $apeCode;

        return $this;
    }

    public function getInhabitantsNumber(): ?int
    {
        return $this->inhabitantsNumber;
    }

    public function setInhabitantsNumber(?int $inhabitantsNumber): static
    {
        $this->inhabitantsNumber = $inhabitantsNumber;

        return $this;
    }

    public function getVotersNumber(): ?int
    {
        return $this->votersNumber;
    }

    public function setVotersNumber(?int $votersNumber): static
    {
        $this->votersNumber = $votersNumber;

        return $this;
    }

    public function getCorporatesNumber(): ?int
    {
        return $this->corporatesNumber;
    }

    public function setCorporatesNumber(?int $corporatesNumber): static
    {
        $this->corporatesNumber = $corporatesNumber;

        return $this;
    }

    public function getAssociationsNumber(): ?int
    {
        return $this->associationsNumber;
    }

    public function setAssociationsNumber(?int $associationsNumber): static
    {
        $this->associationsNumber = $associationsNumber;

        return $this;
    }

    public function getMunicipalRoads(): ?int
    {
        return $this->municipalRoads;
    }

    public function setMunicipalRoads(?int $municipalRoads): static
    {
        $this->municipalRoads = $municipalRoads;

        return $this;
    }

    public function getDepartmentalRoads(): ?int
    {
        return $this->departmentalRoads;
    }

    public function setDepartmentalRoads(?int $departmentalRoads): static
    {
        $this->departmentalRoads = $departmentalRoads;

        return $this;
    }

    public function getTramRoads(): ?int
    {
        return $this->tramRoads;
    }

    public function setTramRoads(?int $tramRoads): static
    {
        $this->tramRoads = $tramRoads;

        return $this;
    }

    public function getLamppostNumber(): ?int
    {
        return $this->lamppostNumber;
    }

    public function setLamppostNumber(?int $lamppostNumber): static
    {
        $this->lamppostNumber = $lamppostNumber;

        return $this;
    }

    public function getLibraryNumber(): ?int
    {
        return $this->libraryNumber;
    }

    public function setLibraryNumber(?int $libraryNumber): static
    {
        $this->libraryNumber = $libraryNumber;

        return $this;
    }

    public function getMedialibraryNumber(): ?int
    {
        return $this->medialibraryNumber;
    }

    public function setMedialibraryNumber(?int $medialibraryNumber): static
    {
        $this->medialibraryNumber = $medialibraryNumber;

        return $this;
    }

    public function getTheaterNumber(): ?int
    {
        return $this->theaterNumber;
    }

    public function setTheaterNumber(?int $theaterNumber): static
    {
        $this->theaterNumber = $theaterNumber;

        return $this;
    }

    public function getMuseumNumber(): ?int
    {
        return $this->museumNumber;
    }

    public function setMuseumNumber(?int $museumNumber): static
    {
        $this->museumNumber = $museumNumber;

        return $this;
    }

    public function getKindergartenNumber(): ?int
    {
        return $this->kindergartenNumber;
    }

    public function setKindergartenNumber(?int $kindergartenNumber): static
    {
        $this->kindergartenNumber = $kindergartenNumber;

        return $this;
    }

    public function getPrimarySchoolNumber(): ?int
    {
        return $this->primarySchoolNumber;
    }

    public function setPrimarySchoolNumber(?int $primarySchoolNumber): static
    {
        $this->primarySchoolNumber = $primarySchoolNumber;

        return $this;
    }

    public function getMiddleSchoolNumber(): ?int
    {
        return $this->middleSchoolNumber;
    }

    public function setMiddleSchoolNumber(?int $middleSchoolNumber): static
    {
        $this->middleSchoolNumber = $middleSchoolNumber;

        return $this;
    }

    public function getHighSchoolNumber(): ?int
    {
        return $this->highSchoolNumber;
    }

    public function setHighSchoolNumber(?int $highSchoolNumber): static
    {
        $this->highSchoolNumber = $highSchoolNumber;

        return $this;
    }

    public function getUniversityNumber(): ?int
    {
        return $this->universityNumber;
    }

    public function setUniversityNumber(?int $universityNumber): static
    {
        $this->universityNumber = $universityNumber;

        return $this;
    }

    public function getSwimmingPoolNumber(): ?int
    {
        return $this->swimmingPoolNumber;
    }

    public function setSwimmingPoolNumber(?int $swimmingPoolNumber): static
    {
        $this->swimmingPoolNumber = $swimmingPoolNumber;

        return $this;
    }

    public function getPlaceOfWorshipNumber(): ?int
    {
        return $this->placeOfWorshipNumber;
    }

    public function setPlaceOfWorshipNumber(?int $placeOfWorshipNumber): static
    {
        $this->placeOfWorshipNumber = $placeOfWorshipNumber;

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

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

        return $this;
    }

    public function getPerimeterDepartment(): ?Perimeter
    {
        return $this->perimeterDepartment;
    }

    public function setPerimeterDepartment(?Perimeter $perimeterDepartment): static
    {
        $this->perimeterDepartment = $perimeterDepartment;

        return $this;
    }

    public function getPerimeterRegion(): ?Perimeter
    {
        return $this->perimeterRegion;
    }

    public function setPerimeterRegion(?Perimeter $perimeterRegion): static
    {
        $this->perimeterRegion = $perimeterRegion;

        return $this;
    }

    public function getImportedTime(): ?\DateTimeInterface
    {
        return $this->importedTime;
    }

    public function setImportedTime(?\DateTimeInterface $importedTime): static
    {
        $this->importedTime = $importedTime;

        return $this;
    }

    public function isIsImported(): ?bool
    {
        return $this->isImported;
    }

    public function setIsImported(bool $isImported): static
    {
        $this->isImported = $isImported;

        return $this;
    }

    public function getIntercommunalityType(): ?string
    {
        return $this->intercommunalityType;
    }

    public function setIntercommunalityType(?string $intercommunalityType): static
    {
        $this->intercommunalityType = $intercommunalityType;

        return $this;
    }

    public function getBridgeNumber(): ?int
    {
        return $this->bridgeNumber;
    }

    public function setBridgeNumber(?int $bridgeNumber): static
    {
        $this->bridgeNumber = $bridgeNumber;

        return $this;
    }

    public function getCinemaNumber(): ?int
    {
        return $this->cinemaNumber;
    }

    public function setCinemaNumber(?int $cinemaNumber): static
    {
        $this->cinemaNumber = $cinemaNumber;

        return $this;
    }

    public function getCoveredSportingComplexNumber(): ?int
    {
        return $this->coveredSportingComplexNumber;
    }

    public function setCoveredSportingComplexNumber(?int $coveredSportingComplexNumber): static
    {
        $this->coveredSportingComplexNumber = $coveredSportingComplexNumber;

        return $this;
    }

    public function getFootballFieldNumber(): ?int
    {
        return $this->footballFieldNumber;
    }

    public function setFootballFieldNumber(?int $footballFieldNumber): static
    {
        $this->footballFieldNumber = $footballFieldNumber;

        return $this;
    }

    public function getForestNumber(): ?int
    {
        return $this->forestNumber;
    }

    public function setForestNumber(?int $forestNumber): static
    {
        $this->forestNumber = $forestNumber;

        return $this;
    }

    public function getNurseryNumber(): ?int
    {
        return $this->nurseryNumber;
    }

    public function setNurseryNumber(?int $nurseryNumber): static
    {
        $this->nurseryNumber = $nurseryNumber;

        return $this;
    }

    public function getOtherOutsideStructureNumber(): ?int
    {
        return $this->otherOutsideStructureNumber;
    }

    public function setOtherOutsideStructureNumber(?int $otherOutsideStructureNumber): static
    {
        $this->otherOutsideStructureNumber = $otherOutsideStructureNumber;

        return $this;
    }

    public function getProtectedMonumentNumber(): ?int
    {
        return $this->protectedMonumentNumber;
    }

    public function setProtectedMonumentNumber(?int $protectedMonumentNumber): static
    {
        $this->protectedMonumentNumber = $protectedMonumentNumber;

        return $this;
    }

    public function getRecCenterNumber(): ?int
    {
        return $this->recCenterNumber;
    }

    public function setRecCenterNumber(?int $recCenterNumber): static
    {
        $this->recCenterNumber = $recCenterNumber;

        return $this;
    }

    public function getRunningTrackNumber(): ?int
    {
        return $this->runningTrackNumber;
    }

    public function setRunningTrackNumber(?int $runningTrackNumber): static
    {
        $this->runningTrackNumber = $runningTrackNumber;

        return $this;
    }

    public function getShopsNumber(): ?int
    {
        return $this->shopsNumber;
    }

    public function setShopsNumber(?int $shopsNumber): static
    {
        $this->shopsNumber = $shopsNumber;

        return $this;
    }

    public function getTennisCourtNumber(): ?int
    {
        return $this->tennisCourtNumber;
    }

    public function setTennisCourtNumber(?int $tennisCourtNumber): static
    {
        $this->tennisCourtNumber = $tennisCourtNumber;

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

    public function getDensityTypology(): ?string
    {
        return $this->densityTypology;
    }

    public function setDensityTypology(?string $densityTypology): static
    {
        $this->densityTypology = $densityTypology;

        return $this;
    }

    public function getInseeCode(): ?string
    {
        return $this->inseeCode;
    }

    public function setInseeCode(?string $inseeCode): static
    {
        $this->inseeCode = $inseeCode;

        return $this;
    }

    public function getPopulationStrata(): ?string
    {
        return $this->populationStrata;
    }

    public function setPopulationStrata(?string $populationStrata): static
    {
        $this->populationStrata = $populationStrata;

        return $this;
    }

    public function getCemeteryNumber(): ?int
    {
        return $this->cemeteryNumber;
    }

    public function setCemeteryNumber(?int $cemeteryNumber): static
    {
        $this->cemeteryNumber = $cemeteryNumber;

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

    /**
     * @return Collection<int, Project>
     */
    public function getFavoriteProjects(): Collection
    {
        return $this->favoriteProjects;
    }

    public function addFavoriteProject(Project $favoriteProject): static
    {
        if (!$this->favoriteProjects->contains($favoriteProject)) {
            $this->favoriteProjects->add($favoriteProject);
        }

        return $this;
    }

    public function removeFavoriteProject(Project $favoriteProject): static
    {
        $this->favoriteProjects->removeElement($favoriteProject);

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setOrganization($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getOrganization() === $this) {
                $project->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectValidated>
     */
    public function getProjectValidateds(): Collection
    {
        return $this->projectValidateds;
    }

    public function addProjectValidated(ProjectValidated $projectValidated): static
    {
        if (!$this->projectValidateds->contains($projectValidated)) {
            $this->projectValidateds->add($projectValidated);
            $projectValidated->setOrganization($this);
        }

        return $this;
    }

    public function removeProjectValidated(ProjectValidated $projectValidated): static
    {
        if ($this->projectValidateds->removeElement($projectValidated)) {
            // set the owning side to null (unless already changed)
            if ($projectValidated->getOrganization() === $this) {
                $projectValidated->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getBeneficiairies(): Collection
    {
        return $this->beneficiairies;
    }

    public function addBeneficiairy(User $beneficiairy): static
    {
        if (!$this->beneficiairies->contains($beneficiairy)) {
            $this->beneficiairies->add($beneficiairy);
        }

        return $this;
    }

    public function removeBeneficiairy(User $beneficiairy): static
    {
        $this->beneficiairies->removeElement($beneficiairy);

        return $this;
    }

    /**
     * @return Collection<int, Directory>
     */
    public function getDirectories(): Collection
    {
        return $this->directories;
    }

    public function addDirectory(Directory $directory): static
    {
        if (!$this->directories->contains($directory)) {
            $this->directories->add($directory);
            $directory->setOrganization($this);
        }

        return $this;
    }

    public function removeDirectory(Directory $directory): static
    {
        if ($this->directories->removeElement($directory)) {
            // set the owning side to null (unless already changed)
            if ($directory->getOrganization() === $this) {
                $directory->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidView>
     */
    public function getLogAidViews(): Collection
    {
        return $this->logAidViews;
    }

    public function addLogAidView(LogAidView $logAidView): static
    {
        if (!$this->logAidViews->contains($logAidView)) {
            $this->logAidViews->add($logAidView);
            $logAidView->setOrganization($this);
        }

        return $this;
    }

    public function removeLogAidView(LogAidView $logAidView): static
    {
        if ($this->logAidViews->removeElement($logAidView)) {
            // set the owning side to null (unless already changed)
            if ($logAidView->getOrganization() === $this) {
                $logAidView->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidCreatedsFolder>
     */
    public function getLogAidCreatedsFolders(): Collection
    {
        return $this->logAidCreatedsFolders;
    }

    public function addLogAidCreatedsFolder(LogAidCreatedsFolder $logAidCreatedsFolder): static
    {
        if (!$this->logAidCreatedsFolders->contains($logAidCreatedsFolder)) {
            $this->logAidCreatedsFolders->add($logAidCreatedsFolder);
            $logAidCreatedsFolder->setOrganization($this);
        }

        return $this;
    }

    public function removeLogAidCreatedsFolder(LogAidCreatedsFolder $logAidCreatedsFolder): static
    {
        if ($this->logAidCreatedsFolders->removeElement($logAidCreatedsFolder)) {
            // set the owning side to null (unless already changed)
            if ($logAidCreatedsFolder->getOrganization() === $this) {
                $logAidCreatedsFolder->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidSearch>
     */
    public function getLogAidSearches(): Collection
    {
        return $this->logAidSearches;
    }

    public function addLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if (!$this->logAidSearches->contains($logAidSearch)) {
            $this->logAidSearches->add($logAidSearch);
            $logAidSearch->setOrganization($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            // set the owning side to null (unless already changed)
            if ($logAidSearch->getOrganization() === $this) {
                $logAidSearch->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBackerView>
     */
    public function getLogBackerViews(): Collection
    {
        return $this->logBackerViews;
    }

    public function addLogBackerView(LogBackerView $logBackerView): static
    {
        if (!$this->logBackerViews->contains($logBackerView)) {
            $this->logBackerViews->add($logBackerView);
            $logBackerView->setOrganization($this);
        }

        return $this;
    }

    public function removeLogBackerView(LogBackerView $logBackerView): static
    {
        if ($this->logBackerViews->removeElement($logBackerView)) {
            // set the owning side to null (unless already changed)
            if ($logBackerView->getOrganization() === $this) {
                $logBackerView->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBlogPostView>
     */
    public function getLogBlogPostViews(): Collection
    {
        return $this->logBlogPostViews;
    }

    public function addLogBlogPostView(LogBlogPostView $logBlogPostView): static
    {
        if (!$this->logBlogPostViews->contains($logBlogPostView)) {
            $this->logBlogPostViews->add($logBlogPostView);
            $logBlogPostView->setOrganization($this);
        }

        return $this;
    }

    public function removeLogBlogPostView(LogBlogPostView $logBlogPostView): static
    {
        if ($this->logBlogPostViews->removeElement($logBlogPostView)) {
            // set the owning side to null (unless already changed)
            if ($logBlogPostView->getOrganization() === $this) {
                $logBlogPostView->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogProgramView>
     */
    public function getLogProgramViews(): Collection
    {
        return $this->logProgramViews;
    }

    public function addLogProgramView(LogProgramView $logProgramView): static
    {
        if (!$this->logProgramViews->contains($logProgramView)) {
            $this->logProgramViews->add($logProgramView);
            $logProgramView->setOrganization($this);
        }

        return $this;
    }

    public function removeLogProgramView(LogProgramView $logProgramView): static
    {
        if ($this->logProgramViews->removeElement($logProgramView)) {
            // set the owning side to null (unless already changed)
            if ($logProgramView->getOrganization() === $this) {
                $logProgramView->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogPublicProjectSearch>
     */
    public function getLogPublicProjectSearches(): Collection
    {
        return $this->logPublicProjectSearches;
    }

    public function addLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if (!$this->logPublicProjectSearches->contains($logPublicProjectSearch)) {
            $this->logPublicProjectSearches->add($logPublicProjectSearch);
            $logPublicProjectSearch->setOrganization($this);
        }

        return $this;
    }

    public function removeLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if ($this->logPublicProjectSearches->removeElement($logPublicProjectSearch)) {
            // set the owning side to null (unless already changed)
            if ($logPublicProjectSearch->getOrganization() === $this) {
                $logPublicProjectSearch->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogPublicProjectView>
     */
    public function getLogPublicProjectViews(): Collection
    {
        return $this->logPublicProjectViews;
    }

    public function addLogPublicProjectView(LogPublicProjectView $logPublicProjectView): static
    {
        if (!$this->logPublicProjectViews->contains($logPublicProjectView)) {
            $this->logPublicProjectViews->add($logPublicProjectView);
            $logPublicProjectView->setOrganization($this);
        }

        return $this;
    }

    public function removeLogPublicProjectView(LogPublicProjectView $logPublicProjectView): static
    {
        if ($this->logPublicProjectViews->removeElement($logPublicProjectView)) {
            // set the owning side to null (unless already changed)
            if ($logPublicProjectView->getOrganization() === $this) {
                $logPublicProjectView->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogProjectValidatedSearch>
     */
    public function getLogProjectValidatedSearches(): Collection
    {
        return $this->logProjectValidatedSearches;
    }

    public function addLogProjectValidatedSearch(LogProjectValidatedSearch $logProjectValidatedSearch): static
    {
        if (!$this->logProjectValidatedSearches->contains($logProjectValidatedSearch)) {
            $this->logProjectValidatedSearches->add($logProjectValidatedSearch);
            $logProjectValidatedSearch->setOrganization($this);
        }

        return $this;
    }

    public function removeLogProjectValidatedSearch(LogProjectValidatedSearch $logProjectValidatedSearch): static
    {
        if ($this->logProjectValidatedSearches->removeElement($logProjectValidatedSearch)) {
            // set the owning side to null (unless already changed)
            if ($logProjectValidatedSearch->getOrganization() === $this) {
                $logProjectValidatedSearch->setOrganization(null);
            }
        }

        return $this;
    }


    public function __toString(): string
    {
        return $this->name ?? null;
    }

    /**
     * @return Collection<int, OrganizationInvitation>
     */
    public function getOrganizationInvitations(): Collection
    {
        return $this->organizationInvitations;
    }

    public function addOrganizationInvitation(OrganizationInvitation $organizationInvitation): static
    {
        if (!$this->organizationInvitations->contains($organizationInvitation)) {
            $this->organizationInvitations->add($organizationInvitation);
            $organizationInvitation->setOrganization($this);
        }

        return $this;
    }

    public function removeOrganizationInvitation(OrganizationInvitation $organizationInvitation): static
    {
        if ($this->organizationInvitations->removeElement($organizationInvitation)) {
            // set the owning side to null (unless already changed)
            if ($organizationInvitation->getOrganization() === $this) {
                $organizationInvitation->setOrganization(null);
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
            $aid->setOrganization($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            // set the owning side to null (unless already changed)
            if ($aid->getOrganization() === $this) {
                $aid->setOrganization(null);
            }
        }

        return $this;
    }


    public function  getProjectsOfUser(User $user) : array {
        $projects = [];
        foreach ($this->getProjects() as $project) {
            if ($project->getAuthor() && $project->getAuthor()->getId() == $user->getId()) {
                $projects[] = $project;
            }
        }
        return $projects;
    }

    public function  getAidsOfUser(User $user) : array {
        $aids = [];
        foreach ($this->getAids() as $aid) {
            if ($aid->getAuthor() && $aid->getAuthor()->getId() == $user->getId()) {
                $aids[] = $aid;
            }
        }
        return $aids;
    }
}
