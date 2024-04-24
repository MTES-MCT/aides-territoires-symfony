<?php

namespace App\Entity\Aid;

use App\Entity\Bundle\Bundle;
use App\Entity\Category\Category;
use App\Entity\DataSource\DataSource;
use App\Entity\Eligibility\EligibilityTest;
use App\Entity\Keyword\Keyword;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\Log\LogAidContactClick;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidEligibilityTest;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Entity\Log\LogAidView;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Project\ProjectValidated;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\Metadata\ApiProperty;
use App\Controller\Api\Aid\AidController as AidAidController;
use App\Filter\Aid\AidApplyBeforeFilter;
use App\Filter\Aid\AidCallForProjectOnlyFilter;
use App\Filter\Aid\AidDestinationFilter;
use App\Filter\Aid\AidFinancialAidFilter;
use App\Filter\Aid\AidIsChargedFilter;
use App\Filter\Aid\AidMobilizationStepFilter;
use App\Filter\Aid\AidPerimeterFilter;
use App\Filter\Aid\AidPublishedAfterFilter;
use App\Filter\Aid\AidRecurrenceFilter;
use App\Filter\Aid\AidTargetedAudiencesFilter;
use App\Filter\Aid\AidTechnicalAidFilter;
use App\Filter\Aid\AidTextFilter;
use App\Filter\Aid\AidTypeGroupFilter;

#[ORM\Entity(repositoryClass: AidRepository::class)]
#[ORM\Index(columns: ['status'], name: 'status_aid')]
#[ORM\Index(columns: ['date_start'], name: 'date_start_aid')]
#[ORM\Index(columns: ['date_submission_deadline'], name: 'date_submission_deadline_aid')]
#[ORM\Index(columns: ['date_published'], name: 'date_published_aid')]
#[ORM\Index(columns: ['date_check_broken_link'], name: 'date_check_broken_link_aid')]
#[ORM\Index(columns: ['european_aid'], name: 'european_aid_aid')]
#[ORM\Index(columns: ['origin_url'], name: 'origin_url_aid')]
#[ORM\Index(columns: ['import_uniqueid'], name: 'import_uniqueid_aid')]
#[ORM\Index(columns: ['name'], name: 'name_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['name_initial'], name: 'name_initial_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['description'], name: 'description_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['eligibility'], name: 'eligibility_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['name','description','eligibility'], name: 'synonym_aid_nde_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['description','eligibility','project_examples'], name: 'synonym_aid_fulltext', flags: ['fulltext'])]
#[ApiResource(
    operations: [
        new GetCollection(
            name: self::API_OPERATION_GET_COLLECTION_PUBLISHED,
            uriTemplate: '/aids/',
            controller: AidAidController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister toutes les aides actuellement publiées', 
                description: 'Lister toutes les aides actuellement publiées',
            )
        ),
        new Get(
            name: self::API_OPERATION_GET_BY_SLUG,
            normalizationContext: ['groups' => self::API_GROUP_ITEM],
            uriTemplate: '/aids/{slug}/',
            controller: AidAidController::class,
        ),
    ],
    order: ['dateStart' => 'DESC', 'id' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationClientItemsPerPage: true
    
)]

#[ApiFilter(AidTextFilter::class)]
#[ApiFilter(AidTargetedAudiencesFilter::class)]
#[ApiFilter(AidApplyBeforeFilter::class)]
#[ApiFilter(AidPublishedAfterFilter::class)]
#[ApiFilter(AidTypeGroupFilter::class)]
#[ApiFilter(AidFinancialAidFilter::class)]
#[ApiFilter(AidTechnicalAidFilter::class)]
#[ApiFilter(AidMobilizationStepFilter::class)]
#[ApiFilter(AidDestinationFilter::class)]
#[ApiFilter(AidRecurrenceFilter::class)]
#[ApiFilter(AidCallForProjectOnlyFilter::class)]
#[ApiFilter(AidIsChargedFilter::class)]
#[ApiFilter(AidPerimeterFilter::class)]
class Aid
{
    const API_OPERATION_GET_BY_SLUG = 'api_aid_get_by_slug';
    const API_OPERATION_GET_COLLECTION_PUBLISHED = 'api_aids_published';
    const API_OPERATION_GET_COLLECTION_ALL = 'api_aids_all';

    const API_GROUP_LIST = 'aid:list';
    const API_GROUP_ITEM = 'aid:item';

    const STATUS_PUBLISHED = 'published';
    const STATUS_DELETED = 'deleted';
    const STATUS_DRAFT = 'draft';
    const STATUS_MERGED = 'merged';
    const STATUS_REVIEWABLE = 'reviewable';

    const STATUSES = [
        ['slug' => self::STATUS_PUBLISHED, 'name' => 'Publiée'],
        ['slug' => self::STATUS_DELETED, 'name' => 'Supprimée'],
        ['slug' => self::STATUS_DRAFT, 'name' => 'Brouillon'],
        ['slug' => self::STATUS_MERGED, 'name' => 'Mergé'],
        ['slug' => self::STATUS_REVIEWABLE, 'name' => 'En revue'],
    ];
    
    const APPROACHING_DEADLINE_DELTA = 30;  # days

    const SLUG_EUROPEAN = 'european';
    const SLUG_EUROPEAN_SECTORIAL = 'sectorial';
    const SLUG_EUROPEAN_ORGANIZATIONAL = 'organizational';
    const LABELS_EUROPEAN = [
        self::SLUG_EUROPEAN => 'Aides européennes',
        self::SLUG_EUROPEAN_SECTORIAL => 'Aides européennes sectorielles',
        self::SLUG_EUROPEAN_ORGANIZATIONAL => 'Aides européennes structurelles',
    ];


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $description = null;

    #[ORM\Column(length: 16)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $status = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Lien vers plus d\'informations',
            'example' => 'https://appelsaprojets.ademe.fr/aap/AURASTC2019-54'
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originUrl = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Les audiences cibles',
            'enum' => [
                OrganizationType::SLUG_COMMUNE,
                OrganizationType::SLUG_EPCI,
                OrganizationType::SLUG_DEPARTMENT,
                OrganizationType::SLUG_REGION,
                OrganizationType::SLUG_SPECIAL,
                OrganizationType::SLUG_PUBLIC_ORG,
                OrganizationType::SLUG_PUBLIC_CIES,
                OrganizationType::SLUG_ASSOCIATION,
                OrganizationType::SLUG_PRIVATE_SECTOR,
                OrganizationType::SLUG_PRIVATE_PERSON,
                OrganizationType::SLUG_FARMER,
                OrganizationType::SLUG_RESEARCHER,
            ],
            'example' => OrganizationType::SLUG_COMMUNE
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: OrganizationType::class, inversedBy: 'aids')]
    private Collection $aidAudiences;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Nature de l\'aide',
            'enum' => [
                AidType::SLUG_GRANT,
                AidType::SLUG_LOAN,
                AidType::SLUG_RECOVERABLE_ADVANCE,
                AidType::SLUG_CEE,
                AidType::SLUG_OTHER,
                AidType::SLUG_TECHNICAL_ENGINEERING,
                AidType::SLUG_FINANCIAL_ENGINEERING,
                AidType::SLUG_LEGAL_ENGINEERING,
            ],
            'example' => AidType::SLUG_GRANT
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: AidType::class, inversedBy: 'aids')]
    private Collection $aidTypes;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Actions concernées',
            'enum' => [
                AidDestination::SLUG_SUPPLY,
                AidDestination::SLUG_INVESTMENT,
            ],
            'example' => AidDestination::SLUG_SUPPLY
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: AidDestination::class, inversedBy: 'aids')]
    private Collection $aidDestinations;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateStart = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePredeposit = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'La date limite de dépôt du dossier',
            'example' => '2023-05-30'
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSubmissionDeadline = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactDetail = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\ManyToOne(inversedBy: 'aids')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $author = null;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: AidStep::class, inversedBy: 'aids')]
    private Collection $aidSteps;

    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $eligibility = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Récurrence',
            'enum' => [
                AidRecurrence::SLUG_ONEOFF,
                AidRecurrence::SLUG_ONGOING,
                AidRecurrence::SLUG_RECURRING,
            ],
            'example' => AidRecurrence::SLUG_ONEOFF
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToOne(inversedBy: 'aids')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AidRecurrence $aidRecurrence = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Le territoire. Note : passer seulement l\'id du périmètre suffit (perimeter=70973).',
            'example' => '70973'
        ]
    )]
    #[Groups([Aid::API_GROUP_LIST, Aid::API_GROUP_ITEM])]
    #[ORM\ManyToOne(inversedBy: 'aids')]
    private ?Perimeter $perimeter = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $applicationUrl = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ApiProperty(identifier: true)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $isImported = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importUniqueid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $financerSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importDataUrl = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateImportLastAccess = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $importShareLicence = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Appels à projets / Appels à manifestation d\'intérêt',
            'enum' => [true, false],
            'example' => true
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column]
    private ?bool $isCallForProject = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'aidsAmended')]
    private ?self $amendedAid = null;

    #[ORM\OneToMany(mappedBy: 'amendedAid', targetEntity: self::class)]
    private Collection $aidsAmended;

    #[ORM\Column]
    private ?bool $isAmendment = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $amendmentComment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorOrg = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $subventionRateMin = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $subventionRateMax = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subventionComment = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instructorSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $projectExamples = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $perimeterSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $shortTitle = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Aides France Relance concernant le MTFP. Pour les aides du Plan de relance, utiliser le paramètre programs.slug',
            'enum' => [true, false],
            'example' => true
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column]
    private ?bool $inFranceRelance = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'aidsFromGeneric')]
    private ?self $genericAid = null;

    #[ORM\OneToMany(mappedBy: 'genericAid', targetEntity: self::class)]
    private Collection $aidsFromGeneric;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $localCharacteristics = null;

    #[ORM\ManyToOne(inversedBy: 'aids')]
    private ?DataSource $importDataSource = null;

    #[ORM\ManyToOne(inversedBy: 'aids')]
    private ?EligibilityTest $eligibilityTest = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Aides génériques',
            'enum' => [true, false],
            'example' => true
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column]
    private ?bool $isGeneric = false;

    #[ORM\Column(nullable: true)]
    private ?array $importRawObject = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $loanAmount = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $otherFinancialAidComment = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $recoverableAdvanceAmount = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameInitial = null;

    #[ORM\Column]
    private ?bool $authorNotification = false;

    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectCalendar = null;

    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectTemp = null;

    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectTempCalendar = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $europeanAid = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importDataMention = null;

    #[ORM\Column]
    private ?bool $hasBrokenLink = false;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Aides payantes',
            'enum' => [true, false],
            'example' => true
        ]
    )]
    #[ORM\Column]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?bool $isCharged = false;

    #[ORM\Column]
    private ?bool $importUpdated = false;

    #[ORM\Column(nullable: true)]
    private ?int $dsId = null;

    #[ORM\Column(nullable: true)]
    private ?array $dsMapping = null;

    #[ORM\Column]
    private ?bool $dsSchemaExists = false;

    #[ORM\Column]
    private ?bool $contactInfoUpdated = false;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'La date de publicaiton',
            'example' => '2023-05-30'
        ]
    )]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timePublished = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePublished = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Catégories d\'aides. Il faut passer le slug du (ou des) catégorie(s)',
            'example' => OrganizationType::SLUG_COMMUNE
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'aids')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Keyword::class, inversedBy: 'aids', cascade:['persist'])]
    private Collection $keywords;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Programmes d\'aides. Il faut passer le slug du (ou des) programme(s)',
            'example' => OrganizationType::SLUG_COMMUNE
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: Program::class, inversedBy: 'aids')]
    private Collection $programs;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Porteurs d\'aides. passer seulement l\'id du (ou des) porteur(s) d\'aides suffit',
            'example' => 22
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidFinancer::class, orphanRemoval: true, cascade:['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $aidFinancers;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidInstructor::class, orphanRemoval: true, cascade:['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $aidInstructors;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidProject::class, orphanRemoval: true)]
    private Collection $aidProjects;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidSuggestedAidProject::class)]
    private Collection $aidSuggestedAidProjects;

    #[ORM\ManyToMany(targetEntity: Bundle::class, mappedBy: 'aids', cascade:['persist'])]
    private Collection $bundles;

    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'excludedAids')]
    private Collection $excludedSearchPages;

    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'highlightedAids')]
    private Collection $highlightedSearchPages;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidView::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidViews;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidApplicationUrlClick::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidApplicationUrlClicks;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidOriginUrlClick::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidOriginUrlClicks; 

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidContactClick::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidContactClicks;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidCreatedsFolder::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidCreatedsFolders;

    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidEligibilityTest::class)]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private Collection $logAidEligibilityTests;
    
    /**
     * >Non Database Fields
     */
    private int $nbViews = 0;
    private int $scoreTotal = 0;
    private int $scoreObjects = 0;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $url = null;

    private ArrayCollection $financers;

    private ArrayCollection $instructors;

    #[ORM\ManyToOne(inversedBy: 'aids')]
    #[ORM\JoinColumn(onDelete:'SET NULL')]
    private ?Organization $organization = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $applicationUrlText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originUrlText = null;

    #[ORM\ManyToMany(targetEntity: KeywordReference::class, inversedBy: 'aids', cascade:['persist'])]
    private Collection $keywordReferences;

    #[ORM\ManyToMany(targetEntity: ProjectReference::class, inversedBy: 'aids')]
    private Collection $projectReferences;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCheckBrokenLink = null;

    private ArrayCollection $aidsFromGenericLive;

    /**
     * <Non Database Fields
     */

    public function __construct()
    {
        $this->aidAudiences = new ArrayCollection();
        $this->aidTypes = new ArrayCollection();
        $this->aidDestinations = new ArrayCollection();
        $this->aidSteps = new ArrayCollection();
        $this->aidsAmended = new ArrayCollection();
        $this->aidsFromGeneric = new ArrayCollection();
        $this->aidsFromGenericLive = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->programs = new ArrayCollection();
        $this->aidFinancers = new ArrayCollection();
        $this->aidInstructors = new ArrayCollection();
        $this->projectValidateds = new ArrayCollection();
        $this->aidProjects = new ArrayCollection();
        $this->aidSuggestedAidProjects = new ArrayCollection();
        $this->bundles = new ArrayCollection();
        $this->excludedSearchPages = new ArrayCollection();
        $this->highlightedSearchPages = new ArrayCollection();
        $this->logAidViews = new ArrayCollection();
        $this->logAidApplicationUrlClicks = new ArrayCollection();
        $this->logAidOriginUrlClicks = new ArrayCollection();
        $this->logAidContactClicks = new ArrayCollection();
        $this->logAidCreatedsFolders = new ArrayCollection();
        $this->logAidEligibilityTests = new ArrayCollection();
        $this->financers = new ArrayCollection();
        $this->keywordReferences = new ArrayCollection();
        $this->projectReferences = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOriginUrl(): ?string
    {
        return $this->originUrl;
    }

    public function setOriginUrl(?string $originUrl): static
    {
        $this->originUrl = $originUrl;

        return $this;
    }

    /**
     * @return Collection<int, OrganizationType>
     */
    public function getAidAudiences(): Collection
    {
        return $this->aidAudiences;
    }

    public function addAidAudience(OrganizationType $aidAudience): static
    {
        if (!$this->aidAudiences->contains($aidAudience)) {
            $this->aidAudiences->add($aidAudience);
            // $this->setTimeUpdate(new \DateTime(date('Y-m-d H:i:s')));
        }

        return $this;
    }

    public function removeAidAudience(OrganizationType $aidAudience): static
    {
        $this->aidAudiences->removeElement($aidAudience);

        return $this;
    }

    /**
     * @return Collection<int, AidType>
     */
    public function getAidTypes(): Collection
    {
        return $this->aidTypes;
    }

    public function addAidType(AidType $aidType): static
    {
        if (!$this->aidTypes->contains($aidType)) {
            $this->aidTypes->add($aidType);
        }

        return $this;
    }

    public function removeAidType(AidType $aidType): static
    {
        $this->aidTypes->removeElement($aidType);

        return $this;
    }

    /**
     * @return Collection<int, AidDestination>
     */
    public function getAidDestinations(): Collection
    {
        return $this->aidDestinations;
    }

    public function addAidDestination(AidDestination $aidDestination): static
    {
        if (!$this->aidDestinations->contains($aidDestination)) {
            $this->aidDestinations->add($aidDestination);
        }

        return $this;
    }

    public function removeAidDestination(AidDestination $aidDestination): static
    {
        $this->aidDestinations->removeElement($aidDestination);

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeInterface $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDatePredeposit(): ?\DateTimeInterface
    {
        return $this->datePredeposit;
    }

    public function setDatePredeposit(?\DateTimeInterface $datePredeposit): static
    {
        $this->datePredeposit = $datePredeposit;

        return $this;
    }

    public function getDateSubmissionDeadline(): ?\DateTimeInterface
    {
        return $this->dateSubmissionDeadline;
    }

    public function setDateSubmissionDeadline(?\DateTimeInterface $dateSubmissionDeadline): static
    {
        $this->dateSubmissionDeadline = $dateSubmissionDeadline;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactDetail(): ?string
    {
        return $this->contactDetail;
    }

    public function setContactDetail(?string $contactDetail): static
    {
        $this->contactDetail = $contactDetail;

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
     * @return Collection<int, AidStep>
     */
    public function getAidSteps(): Collection
    {
        return $this->aidSteps;
    }

    public function addAidStep(AidStep $aidStep): static
    {
        if (!$this->aidSteps->contains($aidStep)) {
            $this->aidSteps->add($aidStep);
        }

        return $this;
    }

    public function removeAidStep(AidStep $aidStep): static
    {
        $this->aidSteps->removeElement($aidStep);

        return $this;
    }

    public function getEligibility(): ?string
    {
        return $this->eligibility;
    }

    public function setEligibility(?string $eligibility): static
    {
        $this->eligibility = $eligibility;

        return $this;
    }

    public function getAidRecurrence(): ?AidRecurrence
    {
        return $this->aidRecurrence;
    }

    public function setAidRecurrence(?AidRecurrence $aidRecurrence): static
    {
        $this->aidRecurrence = $aidRecurrence;

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

    public function getApplicationUrl(): ?string
    {
        return $this->applicationUrl;
    }

    public function setApplicationUrl(?string $applicationUrl): static
    {
        $this->applicationUrl = $applicationUrl;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getImportUniqueid(): ?string
    {
        return $this->importUniqueid;
    }

    public function setImportUniqueid(?string $importUniqueid): static
    {
        $this->importUniqueid = $importUniqueid;

        return $this;
    }

    public function getFinancerSuggestion(): ?string
    {
        return $this->financerSuggestion;
    }

    public function setFinancerSuggestion(?string $financerSuggestion): static
    {
        $this->financerSuggestion = $financerSuggestion;

        return $this;
    }

    public function getImportDataUrl(): ?string
    {
        return $this->importDataUrl;
    }

    public function setImportDataUrl(?string $importDataUrl): static
    {
        $this->importDataUrl = $importDataUrl;

        return $this;
    }

    public function getDateImportLastAccess(): ?\DateTimeInterface
    {
        return $this->dateImportLastAccess;
    }

    public function setDateImportLastAccess(?\DateTimeInterface $dateImportLastAccess): static
    {
        $this->dateImportLastAccess = $dateImportLastAccess;

        return $this;
    }

    public function getImportShareLicence(): ?string
    {
        return $this->importShareLicence;
    }

    public function setImportShareLicence(?string $importShareLicence): static
    {
        $this->importShareLicence = $importShareLicence;

        return $this;
    }

    public function isIsCallForProject(): ?bool
    {
        return $this->isCallForProject;
    }

    public function setIsCallForProject(bool $isCallForProject): static
    {
        $this->isCallForProject = $isCallForProject;

        return $this;
    }

    public function getAmendedAid(): ?self
    {
        return $this->amendedAid;
    }

    public function setAmendedAid(?self $amendedAid): static
    {
        $this->amendedAid = $amendedAid;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAidsAmended(): Collection
    {
        return $this->aidsAmended;
    }

    public function addAidsAmended(self $aidsAmended): static
    {
        if (!$this->aidsAmended->contains($aidsAmended)) {
            $this->aidsAmended->add($aidsAmended);
            $aidsAmended->setAmendedAid($this);
        }

        return $this;
    }

    public function removeAidsAmended(self $aidsAmended): static
    {
        if ($this->aidsAmended->removeElement($aidsAmended)) {
            // set the owning side to null (unless already changed)
            if ($aidsAmended->getAmendedAid() === $this) {
                $aidsAmended->setAmendedAid(null);
            }
        }

        return $this;
    }

    public function getAmendmentAuthorEmail(): ?string
    {
        return $this->amendmentAuthorEmail;
    }

    public function setAmendmentAuthorEmail(?string $amendmentAuthorEmail): static
    {
        $this->amendmentAuthorEmail = $amendmentAuthorEmail;

        return $this;
    }

    public function getAmendmentAuthorOrg(): ?string
    {
        return $this->amendmentAuthorOrg;
    }

    public function setAmendmentAuthorOrg(?string $amendmentAuthorOrg): static
    {
        $this->amendmentAuthorOrg = $amendmentAuthorOrg;

        return $this;
    }

    public function getSubventionRateMin(): ?int
    {
        return $this->subventionRateMin;
    }

    public function setSubventionRateMin(?int $subventionRateMin): static
    {
        $this->subventionRateMin = $subventionRateMin;

        return $this;
    }

    public function getSubventionRateMax(): ?int
    {
        return $this->subventionRateMax;
    }

    public function setSubventionRateMax(?int $subventionRateMax): static
    {
        $this->subventionRateMax = $subventionRateMax;

        return $this;
    }

    public function getSubventionComment(): ?string
    {
        return $this->subventionComment;
    }

    public function setSubventionComment(?string $subventionComment): static
    {
        $this->subventionComment = $subventionComment;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getInstructorSuggestion(): ?string
    {
        return $this->instructorSuggestion;
    }

    public function setInstructorSuggestion(?string $instructorSuggestion): static
    {
        $this->instructorSuggestion = $instructorSuggestion;

        return $this;
    }

    public function getProjectExamples(): ?string
    {
        return $this->projectExamples;
    }

    public function setProjectExamples(?string $projectExamples): static
    {
        $this->projectExamples = $projectExamples;

        return $this;
    }

    public function getPerimeterSuggestion(): ?string
    {
        return $this->perimeterSuggestion;
    }

    public function setPerimeterSuggestion(?string $perimeterSuggestion): static
    {
        $this->perimeterSuggestion = $perimeterSuggestion;

        return $this;
    }

    public function getShortTitle(): ?string
    {
        return $this->shortTitle;
    }

    public function setShortTitle(?string $shortTitle): static
    {
        $this->shortTitle = $shortTitle;

        return $this;
    }

    public function isInFranceRelance(): ?bool
    {
        return $this->inFranceRelance;
    }

    public function setInFranceRelance(bool $inFranceRelance): static
    {
        $this->inFranceRelance = $inFranceRelance;

        return $this;
    }

    public function getGenericAid(): ?self
    {
        return $this->genericAid;
    }

    public function setGenericAid(?self $genericAid): static
    {
        $this->genericAid = $genericAid;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAidsFromGeneric(): Collection
    {
        return $this->aidsFromGeneric;
    }

    public function addAidsFromGeneric(self $aidsFromGeneric): static
    {
        if (!$this->aidsFromGeneric->contains($aidsFromGeneric)) {
            $this->aidsFromGeneric->add($aidsFromGeneric);
            $aidsFromGeneric->setGenericAid($this);
        }

        return $this;
    }

    public function removeAidsFromGeneric(self $aidsFromGeneric): static
    {
        if ($this->aidsFromGeneric->removeElement($aidsFromGeneric)) {
            // set the owning side to null (unless already changed)
            if ($aidsFromGeneric->getGenericAid() === $this) {
                $aidsFromGeneric->setGenericAid(null);
            }
        }

        return $this;
    }

    public function getLocalCharacteristics(): ?string
    {
        return $this->localCharacteristics;
    }

    public function setLocalCharacteristics(?string $localCharacteristics): static
    {
        $this->localCharacteristics = $localCharacteristics;

        return $this;
    }

    public function getImportDataSource(): ?DataSource
    {
        return $this->importDataSource;
    }

    public function setImportDataSource(?DataSource $importDataSource): static
    {
        $this->importDataSource = $importDataSource;

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

    public function isIsGeneric(): ?bool
    {
        return $this->isGeneric;
    }

    public function setIsGeneric(bool $isGeneric): static
    {
        $this->isGeneric = $isGeneric;

        return $this;
    }

    public function getImportRawObject(): ?array
    {
        return $this->importRawObject;
    }

    public function setImportRawObject(?array $importRawObject): static
    {
        $this->importRawObject = $importRawObject;

        return $this;
    }

    public function getLoanAmount(): ?int
    {
        return $this->loanAmount;
    }

    public function setLoanAmount(?int $loanAmount): static
    {
        $this->loanAmount = $loanAmount;

        return $this;
    }

    public function getOtherFinancialAidComment(): ?string
    {
        return $this->otherFinancialAidComment;
    }

    public function setOtherFinancialAidComment(?string $otherFinancialAidComment): static
    {
        $this->otherFinancialAidComment = $otherFinancialAidComment;

        return $this;
    }

    public function getRecoverableAdvanceAmount(): ?int
    {
        return $this->recoverableAdvanceAmount;
    }

    public function setRecoverableAdvanceAmount(?int $recoverableAdvanceAmount): static
    {
        $this->recoverableAdvanceAmount = $recoverableAdvanceAmount;

        return $this;
    }

    public function getNameInitial(): ?string
    {
        return $this->nameInitial;
    }

    public function setNameInitial(?string $nameInitial): static
    {
        $this->nameInitial = $nameInitial;

        return $this;
    }

    public function isAuthorNotification(): ?bool
    {
        return $this->authorNotification;
    }

    public function setAuthorNotification(bool $authorNotification): static
    {
        $this->authorNotification = $authorNotification;

        return $this;
    }

    public function getImportRawObjectCalendar(): ?array
    {
        return $this->importRawObjectCalendar;
    }

    public function setImportRawObjectCalendar(?array $importRawObjectCalendar): static
    {
        $this->importRawObjectCalendar = $importRawObjectCalendar;

        return $this;
    }

    public function getImportRawObjectTemp(): ?array
    {
        return $this->importRawObjectTemp;
    }

    public function setImportRawObjectTemp(?array $importRawObjectTemp): static
    {
        $this->importRawObjectTemp = $importRawObjectTemp;

        return $this;
    }

    public function getImportRawObjectTempCalendar(): ?array
    {
        return $this->importRawObjectTempCalendar;
    }

    public function setImportRawObjectTempCalendar(?array $importRawObjectTempCalendar): static
    {
        $this->importRawObjectTempCalendar = $importRawObjectTempCalendar;

        return $this;
    }

    public function getEuropeanAid(): ?string
    {
        return $this->europeanAid;
    }

    public function setEuropeanAid(?string $europeanAid): static
    {
        $this->europeanAid = $europeanAid;

        return $this;
    }

    public function getImportDataMention(): ?string
    {
        return $this->importDataMention;
    }

    public function setImportDataMention(?string $importDataMention): static
    {
        $this->importDataMention = $importDataMention;

        return $this;
    }

    public function isHasBrokenLink(): ?bool
    {
        return $this->hasBrokenLink;
    }

    public function setHasBrokenLink(bool $hasBrokenLink): static
    {
        $this->hasBrokenLink = $hasBrokenLink;

        return $this;
    }

    public function isIsCharged(): ?bool
    {
        return $this->isCharged;
    }

    public function setIsCharged(bool $isCharged): static
    {
        $this->isCharged = $isCharged;

        return $this;
    }

    public function isImportUpdated(): ?bool
    {
        return $this->importUpdated;
    }

    public function setImportUpdated(bool $importUpdated): static
    {
        $this->importUpdated = $importUpdated;

        return $this;
    }

    public function getDsId(): ?int
    {
        return $this->dsId;
    }

    public function setDsId(?int $dsId): static
    {
        $this->dsId = $dsId;

        return $this;
    }

    public function getDsMapping(): ?array
    {
        return $this->dsMapping;
    }

    public function setDsMapping(?array $dsMapping): static
    {
        $this->dsMapping = $dsMapping;

        return $this;
    }

    public function isDsSchemaExists(): ?bool
    {
        return $this->dsSchemaExists;
    }

    public function setDsSchemaExists(bool $dsSchemaExists): static
    {
        $this->dsSchemaExists = $dsSchemaExists;

        return $this;
    }

    public function isContactInfoUpdated(): ?bool
    {
        return $this->contactInfoUpdated;
    }

    public function setContactInfoUpdated(bool $contactInfoUpdated): static
    {
        $this->contactInfoUpdated = $contactInfoUpdated;

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

    public function getTimePublished(): ?\DateTimeInterface
    {
        return $this->timePublished;
    }

    public function setTimePublished(?\DateTimeInterface $timePublished): static
    {
        $this->timePublished = $timePublished;

        return $this;
    }

    public function getAmendmentComment(): ?string
    {
        return $this->amendmentComment;
    }

    public function setAmendmentComment(?string $amendmentComment): static
    {
        $this->amendmentComment = $amendmentComment;

        return $this;
    }

    public function isIsAmendment(): ?bool
    {
        return $this->isAmendment;
    }

    public function setIsAmendment(bool $isAmendment): static
    {
        $this->isAmendment = $isAmendment;

        return $this;
    }

    public function getAmendmentAuthorName(): ?string
    {
        return $this->amendmentAuthorName;
    }

    public function setAmendmentAuthorName(?string $amendmentAuthorName): static
    {
        $this->amendmentAuthorName = $amendmentAuthorName;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            // $this->setTimeUpdate(new \DateTime(date('Y-m-d H:i:s')));
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword): static
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);
        }

        return $this;
    }

    public function removeKeyword(Keyword $keyword): static
    {
        $this->keywords->removeElement($keyword);

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
        }

        return $this;
    }

    public function removeProgram(Program $program): static
    {
        $this->programs->removeElement($program);

        return $this;
    }

    /**
     * @return Collection<int, AidFinancer>
     */
    public function getAidFinancers(): Collection
    {
        return $this->aidFinancers;
    }

    public function addAidFinancer(AidFinancer $aidFinancer): static
    {
        if (!$this->aidFinancers->contains($aidFinancer)) {
            $this->aidFinancers->add($aidFinancer);
            $aidFinancer->setAid($this);
        }

        return $this;
    }

    public function removeAidFinancer(AidFinancer $aidFinancer): static
    {
        if ($this->aidFinancers->removeElement($aidFinancer)) {
            // set the owning side to null (unless already changed)
            if ($aidFinancer->getAid() === $this) {
                $aidFinancer->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AidInstructor>
     */
    public function getAidInstructors(): Collection
    {
        return $this->aidInstructors;
    }

    public function addAidInstructor(AidInstructor $aidInstructor): static
    {
        if (!$this->aidInstructors->contains($aidInstructor)) {
            $this->aidInstructors->add($aidInstructor);
            $aidInstructor->setAid($this);
        }

        return $this;
    }

    public function removeAidInstructor(AidInstructor $aidInstructor): static
    {
        if ($this->aidInstructors->removeElement($aidInstructor)) {
            // set the owning side to null (unless already changed)
            if ($aidInstructor->getAid() === $this) {
                $aidInstructor->setAid(null);
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
            $projectValidated->setAid($this);
        }

        return $this;
    }

    public function removeProjectValidated(ProjectValidated $projectValidated): static
    {
        if ($this->projectValidateds->removeElement($projectValidated)) {
            // set the owning side to null (unless already changed)
            if ($projectValidated->getAid() === $this) {
                $projectValidated->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AidProject>
     */
    public function getAidProjects(): Collection
    {
        return $this->aidProjects;
    }

    public function addAidProject(AidProject $aidProject): static
    {
        if (!$this->aidProjects->contains($aidProject)) {
            $this->aidProjects->add($aidProject);
            $aidProject->setAid($this);
        }

        return $this;
    }

    public function removeAidProject(AidProject $aidProject): static
    {
        if ($this->aidProjects->removeElement($aidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidProject->getAid() === $this) {
                $aidProject->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AidSuggestedAidProject>
     */
    public function getAidSuggestedAidProjects(): Collection
    {
        return $this->aidSuggestedAidProjects;
    }

    public function addAidSuggestedAidProject(AidSuggestedAidProject $aidSuggestedAidProject): static
    {
        if (!$this->aidSuggestedAidProjects->contains($aidSuggestedAidProject)) {
            $this->aidSuggestedAidProjects->add($aidSuggestedAidProject);
            $aidSuggestedAidProject->setAid($this);
        }

        return $this;
    }

    public function removeAidSuggestedAidProject(AidSuggestedAidProject $aidSuggestedAidProject): static
    {
        if ($this->aidSuggestedAidProjects->removeElement($aidSuggestedAidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidSuggestedAidProject->getAid() === $this) {
                $aidSuggestedAidProject->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Bundle>
     */
    public function getBundles(): Collection
    {
        return $this->bundles;
    }

    public function addBundle(Bundle $bundle): static
    {
        if (!$this->bundles->contains($bundle)) {
            $this->bundles->add($bundle);
            $bundle->addAid($this);
        }

        return $this;
    }

    public function removeBundle(Bundle $bundle): static
    {
        if ($this->bundles->removeElement($bundle)) {
            $bundle->removeAid($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchPage>
     */
    public function getExcludedSearchPages(): Collection
    {
        return $this->excludedSearchPages;
    }

    public function addExcludedSearchPage(SearchPage $excludedSearchPage): static
    {
        if (!$this->excludedSearchPages->contains($excludedSearchPage)) {
            $this->excludedSearchPages->add($excludedSearchPage);
            $excludedSearchPage->addExcludedAid($this);
        }

        return $this;
    }

    public function removeExcludedSearchPage(SearchPage $excludedSearchPage): static
    {
        if ($this->excludedSearchPages->removeElement($excludedSearchPage)) {
            $excludedSearchPage->removeExcludedAid($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchPage>
     */
    public function getHighlightedSearchPages(): Collection
    {
        return $this->highlightedSearchPages;
    }

    public function addHighlightedSearchPage(SearchPage $highlightedSearchPage): static
    {
        if (!$this->highlightedSearchPages->contains($highlightedSearchPage)) {
            $this->highlightedSearchPages->add($highlightedSearchPage);
            $highlightedSearchPage->addHighlightedAid($this);
        }

        return $this;
    }

    public function removeHighlightedSearchPage(SearchPage $highlightedSearchPage): static
    {
        if ($this->highlightedSearchPages->removeElement($highlightedSearchPage)) {
            $highlightedSearchPage->removeHighlightedAid($this);
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
            $logAidView->setAid($this);
        }

        return $this;
    }

    public function removeLogAidView(LogAidView $logAidView): static
    {
        if ($this->logAidViews->removeElement($logAidView)) {
            // set the owning side to null (unless already changed)
            if ($logAidView->getAid() === $this) {
                $logAidView->setAid(null);
            }
        }

        return $this;
    }


    /******************************
     * SPECIFIC
     */

    public function isApproachingDeadline() : bool {
        if (!$this->getDateSubmissionDeadline()) {
            return false;
        }

        try {
            $today = new \DateTime(date('Y-m-d'));
            $interval = $this->getDateSubmissionDeadline()->diff($today);

            if ($interval->days <= self::APPROACHING_DEADLINE_DELTA) {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    public function getDaysBeforeDeadline() : int {
        if (!$this->getDateSubmissionDeadline()) {
            return 0;
        }

        try {
            $today = new \DateTime(date('Y-m-d'));
            $interval = $this->getDateSubmissionDeadline()->diff($today);

            return $interval->days;
        } catch (\Exception $e) {
        }

        return 0;
    }

    public function isFinancial(): bool
    {
        foreach ($this->getAidTypes() as $aidType) {
            if (in_array($aidType->getSlug(), AidType::TYPE_FINANCIAL_SLUGS)) {
                return true;
            }
        }
        return false;
    }

    public function isTechnical(): bool
    {
        foreach ($this->getAidTypes() as $aidType) {
            if (in_array($aidType->getSlug(), AidType::TYPE_TECHNICAL_SLUG)) {
                return true;
            }
        }
        return false;
    }

    public function isGrant(): bool
    {
        foreach ($this->getAidTypes() as $aidType) {
            if ($aidType->getSlug() == AidType::SLUG_GRANT) {
                return true;
            }
        }
        return false;
    }


    public function isLoan(): bool
    {
        foreach ($this->getAidTypes() as $aidType) {
            if ($aidType->getSlug() == AidType::SLUG_LOAN) {
                return true;
            }
        }
        return false;
    }

    public function isLive(): bool
    {
        $today = new \DateTime(date('Y-m-d'));
        // return $this->dateStart > $today;
        if (
            $this->status == self::STATUS_PUBLISHED
            && (($this->dateStart && $this->dateStart <= $today) || !$this->dateStart)
            && (($this->dateSubmissionDeadline && $this->dateSubmissionDeadline >= $today) || !$this->dateSubmissionDeadline)
        ) {
            return true;
        }

        return false;
    }

    public function isOnGoing(): bool
    {
        if ($this->getAidRecurrence()) {
            if ($this->aidRecurrence->getSlug() == AidRecurrence::SLUG_ONGOING) {
                return true;
            }
        }

        return false;
    }

    public function isRecurring(): bool
    {
        if ($this->getAidRecurrence()) {
            if ($this->aidRecurrence->getSlug() == AidRecurrence::SLUG_RECURRING) {
                return true;
            }
        }

        return false;
    }

    public function isDraft(): bool
    {
        if ($this->status == Aid::STATUS_DRAFT) {
            return true;
        }

        return false;
    }

    public function isPublished(): bool
    {
        if ($this->status == Aid::STATUS_PUBLISHED) {
            return true;
        }

        return false;
    }

    public function isLocal(): bool
    {
        if ($this->genericAid !== null) {
            return true;
        }

        return false;
    }

    public function  hasCalendar() : bool 
    {
        if ($this->isOnGoing()) {
            return false;
        }

        if ($this->dateStart || $this->datePredeposit || $this->dateSubmissionDeadline) {
            return true;
        }

        return false;
    }

    public function isComingSoon()
    {
        if (!$this->dateStart) {
            return false;
        }

        $today = new \DateTime(date('Y-m-d'));
        return $this->dateStart > $today;
    }

    public function hasExpired()
    {
        if (!$this->dateSubmissionDeadline) {
            return false;
        }

        $today = new \DateTime(date('Y-m-d'));
        return $this->dateSubmissionDeadline < $today;
    }
    public function getFinancersPublicOrCorporate(): string
    {
        $corporate=false;
        $public=false;
        foreach($this->getAidFinancers() as $aidFinancers){
            if($aidFinancers->getBacker() && $aidFinancers->getBacker()->isIsCorporate()){
                $corporate=true;
            }else{
                $public=true;
            }
        }

        if($corporate && $public){
            return 'PORTEURS D\'AIDE PUBLIC ET PRIVÉ';
        }elseif($corporate && !$public){
            return 'PORTEUR D\'AIDE PRIVÉ';
        }elseif(!$corporate && $public){
            return 'PORTEUR D\'AIDE PUBLIC';
        }else{
            return '';
        }

    }

    public function getFinancers() : ArrayCollection
    {
        $financers = new ArrayCollection();
        foreach ($this->getAidFinancers() as $aidFinancer) {
            $financers->add($aidFinancer->getBacker());
        }
        return $financers;    
    }

    public function getInstructors() : ArrayCollection
    {
        $instructors = new ArrayCollection();
        foreach ($this->getAidInstructors() as $aidInstructor) {
            $instructors->add($aidInstructor->getBacker());
        }
        return $instructors;    
    }

    public function getNbViews() : int {
        return $this->nbViews;
    }

    public function setNbViews(int $nbViews) : static {
        $this->nbViews = $nbViews;
        return $this;
    }

    /**
     * @return Collection<int, LogAidApplicationUrlClick>
     */
    public function getLogAidApplicationUrlClicks(): Collection
    {
        return $this->logAidApplicationUrlClicks;
    }

    public function addLogAidApplicationUrlClick(LogAidApplicationUrlClick $logAidApplicationUrlClick): static
    {
        if (!$this->logAidApplicationUrlClicks->contains($logAidApplicationUrlClick)) {
            $this->logAidApplicationUrlClicks->add($logAidApplicationUrlClick);
            $logAidApplicationUrlClick->setAid($this);
        }

        return $this;
    }

    public function removeLogAidApplicationUrlClick(LogAidApplicationUrlClick $logAidApplicationUrlClick): static
    {
        if ($this->logAidApplicationUrlClicks->removeElement($logAidApplicationUrlClick)) {
            // set the owning side to null (unless already changed)
            if ($logAidApplicationUrlClick->getAid() === $this) {
                $logAidApplicationUrlClick->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidOriginUrlClick>
     */
    public function getLogAidOriginUrlClicks(): Collection
    {
        return $this->logAidOriginUrlClicks;
    }

    public function addLogAidOriginUrlClick(LogAidOriginUrlClick $logAidOriginUrlClick): static
    {
        if (!$this->logAidOriginUrlClicks->contains($logAidOriginUrlClick)) {
            $this->logAidOriginUrlClicks->add($logAidOriginUrlClick);
            $logAidOriginUrlClick->setAid($this);
        }

        return $this;
    }

    public function removeLogAidOriginUrlClick(LogAidOriginUrlClick $logAidOriginUrlClick): static
    {
        if ($this->logAidOriginUrlClicks->removeElement($logAidOriginUrlClick)) {
            // set the owning side to null (unless already changed)
            if ($logAidOriginUrlClick->getAid() === $this) {
                $logAidOriginUrlClick->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAidContactClick>
     */
    public function getLogAidContactClicks(): Collection
    {
        return $this->logAidContactClicks;
    }

    public function addLogAidContactClick(LogAidContactClick $logAidContactClick): static
    {
        if (!$this->logAidContactClicks->contains($logAidContactClick)) {
            $this->logAidContactClicks->add($logAidContactClick);
            $logAidContactClick->setAid($this);
        }

        return $this;
    }

    public function removeLogAidContactClick(LogAidContactClick $logAidContactClick): static
    {
        if ($this->logAidContactClicks->removeElement($logAidContactClick)) {
            // set the owning side to null (unless already changed)
            if ($logAidContactClick->getAid() === $this) {
                $logAidContactClick->setAid(null);
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
            $logAidCreatedsFolder->setAid($this);
        }

        return $this;
    }

    public function removeLogAidCreatedsFolder(LogAidCreatedsFolder $logAidCreatedsFolder): static
    {
        if ($this->logAidCreatedsFolders->removeElement($logAidCreatedsFolder)) {
            // set the owning side to null (unless already changed)
            if ($logAidCreatedsFolder->getAid() === $this) {
                $logAidCreatedsFolder->setAid(null);
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
            $logAidEligibilityTest->setAid($this);
        }

        return $this;
    }

    public function removeLogAidEligibilityTest(LogAidEligibilityTest $logAidEligibilityTest): static
    {
        if ($this->logAidEligibilityTests->removeElement($logAidEligibilityTest)) {
            // set the owning side to null (unless already changed)
            if ($logAidEligibilityTest->getAid() === $this) {
                $logAidEligibilityTest->setAid(null);
            }
        }

        return $this;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function setUrl(?string $url): static {
        $this->url = $url;
        return $this;
    }




    public function __toString(): string
    {
        return $this->name ?? 'Aide';
    }

    public function getDatePublished(): ?\DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(?\DateTimeInterface $datePublished): static
    {
        $this->datePublished = $datePublished;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getApplicationUrlText(): ?string
    {
        return $this->applicationUrlText;
    }

    public function setApplicationUrlText(?string $applicationUrlText): static
    {
        $this->applicationUrlText = $applicationUrlText;

        return $this;
    }

    public function getOriginUrlText(): ?string
    {
        return $this->originUrlText;
    }

    public function setOriginUrlText(?string $originUrlText): static
    {
        $this->originUrlText = $originUrlText;

        return $this;
    }

    /**
     * @return Collection<int, KeywordReference>
     */
    public function getKeywordReferences(): Collection
    {
        return $this->keywordReferences;
    }

    public function addKeywordReference(KeywordReference $keywordReference): static
    {
        if (!$this->keywordReferences->contains($keywordReference)) {
            $this->keywordReferences->add($keywordReference);
        }

        return $this;
    }

    public function removeKeywordReference(KeywordReference $keywordReference): static
    {
        $this->keywordReferences->removeElement($keywordReference);

        return $this;
    }

    /**
     * @return Collection<int, ProjectReference>
     */
    public function getProjectReferences(): Collection
    {
        return $this->projectReferences;
    }

    public function addProjectReference(ProjectReference $projectReference): static
    {
        if (!$this->projectReferences->contains($projectReference)) {
            $this->projectReferences->add($projectReference);
        }

        return $this;
    }

    public function removeProjectReference(ProjectReference $projectReference): static
    {
        $this->projectReferences->removeElement($projectReference);

        return $this;
    }

    public function getScoreTotal(): ?float
    {
        return $this->scoreTotal;
    }

    public function setScoreTotal(?float $scoreTotal): static
    {
        $this->scoreTotal = $scoreTotal;

        return $this;
    }

    public function getScoreObjects(): ?float
    {
        return $this->scoreObjects;
    }

    public function setScoreObjects(?float $scoreObjects): static
    {
        $this->scoreObjects = $scoreObjects;

        return $this;
    }

    public function getDateCheckBrokenLink(): ?\DateTimeInterface
    {
        return $this->dateCheckBrokenLink;
    }

    public function setDateCheckBrokenLink(?\DateTimeInterface $dateCheckBrokenLink): static
    {
        $this->dateCheckBrokenLink = $dateCheckBrokenLink;

        return $this;
    }
    
    public function getAidsFromGenericLive()
    {
        $aids = new ArrayCollection();
        foreach ($this->getAidsFromGeneric() as $aid) {
            if ($aid->isLive()) {
                $aids->add($aid);
            }
        }
        return $aids;
    }
    
}
