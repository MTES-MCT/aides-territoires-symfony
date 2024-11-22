<?php

namespace App\Entity\Perimeter;

use App\Entity\Aid\Aid;
use App\Entity\Backer\Backer;
use App\Entity\Blog\BlogPromotionPost;
use App\Entity\DataSource\DataSource;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogProjectValidatedSearch;
use App\Entity\Log\LogPublicProjectSearch;
use App\Entity\Organization\Organization;
use App\Entity\Program\Program;
use App\Entity\User\User;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model;
use App\Controller\Api\Perimeter\PerimeterController;
use App\Filter\Perimeter\PerimeterInseesFilter;
use App\Filter\Perimeter\PerimeterScaleFilter;
use App\Filter\Perimeter\PerimeterTextFilter;
use App\Filter\Perimeter\PerimeterZipcodesFilter;
use App\Service\Doctrine\DoctrineConstants;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Périmètres',
    operations: [
        new GetCollection(
            uriTemplate: '/perimeters/',
            controller: PerimeterController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: self::API_DESCRIPTION,
                description: self::API_DESCRIPTION,
            ),
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true
        ),
        new Get(
            normalizationContext: ['groups' => self::API_GROUP_ITEM],
            uriTemplate: '/perimeters/{id}/',
            controller: PerimeterController::class,
        ),
    ]
)]
#[ApiFilter(PerimeterTextFilter::class)]
#[ApiFilter(PerimeterScaleFilter::class)]
#[ApiFilter(PerimeterZipcodesFilter::class)]
#[ApiFilter(PerimeterInseesFilter::class)]
#[ORM\Entity(repositoryClass: PerimeterRepository::class)]
#[ORM\Index(columns: ['date_create'], name: 'date_create_peri')]
#[ORM\Index(columns: ['scale'], name: 'scale_peri')]
#[ORM\Index(columns: ['is_obsolete'], name: 'is_obsolete_peri')]
#[ORM\Index(columns: ['insee'], name: 'insee_peri')]
#[ORM\Index(columns: ['code'], name: 'codpe_peri')]
#[ORM\Index(columns: ['name'], name: 'name_peri')]
#[ORM\Index(columns: ['name'], name: 'name_peri_fulltext', flags: ['fulltext'])]
class Perimeter // NOSONAR too much methods
{
    public const API_GROUP_LIST = 'perimeter:list';
    public const API_GROUP_ITEM = 'perimeter:item';
    public const API_DESCRIPTION = 'Lister tous les périmètres';

    public const SCALE_COMMUNE_NAME = 'commune';
    public const SCALE_EPCI_NAME = 'epci';
    public const SCALE_BASIN_NAME = 'Bassin hydrographique';
    public const SCALE_DEPARTMENT_NAME = 'Département';
    public const SCALE_REGION_NAME = 'Région';
    public const SCALE_OVERSEAS_NAME = 'Outre-mer';
    public const SCALE_MAINLAND_NAME = 'Métropole';
    public const SCALE_ADHOC_NAME = 'Ad-hoc';
    public const SCALE_COUNTRY_NAME = 'Pays';
    public const SCALE_CONTINENT_NAME = 'Continent';

    public const SCALES_TUPLE = [
        ['scale' => 1, 'slug' => 'commune', 'name' => self::SCALE_COMMUNE_NAME],
        ['scale' => 5, 'slug' => 'epci', 'name' => self::SCALE_EPCI_NAME],
        ['scale' => 8, 'slug' => 'basin', 'name' => self::SCALE_BASIN_NAME],
        ['scale' => 10, 'slug' => 'department', 'name' => self::SCALE_DEPARTMENT_NAME],
        ['scale' => 15, 'slug' => 'region', 'name' => self::SCALE_REGION_NAME],
        ['scale' => 16, 'slug' => 'overseas', 'name' => self::SCALE_OVERSEAS_NAME],
        ['scale' => 17, 'slug' => 'mainland', 'name' => self::SCALE_MAINLAND_NAME],
        ['scale' => 18, 'slug' => 'adhoc', 'name' => self::SCALE_ADHOC_NAME],
        ['scale' => 20, 'slug' => 'country', 'name' => self::SCALE_COUNTRY_NAME],
        ['scale' => 25, 'slug' => 'continent', 'name' => self::SCALE_CONTINENT_NAME]
    ];


    public const SCALES_FOR_SEARCH = [
        1 => ['slug' => 'commune', 'name' => self::SCALE_COMMUNE_NAME],
        5 => ['slug' => 'epci', 'name' => self::SCALE_EPCI_NAME],
        8 => ['slug' => 'basin', 'name' => self::SCALE_BASIN_NAME],
        10 => ['slug' => 'department', 'name' => self::SCALE_DEPARTMENT_NAME],
        15 => ['slug' => 'region', 'name' => self::SCALE_REGION_NAME],
        16 => ['slug' => 'overseas', 'name' => self::SCALE_OVERSEAS_NAME],
        17 => ['slug' => 'mainland', 'name' => self::SCALE_MAINLAND_NAME],
        18 => ['slug' => 'adhoc', 'name' => self::SCALE_ADHOC_NAME],
        20 => ['slug' => 'country', 'name' => self::SCALE_COUNTRY_NAME],
        25 => ['slug' => 'continent', 'name' => self::SCALE_CONTINENT_NAME]
    ];

    public const SCALE_COUNTY = 10;
    public const SCALE_COMMUNE = 1;
    public const SCALE_EPCI = 5;
    public const SCALE_DEPARTEMENT = 10;
    public const SCALE_REGION = 15;
    public const SCALE_ADHOC = 18;
    public const SCALE_CONTINENT = 25;

    public const SLUG_LOCAL_GROUP = 'local_group';
    public const SCALES_LOCAL_GROUP = [
        ['scale' => 1, 'slug' => 'commune', 'name' => self::SCALE_COMMUNE_NAME],
        ['scale' => 5, 'slug' => 'epci', 'name' => self::SCALE_EPCI_NAME],
        ['scale' => 8, 'slug' => 'basin', 'name' => self::SCALE_BASIN_NAME],
        ['scale' => 10, 'slug' => 'department', 'name' => self::SCALE_DEPARTMENT_NAME],
        ['scale' => 15, 'slug' => 'region', 'name' => self::SCALE_REGION_NAME],
        ['scale' => 16, 'slug' => 'overseas', 'name' => self::SCALE_OVERSEAS_NAME],
        ['scale' => 18, 'slug' => 'adhoc', 'name' => self::SCALE_ADHOC_NAME],
    ];

    public const SLUG_NATIONAL_GROUP = 'national_group';
    public const SCALES_NATIONAL_GROUP = [
        ['scale' => 17, 'slug' => 'mainland', 'name' => self::SCALE_MAINLAND_NAME],
        ['scale' => 20, 'slug' => 'country', 'name' => self::SCALE_COUNTRY_NAME],
        ['scale' => 25, 'slug' => 'continent', 'name' => self::SCALE_CONTINENT_NAME]
    ];

    public const SLUG_CONTINENT_DEFAULT = 'EU';
    public const CODE_EUROPE = 'EU';
    public const SLUG_COUNTRY_DEFAULT = 'FRA';
    public const CODE_FRANCE = 'FRA';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $scale = null;

    #[Assert\Length(max: 16)]
    #[ORM\Column(length: 16)]
    private ?string $code = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM, Backer::API_GROUP_LIST])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(max: 32)]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $epci = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $zipcodes = null;

    #[ORM\Column(length: 2)]
    private ?string $continent = null;

    #[Assert\Length(max: 3)]
    #[ORM\Column(length: 3)]
    private ?string $country = null;

    #[ORM\Column]
    private ?bool $isOverseas = false;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $departments = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $regions = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $basin = null;

    #[ORM\Column]
    private ?bool $manuallyCreated = false;

    #[ORM\Column]
    private ?bool $isVisibleToUsers = false;

    #[Assert\Length(max: 128)]
    #[ORM\Column(length: 128)]
    private ?string $unaccentedName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(nullable: true)]
    private ?int $backersCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $programsCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $categoriesCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $liveAidsCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeObsolete = null;

    #[ORM\Column]
    private ?bool $isObsolete = false;

    #[ORM\Column(nullable: true)]
    private ?int $population = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $projectsCount = null;

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $densityTypology = null;

    #[Assert\Length(max: 5)]
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $insee = null;

    #[Assert\Length(exactly: 9)]
    #[ORM\Column(length: 9, nullable: true)]
    private ?string $siren = null;

    #[Assert\Length(exactly: 14)]
    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $surface = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: User::class)]
    private Collection $users;

    /**
     * @var Collection<int, Organization>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: Organization::class)]
    private Collection $organizations;

    /**
     * @var Collection<int, Organization>
     */
    #[ORM\OneToMany(mappedBy: 'perimeterDepartment', targetEntity: Organization::class)]
    private Collection $organizationDepartments;

    /**
     * @var Collection<int, Organization>
     */
    #[ORM\OneToMany(mappedBy: 'perimeterRegion', targetEntity: Organization::class)]
    private Collection $organizationRegions;

    /**
     * @var Collection<int, Backer>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: Backer::class)]
    private Collection $backers;

    /**
     * @var Collection<int, PerimeterData>
     */
    #[ORM\OneToMany(
        mappedBy: 'perimeter',
        targetEntity: PerimeterData::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $perimeterDatas;

    /**
     * @var Collection<int, PerimeterImport>
     */
    #[ORM\OneToMany(
        mappedBy: 'adhocPerimeter',
        targetEntity: PerimeterImport::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    private Collection $perimeterImports;

    /*
    * Les périmètre parents
    * ceux qui contiennent ce périmètre
    * ex: si ce périmètre = Essonne, perimetersTo contiendra Ile-de-france, France, ...
    */
    /**
     * @var Collection<int, Perimeter>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'perimetersFrom')]
    private Collection $perimetersTo;

    /**
     *
     * Les périmètres enfants
     * ceux contenu dans ce périmètre
     * ex: si ce périmètre = Esonne, perimetersFrom contiendra Fontenay-les-briis, Evry, ...
     */
    /**
     * @var Collection<int, Perimeter>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'perimetersTo')]
    private Collection $perimetersFrom;

    /**
     * @var Collection<int, Program>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: Program::class)]
    private Collection $programs;

    /**
     * @var Collection<int, DataSource>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: DataSource::class)]
    private Collection $dataSources;

    /**
     * @var Collection<int, Aid>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: Aid::class)]
    private Collection $aids;

    /**
     * @var Collection<int, BlogPromotionPost>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: BlogPromotionPost::class)]
    private Collection $blogPromotionPosts;

    /**
     * @var Collection<int, FinancialData>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: FinancialData::class)]
    private Collection $financialData;

    /**
     * @var Collection<int, LogAidSearch>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: LogAidSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidSearches;

    /**
     * @var Collection<int, LogPublicProjectSearch>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: LogPublicProjectSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logPublicProjectSearches;

    /**
     * @var Collection<int, LogProjectValidatedSearch>
     */
    #[ORM\OneToMany(mappedBy: 'perimeter', targetEntity: LogProjectValidatedSearch::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logProjectValidatedSearches;


    private ?string $scaleName;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->organizations = new ArrayCollection();
        $this->organizationDepartments = new ArrayCollection();
        $this->organizationRegions = new ArrayCollection();
        $this->backers = new ArrayCollection();
        $this->perimeterDatas = new ArrayCollection();
        $this->perimeterImports = new ArrayCollection();
        $this->perimetersTo = new ArrayCollection();
        $this->perimetersFrom = new ArrayCollection();
        $this->programs = new ArrayCollection();
        $this->dataSources = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->blogPromotionPosts = new ArrayCollection();
        $this->financialData = new ArrayCollection();
        $this->logAidSearches = new ArrayCollection();
        $this->logPublicProjectSearches = new ArrayCollection();
        $this->logProjectValidatedSearches = new ArrayCollection();
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

    public function getTimeUpdate(): ?\DateTimeInterface
    {
        return $this->timeUpdate;
    }

    public function setTimeUpdate(?\DateTimeInterface $timeUpdate): static
    {
        $this->timeUpdate = $timeUpdate;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setPerimeter($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user) && $user->getPerimeter() === $this) {
            $user->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization): static
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
            $organization->setPerimeter($this);
        }

        return $this;
    }

    public function removeOrganization(Organization $organization): static
    {
        if ($this->organizations->removeElement($organization) && $organization->getPerimeter() === $this) {
            $organization->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizationDepartments(): Collection
    {
        return $this->organizationDepartments;
    }

    public function addOrganizationDepartment(Organization $organizationDepartment): static
    {
        if (!$this->organizationDepartments->contains($organizationDepartment)) {
            $this->organizationDepartments->add($organizationDepartment);
            $organizationDepartment->setPerimeterDepartment($this);
        }

        return $this;
    }

    public function removeOrganizationDepartment(Organization $organizationDepartment): static
    {
        if (
            $this->organizationDepartments->removeElement($organizationDepartment)
            && $organizationDepartment->getPerimeterDepartment() === $this
        ) {
            $organizationDepartment->setPerimeterDepartment(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getOrganizationRegions(): Collection
    {
        return $this->organizationRegions;
    }

    public function addOrganizationRegion(Organization $organizationRegion): static
    {
        if (!$this->organizationRegions->contains($organizationRegion)) {
            $this->organizationRegions->add($organizationRegion);
            $organizationRegion->setPerimeterRegion($this);
        }

        return $this;
    }

    public function removeOrganizationRegion(Organization $organizationRegion): static
    {
        if (
            $this->organizationRegions->removeElement($organizationRegion)
            && $organizationRegion->getPerimeterRegion() === $this
        ) {
            $organizationRegion->setPerimeterRegion(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Backer>
     */
    public function getBackers(): Collection
    {
        return $this->backers;
    }

    public function addBacker(Backer $backer): static
    {
        if (!$this->backers->contains($backer)) {
            $this->backers->add($backer);
            $backer->setPerimeter($this);
        }

        return $this;
    }

    public function removeBacker(Backer $backer): static
    {
        if ($this->backers->removeElement($backer) && $backer->getPerimeter() === $this) {
            $backer->setPerimeter(null);
        }

        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function setScale(int $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getEpci(): ?string
    {
        return $this->epci;
    }

    public function setEpci(?string $epci): static
    {
        $this->epci = $epci;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getZipcodes(): ?array
    {
        return $this->zipcodes;
    }

    /**
     *
     * @param string[]|null $zipcodes
     * @return static
     */
    public function setZipcodes(?array $zipcodes): static
    {
        $this->zipcodes = $zipcodes;

        return $this;
    }

    public function getContinent(): ?string
    {
        return $this->continent;
    }

    public function setContinent(string $continent): static
    {
        $this->continent = $continent;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isIsOverseas(): ?bool
    {
        return $this->isOverseas;
    }

    public function setIsOverseas(bool $isOverseas): static
    {
        $this->isOverseas = $isOverseas;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getDepartments(): ?array
    {
        return $this->departments;
    }

    /**
     *
     * @param string[]|null $departments
     * @return static
     */
    public function setDepartments(?array $departments): static
    {
        $this->departments = $departments;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getRegions(): ?array
    {
        return $this->regions;
    }

    /**
     *
     * @param string[]|null $regions
     * @return static
     */
    public function setRegions(?array $regions): static
    {
        $this->regions = $regions;

        return $this;
    }

    public function getBasin(): ?string
    {
        return $this->basin;
    }

    public function setBasin(?string $basin): static
    {
        $this->basin = $basin;

        return $this;
    }

    public function isManuallyCreated(): ?bool
    {
        return $this->manuallyCreated;
    }

    public function setManuallyCreated(bool $manuallyCreated): static
    {
        $this->manuallyCreated = $manuallyCreated;

        return $this;
    }

    public function isIsVisibleToUsers(): ?bool
    {
        return $this->isVisibleToUsers;
    }

    public function setIsVisibleToUsers(bool $isVisibleToUsers): static
    {
        $this->isVisibleToUsers = $isVisibleToUsers;

        return $this;
    }

    public function getUnaccentedName(): ?string
    {
        return $this->unaccentedName;
    }

    public function setUnaccentedName(string $unaccentedName): static
    {
        $this->unaccentedName = $unaccentedName;

        return $this;
    }

    public function getBackersCount(): ?int
    {
        return $this->backersCount;
    }

    public function setBackersCount(?int $backersCount): static
    {
        $this->backersCount = $backersCount;

        return $this;
    }

    public function getProgramsCount(): ?int
    {
        return $this->programsCount;
    }

    public function setProgramsCount(?int $programsCount): static
    {
        $this->programsCount = $programsCount;

        return $this;
    }

    public function getCategoriesCount(): ?int
    {
        return $this->categoriesCount;
    }

    public function setCategoriesCount(?int $categoriesCount): static
    {
        $this->categoriesCount = $categoriesCount;

        return $this;
    }

    public function getLiveAidsCount(): ?int
    {
        return $this->liveAidsCount;
    }

    public function setLiveAidsCount(?int $liveAidsCount): static
    {
        $this->liveAidsCount = $liveAidsCount;

        return $this;
    }

    public function getTimeObsolete(): ?\DateTimeInterface
    {
        return $this->timeObsolete;
    }

    public function setTimeObsolete(?\DateTimeInterface $timeObsolete): static
    {
        $this->timeObsolete = $timeObsolete;

        return $this;
    }

    public function isIsObsolete(): ?bool
    {
        return $this->isObsolete;
    }

    public function setIsObsolete(bool $isObsolete): static
    {
        $this->isObsolete = $isObsolete;

        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(?int $population): static
    {
        $this->population = $population;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getProjectsCount(): ?int
    {
        return $this->projectsCount;
    }

    public function setProjectsCount(?int $projectsCount): static
    {
        $this->projectsCount = $projectsCount;

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

    public function getInsee(): ?string
    {
        return $this->insee;
    }

    public function setInsee(?string $insee): static
    {
        $this->insee = $insee;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getSurface(): ?string
    {
        return $this->surface;
    }

    public function setSurface(?string $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    /**
     * @return Collection<int, PerimeterData>
     */
    public function getPerimeterDatas(): Collection
    {
        return $this->perimeterDatas;
    }

    public function addPerimeterData(PerimeterData $perimeterData): static
    {
        if (!$this->perimeterDatas->contains($perimeterData)) {
            $this->perimeterDatas->add($perimeterData);
            $perimeterData->setPerimeter($this);
        }

        return $this;
    }

    public function removePerimeterData(PerimeterData $perimeterData): static
    {
        if ($this->perimeterDatas->removeElement($perimeterData) && $perimeterData->getPerimeter() === $this) {
            $perimeterData->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PerimeterImport>
     */
    public function getPerimeterImports(): Collection
    {
        return $this->perimeterImports;
    }

    public function addPerimeterImport(PerimeterImport $perimeterImport): static
    {
        if (!$this->perimeterImports->contains($perimeterImport)) {
            $this->perimeterImports->add($perimeterImport);
            $perimeterImport->setAdhocPerimeter($this);
        }

        return $this;
    }

    public function removePerimeterImport(PerimeterImport $perimeterImport): static
    {
        if (
            $this->perimeterImports->removeElement($perimeterImport)
            && $perimeterImport->getAdhocPerimeter() === $this
        ) {
            $perimeterImport->setAdhocPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getPerimetersTo(): Collection
    {
        return $this->perimetersTo;
    }

    public function addPerimetersTo(self $perimetersTo): static
    {
        if (!$this->perimetersTo->contains($perimetersTo)) {
            $this->perimetersTo->add($perimetersTo);
        }

        return $this;
    }

    public function removePerimetersTo(self $perimetersTo): static
    {
        $this->perimetersTo->removeElement($perimetersTo);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getPerimetersFrom(): Collection
    {
        return $this->perimetersFrom;
    }

    public function addPerimetersFrom(self $perimetersFrom): static
    {
        if (!$this->perimetersFrom->contains($perimetersFrom)) {
            $this->perimetersFrom->add($perimetersFrom);
            $perimetersFrom->addPerimetersTo($this);
        }

        return $this;
    }

    public function removePerimetersFrom(self $perimetersFrom): static
    {
        if ($this->perimetersFrom->removeElement($perimetersFrom)) {
            $perimetersFrom->removePerimetersTo($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Program>
     */
    public function getPrograms(): Collection
    {
        return $this->programs;
    }

    public function addProgram(Program $program): static
    {
        if (!$this->programs->contains($program)) {
            $this->programs->add($program);
            $program->setPerimeter($this);
        }

        return $this;
    }

    public function removeProgram(Program $program): static
    {
        if ($this->programs->removeElement($program) && $program->getPerimeter() === $this) {
            $program->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DataSource>
     */
    public function getDataSources(): Collection
    {
        return $this->dataSources;
    }

    public function addDataSource(DataSource $dataSource): static
    {
        if (!$this->dataSources->contains($dataSource)) {
            $this->dataSources->add($dataSource);
            $dataSource->setPerimeter($this);
        }

        return $this;
    }

    public function removeDataSource(DataSource $dataSource): static
    {
        if ($this->dataSources->removeElement($dataSource) && $dataSource->getPerimeter() === $this) {
            $dataSource->setPerimeter(null);
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
            $aid->setPerimeter($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid) && $aid->getPerimeter() === $this) {
            $aid->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, BlogPromotionPost>
     */
    public function getBlogPromotionPosts(): Collection
    {
        return $this->blogPromotionPosts;
    }

    public function addBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if (!$this->blogPromotionPosts->contains($blogPromotionPost)) {
            $this->blogPromotionPosts->add($blogPromotionPost);
            $blogPromotionPost->setPerimeter($this);
        }

        return $this;
    }

    public function removeBlogPromotionPost(BlogPromotionPost $blogPromotionPost): static
    {
        if (
            $this->blogPromotionPosts->removeElement($blogPromotionPost)
            && $blogPromotionPost->getPerimeter() === $this
        ) {
            $blogPromotionPost->setPerimeter(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, FinancialData>
     */
    public function getFinancialData(): Collection
    {
        return $this->financialData;
    }

    public function addFinancialData(FinancialData $financialData): static
    {
        if (!$this->financialData->contains($financialData)) {
            $this->financialData->add($financialData);
            $financialData->setPerimeter($this);
        }

        return $this;
    }

    public function removeFinancialData(FinancialData $financialData): static
    {
        if ($this->financialData->removeElement($financialData) && $financialData->getPerimeter() === $this) {
            $financialData->setPerimeter(null);
        }

        return $this;
    }

    /**
     *
     * @return int[]
     */
    public function getPerimetersToIds(): array
    {
        $ids = [];
        foreach ($this->getPerimetersTo() as $perimetersTo) {
            $ids[] = $perimetersTo->getId();
        }
        return $ids;
    }

    /**
     *
     * @return int[]
     */
    public function getPerimetersFromIds(): array
    {
        $ids = [];
        foreach ($this->getPerimetersFrom() as $perimetersFrom) {
            $ids[] = $perimetersFrom->getId();
        }
        return $ids;
    }


    public function __toString(): string
    {
        $name = $this->getName();
        if ($this->getScale() == self::SCALE_COUNTY) {
            $name .= ' (Département)';
        } elseif ($this->getScale() == Perimeter::SCALE_REGION) {
            $name .= ' (Région)';
        } elseif ($this->getScale() == Perimeter::SCALE_COMMUNE) {
            $name .= ' (Commune)';
        } elseif ($this->getScale() == Perimeter::SCALE_ADHOC) {
            $name .= ' (Adhoc)';
        }

        return $name;
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
            $logAidSearch->setPerimeter($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch) && $logAidSearch->getPerimeter() === $this) {
            $logAidSearch->setPerimeter(null);
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
            $logPublicProjectSearch->setPerimeter($this);
        }

        return $this;
    }

    public function removeLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if (
            $this->logPublicProjectSearches->removeElement($logPublicProjectSearch)
            && $logPublicProjectSearch->getPerimeter() === $this
        ) {
            $logPublicProjectSearch->setPerimeter(null);
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
            $logProjectValidatedSearch->setPerimeter($this);
        }

        return $this;
    }

    public function removeLogProjectValidatedSearch(LogProjectValidatedSearch $logProjectValidatedSearch): static
    {
        if (
            $this->logProjectValidatedSearches->removeElement($logProjectValidatedSearch)
            && $logProjectValidatedSearch->getPerimeter() === $this
        ) {
            $logProjectValidatedSearch->setPerimeter(null);
        }

        return $this;
    }



    public function getScaleName(): ?string
    {
        $this->scaleName = self::SCALES_FOR_SEARCH[$this->getScale()]['name'] ?? null;
        return $this->scaleName;
    }

    public function setScaleName(?string $scaleName): static
    {
        $this->scaleName = $scaleName;
        return $this;
    }
}
