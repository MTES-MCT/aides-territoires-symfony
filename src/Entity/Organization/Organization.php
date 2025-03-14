<?php

namespace App\Entity\Organization;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerAskAssociate;
use App\Entity\Directory\Directory;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
use App\Entity\Log\LogBackerEdit;
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
use App\Service\Doctrine\DoctrineConstants;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Index(columns: ['is_imported'], name: 'organization_is_imported')]
#[ORM\Index(columns: ['intercommunality_type'], name: 'intercommunality_type_organization')]
class Organization // NOSONAR too much methods
{
    public const INTERCOMMUNALITY_TYPES = [
        ['slug' => 'CC', 'name' => 'Communauté de communes (CC)'],
        ['slug' => 'CA', 'name' => 'Communauté d’agglomération (CA)'],
        ['slug' => 'CU', 'name' => 'Communauté urbaine (CU)'],
        ['slug' => 'METRO', 'name' => 'Métropole'],
        ['slug' => 'GAL', 'name' => 'Groupe d’action locale (GAL)'],
        ['slug' => 'PNR', 'name' => 'Parc naturel régional (PNR)'],
        ['slug' => 'PETR', 'name' => 'Pays et pôles d’équilibre territorial et rural (PETR)'],
        ['slug' => 'SM', 'name' => 'Syndicat mixte et syndicat de commune'],
    ];

    public const TOTAL_BY_INTERCOMMUNALITY_TYPE = [
        "CC" => 1019,
        "CA" => 219,
        "CU" => 14,
        "METRO" => 22,
        "GAL" => 339,
        "PNR" => 59,
        "PETR" => 368,
        "SM" => 9970,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'organizations')]
    private ?OrganizationType $organizationType = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cityName = null;

    #[Assert\Length(max: 10)]
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[Assert\Length(exactly: 9)]
    #[ORM\Column(length: 9, nullable: true)]
    private ?string $sirenCode = null;

    #[Assert\Length(exactly: 14)]
    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siretCode = null;

    #[Assert\Length(max: 10)]
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
    #[JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Backer $backer = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $densityTypology = null;

    #[Assert\Length(exactly: 5)]
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $inseeCode = null;

    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $populationStrata = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\ManyToMany(targetEntity: Project::class, fetch: 'LAZY')]
    private Collection $favoriteProjects;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Project::class)]
    private Collection $projects;

    /**
     * @var Collection<int, ProjectValidated>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'organizations')]
    private Collection $beneficiairies;

    /**
     * @var Collection<int, Directory>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Directory::class, orphanRemoval: true)]
    private Collection $directories;

    /**
     * @var Collection<int, OrganizationInvitation>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: OrganizationInvitation::class, orphanRemoval: true)]
    private Collection $organizationInvitations;

    /**
     * @var Collection<int, Aid>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: Aid::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $aids;

    /**
     * @var Collection<int, LogAidView>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidViews;

    /**
     * @var Collection<int, LogAidCreatedsFolder>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidCreatedsFolder::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidCreatedsFolders;

    /**
     * @var Collection<int, LogAidSearch>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogAidSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidSearches;

    /**
     * @var Collection<int, LogBackerView>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogBackerView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logBackerViews;

    /**
     * @var Collection<int, LogBlogPostView>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogBlogPostView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logBlogPostViews;

    /**
     * @var Collection<int, LogProgramView>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogProgramView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logProgramViews;

    /**
     * @var Collection<int, LogPublicProjectSearch>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogPublicProjectSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logPublicProjectSearches;

    /**
     * @var Collection<int, LogPublicProjectView>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogPublicProjectView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logPublicProjectViews;

    /**
     * @var Collection<int, LogProjectValidatedSearch>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogProjectValidatedSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logProjectValidatedSearches;

    /**
     * @var Collection<int, BackerAskAssociate>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: BackerAskAssociate::class, orphanRemoval: true)]
    private Collection $backerAskAssociates;

    /**
     * @var Collection<int, LogBackerEdit>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: LogBackerEdit::class)]
    private Collection $logBackerEdits;

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
        $this->backerAskAssociates = new ArrayCollection();
        $this->logBackerEdits = new ArrayCollection();
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
        if ($this->projects->removeElement($project) && $project->getOrganization() === $this) {
            $project->setOrganization(null);
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
        if (
            $this->projectValidateds->removeElement($projectValidated)
            && $projectValidated->getOrganization() === $this
        ) {
            $projectValidated->setOrganization(null);
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
        if ($this->directories->removeElement($directory) && $directory->getOrganization() === $this) {
            $directory->setOrganization(null);
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
        if ($this->logAidViews->removeElement($logAidView) && $logAidView->getOrganization() === $this) {
            $logAidView->setOrganization(null);
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
        if (
            $this->logAidCreatedsFolders->removeElement($logAidCreatedsFolder)
            && $logAidCreatedsFolder->getOrganization() === $this
        ) {
            $logAidCreatedsFolder->setOrganization(null);
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
        if ($this->logAidSearches->removeElement($logAidSearch) && $logAidSearch->getOrganization() === $this) {
            $logAidSearch->setOrganization(null);
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
        if ($this->logBackerViews->removeElement($logBackerView) && $logBackerView->getOrganization() === $this) {
            $logBackerView->setOrganization(null);
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
        if ($this->logBlogPostViews->removeElement($logBlogPostView) && $logBlogPostView->getOrganization() === $this) {
            $logBlogPostView->setOrganization(null);
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
        if ($this->logProgramViews->removeElement($logProgramView) && $logProgramView->getOrganization() === $this) {
            $logProgramView->setOrganization(null);
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
        if (
            $this->logPublicProjectSearches->removeElement($logPublicProjectSearch)
            && $logPublicProjectSearch->getOrganization() === $this
        ) {
            $logPublicProjectSearch->setOrganization(null);
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
        if (
            $this->logPublicProjectViews->removeElement($logPublicProjectView)
            && $logPublicProjectView->getOrganization() === $this
        ) {
            $logPublicProjectView->setOrganization(null);
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
        if (
            $this->logProjectValidatedSearches->removeElement($logProjectValidatedSearch)
            && $logProjectValidatedSearch->getOrganization() === $this
        ) {
            $logProjectValidatedSearch->setOrganization(null);
        }

        return $this;
    }


    public function __toString(): string
    {
        $return = '';
        if ($this->name) {
            $return .= $this->name;
        }
        if ($this->id) {
            $return .= ' (' . $this->id . ')';
        }
        return $return;
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
        if (
            $this->organizationInvitations->removeElement($organizationInvitation)
            && $organizationInvitation->getOrganization() === $this
        ) {
            $organizationInvitation->setOrganization(null);
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
        if ($this->aids->removeElement($aid) && $aid->getOrganization() === $this) {
            $aid->setOrganization(null);
        }

        return $this;
    }

    /**
     * @param User $user
     * @return array<int, Project>
     */
    public function getProjectsOfUser(User $user): array
    {
        $projects = [];
        foreach ($this->getProjects() as $project) {
            if ($project->getAuthor() && $project->getAuthor()->getId() == $user->getId()) {
                $projects[] = $project;
            }
        }
        return $projects;
    }

    /**
     * @param User $user
     * @return array<int, Aid>
     */
    public function getAidsOfUser(User $user): array
    {
        $aids = [];
        foreach ($this->getAids() as $aid) {
            if ($aid->getAuthor() && $aid->getAuthor()->getId() == $user->getId()) {
                $aids[] = $aid;
            }
        }
        return $aids;
    }


    public function getFirstBeneficary(): ?User
    {
        if ($this->beneficiairies->isEmpty()) {
            return null;
        }
        return $this->beneficiairies->first();
    }

    /**
     * @return Collection<int, BackerAskAssociate>
     */
    public function getBackerAskAssociates(): Collection
    {
        return $this->backerAskAssociates;
    }

    public function addBackerAskAssociate(BackerAskAssociate $backerAskAssociate): static
    {
        if (!$this->backerAskAssociates->contains($backerAskAssociate)) {
            $this->backerAskAssociates->add($backerAskAssociate);
            $backerAskAssociate->setOrganization($this);
        }

        return $this;
    }

    public function removeBackerAskAssociate(BackerAskAssociate $backerAskAssociate): static
    {
        if ($this->backerAskAssociates->removeElement($backerAskAssociate)) {
            // set the owning side to null (unless already changed)
            if ($backerAskAssociate->getOrganization() === $this) {
                $backerAskAssociate->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogBackerEdit>
     */
    public function getLogBackerEdits(): Collection
    {
        return $this->logBackerEdits;
    }

    public function addLogBackerEdit(LogBackerEdit $logBackerEdit): static
    {
        if (!$this->logBackerEdits->contains($logBackerEdit)) {
            $this->logBackerEdits->add($logBackerEdit);
            $logBackerEdit->setOrganization($this);
        }

        return $this;
    }

    public function removeLogBackerEdit(LogBackerEdit $logBackerEdit): static
    {
        if ($this->logBackerEdits->removeElement($logBackerEdit)) {
            // set the owning side to null (unless already changed)
            if ($logBackerEdit->getOrganization() === $this) {
                $logBackerEdit->setOrganization(null);
            }
        }

        return $this;
    }
}
