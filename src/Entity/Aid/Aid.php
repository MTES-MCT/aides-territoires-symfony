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
use App\Entity\Reference\KeywordReferenceSuggested;
use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceMissing;
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
use App\Entity\Backer\Backer;
use App\Filter\Aid\AidApplyBeforeFilter;
use App\Filter\Aid\AidCallForProjectOnlyFilter;
use App\Filter\Aid\AidCategorySlugsFilter;
use App\Filter\Aid\AidCategoryIdsFilter;
use App\Filter\Aid\AidDestinationIdsFilter;
use App\Filter\Aid\AidDestinationSlugsFilter;
use App\Filter\Aid\AidEuropeanSlugFilter;
use App\Filter\Aid\AidIsChargedFilter;
use App\Filter\Aid\AidOrganizationTypeIdsFilter;
use App\Filter\Aid\AidProjectReferenceFilter;
use App\Filter\Aid\AidPublishedAfterFilter;
use App\Filter\Aid\AidRecurrenceIdFilter;
use App\Filter\Aid\AidRecurrenceSlugFilter;
use App\Filter\Aid\AidStepIdsFilter;
use App\Filter\Aid\AidStepSlugsFilter;
use App\Filter\Aid\AidOrganizationTypeSlugsFilter;
use App\Filter\Aid\AidPerimeterIdFilter;
use App\Filter\Aid\AidTextFilter;
use App\Filter\Aid\AidTypeSlugsFilter;
use App\Filter\Aid\AidTypeGroupSlugFilter;
use App\Filter\Aid\AidTypeGroupIdFilter;
use App\Filter\Aid\AidTypeIdsFilter;
use App\Filter\Backer\BackerIdsFilter;
use App\Filter\Backer\BackerGroupIdFilter;
use App\Service\Doctrine\DoctrineConstants;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AidRepository::class)]
#[ORM\Index(columns: ['status'], name: 'status_aid')]
#[ORM\Index(columns: ['slug'], name: 'slug_aid')]
#[ORM\Index(columns: ['date_start'], name: 'date_start_aid')]
#[ORM\Index(columns: ['date_submission_deadline'], name: 'date_submission_deadline_aid')]
#[ORM\Index(columns: ['status', 'date_submission_deadline'], name: 'status_date_submission_deadline_aid')]
#[ORM\Index(columns: ['date_published'], name: 'date_published_aid')]
#[ORM\Index(columns: ['date_check_broken_link'], name: 'date_check_broken_link_aid')]
#[ORM\Index(columns: ['european_aid'], name: 'european_aid_aid')]
#[ORM\Index(columns: ['origin_url'], name: 'origin_url_aid')]
#[ORM\Index(columns: ['import_uniqueid'], name: 'import_uniqueid_aid')]
#[ORM\Index(columns: ['is_generic'], name: 'is_generic_aid')]
#[ORM\Index(columns: ['name'], name: 'name_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['name_initial'], name: 'name_initial_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['description'], name: 'description_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['eligibility'], name: 'eligibility_aid_fulltext', flags: ['fulltext'])]
#[ORM\Index(columns: ['name', 'description', 'eligibility'], name: 'synonym_aid_nde_fulltext', flags: ['fulltext'])]
#[ORM\Index(
    columns: ['description', 'eligibility', 'project_examples'],
    name: 'synonym_aid_fulltext',
    flags: ['fulltext']
)]
#[ORM\Index(
    columns: ['name', 'name_initial', 'description', 'eligibility', 'project_examples'],
    name: 'synonym_aid_all_fulltext',
    flags: ['fulltext']
)]
#[ApiResource(
    shortName: 'aid',
    operations: [
        new GetCollection(
            name: self::API_OPERATION_GET_COLLECTION_PUBLISHED,
            uriTemplate: '/aids/',
            controller: AidAidController::class,
            normalizationContext: ['groups' => self::API_GROUP_LIST],
            openapi: new Model\Operation(
                summary: 'Lister toutes les aides actuellement publiées',
                description: 'Lister toutes les aides actuellement publiées',
                tags: [self::API_TAG]
            )
        ),
        new Get(
            name: self::API_OPERATION_GET_BY_SLUG,
            normalizationContext: ['groups' => self::API_GROUP_ITEM],
            uriTemplate: '/aids/{slug}/',
            controller: AidAidController::class,
            openapi: new Model\Operation(
                summary: 'Retrouver une aide par slug',
                tags: [self::API_TAG]
            )
        ),
        new Get(
            name: self::API_OPERATION_GET_BY_ID,
            normalizationContext: ['groups' => self::API_GROUP_ITEM],
            uriTemplate: '/aids/by-id/{id}/',
            controller: AidAidController::class,
            openapi: new Model\Operation(
                summary: 'Retrouver une aide par son identifiant',
                tags: [self::API_TAG]
            )
        ),
    ],
    order: ['dateStart' => 'DESC', 'id' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    paginationClientItemsPerPage: true
)]

#[ApiFilter(AidTextFilter::class)]
#[ApiFilter(AidOrganizationTypeSlugsFilter::class)]
#[ApiFilter(AidOrganizationTypeIdsFilter::class)]
#[ApiFilter(AidCategorySlugsFilter::class)]
#[ApiFilter(AidCategoryIdsFilter::class)]
#[ApiFilter(AidApplyBeforeFilter::class)]
#[ApiFilter(AidPublishedAfterFilter::class)]
#[ApiFilter(AidTypeGroupSlugFilter::class)]
#[ApiFilter(AidTypeGroupIdFilter::class)]
#[ApiFilter(AidTypeSlugsFilter::class)]
#[ApiFilter(AidTypeIdsFilter::class)]
#[ApiFilter(AidStepSlugsFilter::class)]
#[ApiFilter(AidStepIdsFilter::class)]
#[ApiFilter(AidDestinationSlugsFilter::class)]
#[ApiFilter(AidDestinationIdsFilter::class)]
#[ApiFilter(AidRecurrenceSlugFilter::class)]
#[ApiFilter(AidRecurrenceIdFilter::class)]
#[ApiFilter(AidCallForProjectOnlyFilter::class)]
#[ApiFilter(AidIsChargedFilter::class)]
#[ApiFilter(AidPerimeterIdFilter::class)]
#[ApiFilter(AidProjectReferenceFilter::class)]
#[ApiFilter(AidEuropeanSlugFilter::class)]
#[ApiFilter(BackerIdsFilter::class)]
#[ApiFilter(BackerGroupIdFilter::class)]
class Aid // NOSONAR too much methods
{
    public const API_OPERATION_GET_BY_ID = 'api_aid_get_by_id';
    public const API_OPERATION_GET_BY_SLUG = 'api_aid_get_by_slug';
    public const API_OPERATION_GET_COLLECTION_PUBLISHED = 'api_aids_published';
    public const API_OPERATION_GET_COLLECTION_ALL = 'api_aids_all';
    public const API_TAG = 'Aides';

    public const API_GROUP_LIST = 'aid:list';
    public const API_GROUP_ITEM = 'aid:item';

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_MERGED = 'merged';
    public const STATUS_REVIEWABLE = 'reviewable';

    public const STATUSES = [
        ['slug' => self::STATUS_PUBLISHED, 'name' => 'Publiée'],
        ['slug' => self::STATUS_DELETED, 'name' => 'Supprimée'],
        ['slug' => self::STATUS_DRAFT, 'name' => 'Brouillon'],
        ['slug' => self::STATUS_MERGED, 'name' => 'Mergé'],
        ['slug' => self::STATUS_REVIEWABLE, 'name' => 'En revue'],
    ];

    public const APPROACHING_DEADLINE_DELTA = 30;  # days

    public const SLUG_EUROPEAN = 'european';
    public const SLUG_EUROPEAN_SECTORIAL = 'sectorial';
    public const SLUG_EUROPEAN_ORGANIZATIONAL = 'organizational';
    public const LABELS_EUROPEAN = [
        self::SLUG_EUROPEAN => 'Aides européennes',
        self::SLUG_EUROPEAN_SECTORIAL => 'Aides européennes sectorielles',
        self::SLUG_EUROPEAN_ORGANIZATIONAL => 'Aides européennes structurelles',
    ];

    public const MAX_NB_EXPORT_PDF = 20;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false)]
    private ?int $id = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $description = null;

    #[Assert\Length(max: 16)]
    #[ORM\Column(length: 16)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $status = null;

    #[Assert\Url()]
    #[Assert\Length(max: 700, maxMessage: 'Le lien ne doit pas dépasser {{ limit }} caractères')]
    #[ApiProperty(
        openapiContext: [
            'description' => 'Lien vers plus d\'informations',
            'example' => 'https://appelsaprojets.ademe.fr/aap/AURASTC2019-54'
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 700, nullable: true)]
    private ?string $originUrl = null;

    /**
     * @var Collection<int, OrganizationType>
     */
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

    /**
     * @var Collection<int, AidType>
     */
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

    /**
     * @var Collection<int, AidDestination>
     */
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

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[Assert\Length(max: 35)]
    #[ORM\Column(length: 35, nullable: true)]
    private ?string $contactPhone = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactDetail = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\ManyToOne(inversedBy: 'aids')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?User $author = null;

    /**
     * @var Collection<int, AidStep>
     */
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

    #[Assert\Length(max: 700, maxMessage: 'Le lien ne doit pas dépasser {{ limit }} caractères')]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 700, nullable: true)]
    private ?string $applicationUrl = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ApiProperty(identifier: true)]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    #[Gedmo\Slug(fields: ['name'], updatable: false)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $isImported = false;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importUniqueid = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $financerSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $importDataUrl = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateImportLastAccess = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 50)]
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

    /**
     * @var Collection<int, Aid>
     */
    #[ORM\OneToMany(mappedBy: 'amendedAid', targetEntity: self::class)]
    private Collection $aidsAmended;

    #[ORM\Column]
    private ?bool $isAmendment = false;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $amendmentComment = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorEmail = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amendmentAuthorOrg = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $subventionRateMin = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $subventionRateMax = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subventionComment = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contact = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instructorSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $projectExamples = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $perimeterSuggestion = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 64)]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $shortTitle = null;

    #[ApiProperty(
        openapiContext: [
            'description' => 'Aides France Relance concernant le MTFP. '
                . 'Pour les aides du Plan de relance, utiliser le paramètre programs.slug',
            'enum' => [true, false],
            'example' => true
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column]
    private ?bool $inFranceRelance = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'aidsFromGeneric')]
    private ?self $genericAid = null;

    /**
     * @var Collection<int, Aid>
     */
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

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $importRawObject = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $loanAmount = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $otherFinancialAidComment = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(nullable: true)]
    private ?int $recoverableAdvanceAmount = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameInitial = null;

    #[ORM\Column]
    private ?bool $authorNotification = false;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectCalendar = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectTemp = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $importRawObjectTempCalendar = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $europeanAid = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[Assert\Length(max: 255)]
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

    /**
     * @var string[]|null
     */
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

    /**
     * @var Collection<int, Category>
     */
    #[ApiProperty(
        openapiContext: [
            'description' => 'Catégories d\'aides. Il faut passer le slug du (ou des) catégorie(s)',
            'example' => OrganizationType::SLUG_COMMUNE
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'aids')]
    private Collection $categories;

    /**
     * @var Collection<int, Keyword>
     */
    #[ORM\ManyToMany(targetEntity: Keyword::class, inversedBy: 'aids', cascade: ['persist'])]
    private Collection $keywords;

    /**
     * @var Collection<int, Program>
     */
    #[ApiProperty(
        openapiContext: [
            'description' => 'Programmes d\'aides. Il faut passer le slug du (ou des) programme(s)',
            'example' => OrganizationType::SLUG_COMMUNE
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: Program::class, inversedBy: 'aids')]
    private Collection $programs;


    /**
     * @var Collection<int, AidFinancer>
     */
    #[ApiProperty(
        openapiContext: [
            'description' => 'Porteurs d\'aides. passer seulement l\'id du (ou des) porteur(s) d\'aides suffit',
            'example' => 22
        ]
    )]
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidFinancer::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $aidFinancers;

    /**
     * @var Collection<int, AidInstructor>
     */
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidInstructor::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $aidInstructors;

    /**
     * @var Collection<int, ProjectValidated>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: ProjectValidated::class)]
    private Collection $projectValidateds;

    /**
     * @var Collection<int, AidProject>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidProject::class, orphanRemoval: true)]
    private Collection $aidProjects;

    /**
     * @var Collection<int, AidSuggestedAidProject>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidSuggestedAidProject::class)]
    private Collection $aidSuggestedAidProjects;

    /**
     * @var Collection<int, Bundle>
     */
    #[ORM\ManyToMany(targetEntity: Bundle::class, mappedBy: 'aids', cascade: ['persist'])]
    private Collection $bundles;

    /**
     * @var Collection<int, SearchPage>
     */
    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'excludedAids')]
    private Collection $excludedSearchPages;

    /**
     * @var Collection<int, SearchPage>
     */
    #[ORM\ManyToMany(targetEntity: SearchPage::class, mappedBy: 'highlightedAids')]
    private Collection $highlightedSearchPages;

    /**
     * @var Collection<int, LogAidView>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidView::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidViews;

    /**
     * @var Collection<int, LogAidApplicationUrlClick>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidApplicationUrlClick::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidApplicationUrlClicks;

    /**
     * @var Collection<int, LogAidOriginUrlClick>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidOriginUrlClick::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidOriginUrlClicks;

    /**
     * @var Collection<int, LogAidContactClick>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidContactClick::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidContactClicks;

    /**
     * @var Collection<int, LogAidCreatedsFolder>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidCreatedsFolder::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidCreatedsFolders;

    /**
     * @var Collection<int, LogAidEligibilityTest>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: LogAidEligibilityTest::class)]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private Collection $logAidEligibilityTests;

    /**
     * >Non Database Fields
     */
    private int $nbViews = 0;
    private int $scoreTotal = 0;
    private int $scoreObjects = 0;
    /** @var ArrayCollection<int, ProjectReference>|null */
    private ?ArrayCollection $projectReferencesSearched = null;

    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    private ?string $url = null;

    /** @var ArrayCollection<int, Backer> */
    private ArrayCollection $financers;

    /** @var ArrayCollection<int, Backer> */
    private ArrayCollection $instructors;

    /** @var array<int, ProjectReference> */
    private array $projectReferencesSuggestions = [];

    #[ORM\ManyToOne(inversedBy: 'aids')]
    #[ORM\JoinColumn(onDelete: DoctrineConstants::SET_NULL)]
    private ?Organization $organization = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $applicationUrlText = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originUrlText = null;

    /**
     * @var Collection<int, KeywordReference>
     */
    #[ORM\ManyToMany(targetEntity: KeywordReference::class, inversedBy: 'aids', cascade: ['persist'])]
    private Collection $keywordReferences;

    /**
     * @var Collection<int, ProjectReference>
     */
    #[Groups([self::API_GROUP_LIST, self::API_GROUP_ITEM])]
    #[ORM\ManyToMany(targetEntity: ProjectReference::class, inversedBy: 'aids')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $projectReferences;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCheckBrokenLink = null;

    /**
     * @var ArrayCollection<int, Aid>
     */
    private ArrayCollection $aidsFromGenericLive;

    /**
     * @var Collection<int, AidLock>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: AidLock::class, orphanRemoval: true)]
    private Collection $aidLocks;

    /**
     * @var Collection<int, KeywordReferenceSuggested>
     */
    #[ORM\OneToMany(mappedBy: 'aid', targetEntity: KeywordReferenceSuggested::class, orphanRemoval: true)]
    private Collection $keywordReferenceSuggesteds;

    /**
     * @var Collection<int, SanctuarizedField>
     */
    #[ORM\ManyToMany(targetEntity: SanctuarizedField::class, mappedBy: 'aids')]
    private Collection $sanctuarizedFields;

    /**
     * @var string[]|null $importDatas
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $importDatas = null;

    #[Groups([self::API_GROUP_ITEM])]
    private bool $live = false;

    /**
     * @var Collection<int, ProjectReferenceMissing>
     */
    #[ORM\ManyToMany(targetEntity: ProjectReferenceMissing::class, mappedBy: 'aids', cascade: ['persist'])]
    private Collection $projectReferenceMissings;

    #[ORM\ManyToOne(inversedBy: 'lastEditedAids')]
    private ?User $lastEditor = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $privateEdition = false;

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
        $this->aidLocks = new ArrayCollection();
        $this->keywordReferenceSuggesteds = new ArrayCollection();
        $this->projectReferencesSearched = new ArrayCollection();
        $this->sanctuarizedFields = new ArrayCollection();
        $this->projectReferenceMissings = new ArrayCollection();
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
        }

        return $this;
    }

    public function removeAidAudience(OrganizationType $aidAudience): static
    {
        $this->aidAudiences->removeElement($aidAudience);

        return $this;
    }

    /**
     * @param Collection<int, OrganizationType> $aidAudiences
     * @return static
     */
    public function setAidAudiences(Collection $aidAudiences): static
    {
        $this->aidAudiences = $aidAudiences;

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
     * @param Collection<int, AidType> $aidTypes
     * @return static
     */
    public function setAidTypes(Collection $aidTypes): static
    {
        $this->aidTypes = $aidTypes;

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

    /**
     * @param Collection<int, AidDestination> $aidDestinations
     * @return static
     */
    public function setAidDestinations(Collection $aidDestinations): static
    {
        $this->aidDestinations = $aidDestinations;

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

    /**
     * @param Collection<int, AidStep> $aidSteps
     * @return static
     */
    public function setAidSteps(Collection $aidSteps): static
    {
        $this->aidSteps = $aidSteps;

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
     * @return Collection<int, Aid>
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
        if ($this->aidsAmended->removeElement($aidsAmended) && $aidsAmended->getAmendedAid() === $this) {
            $aidsAmended->setAmendedAid(null);
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
     * @return Collection<int, Aid>
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
        if ($this->aidsFromGeneric->removeElement($aidsFromGeneric) && $aidsFromGeneric->getGenericAid() === $this) {
            $aidsFromGeneric->setGenericAid(null);
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

    /**
     * @return string[]|null
     */
    public function getImportRawObject(): ?array
    {
        return $this->importRawObject;
    }

    /**
     * @param string[]|null $importRawObject
     * @return static
     */
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

    /**
     * @return string[]|null
     */
    public function getImportRawObjectCalendar(): ?array
    {
        return $this->importRawObjectCalendar;
    }

    /**
     * @param string[]|null $importRawObjectCalendar
     * @return static
     */
    public function setImportRawObjectCalendar(?array $importRawObjectCalendar): static
    {
        $this->importRawObjectCalendar = $importRawObjectCalendar;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getImportRawObjectTemp(): ?array
    {
        return $this->importRawObjectTemp;
    }

    /**
     * @param string[]|null $importRawObjectTemp
     * @return static
     */
    public function setImportRawObjectTemp(?array $importRawObjectTemp): static
    {
        $this->importRawObjectTemp = $importRawObjectTemp;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getImportRawObjectTempCalendar(): ?array
    {
        return $this->importRawObjectTempCalendar;
    }

    /**
     * @param string[]|null $importRawObjectTempCalendar
     * @return static
     */
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

    /**
     * @return string[]|null
     */
    public function getDsMapping(): ?array
    {
        return $this->dsMapping;
    }

    /**
     * @param string[]|null $dsMapping
     * @return static
     */
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
        }

        return $this;
    }

    /**
     * @param Collection<int, Category> $categories
     * @return static
     */
    public function setCategories(Collection $categories): static
    {
        $this->categories = $categories;

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

    /**
     * @param Collection<int, Program> $programs
     * @return static
     */
    public function setPrograms(Collection $programs): static
    {
        $this->programs = $programs;

        return $this;
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

        $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        return $this;
    }

    public function removeAidFinancer(AidFinancer $aidFinancer): static
    {
        if ($this->aidFinancers->removeElement($aidFinancer) && $aidFinancer->getAid() === $this) {
            $aidFinancer->setAid(null);
        }

        $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
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

        $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
        return $this;
    }

    public function removeAidInstructor(AidInstructor $aidInstructor): static
    {
        if ($this->aidInstructors->removeElement($aidInstructor) && $aidInstructor->getAid() === $this) {
            $aidInstructor->setAid(null);
        }

        $this->timeUpdate = new \DateTime(date('Y-m-d H:i:s'));
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
        if ($this->projectValidateds->removeElement($projectValidated) && $projectValidated->getAid() === $this) {
            $projectValidated->setAid(null);
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
        if ($this->aidProjects->removeElement($aidProject) && $aidProject->getAid() === $this) {
            $aidProject->setAid(null);
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
        if (
            $this->aidSuggestedAidProjects->removeElement($aidSuggestedAidProject)
            && $aidSuggestedAidProject->getAid() === $this
        ) {
            $aidSuggestedAidProject->setAid(null);
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
        if ($this->logAidViews->removeElement($logAidView) && $logAidView->getAid() === $this) {
            $logAidView->setAid(null);
        }

        return $this;
    }


    /******************************
     * SPECIFIC
     */

    public function isApproachingDeadline(): bool
    {
        $result = false;

        if ($this->getDateSubmissionDeadline()) {
            $today = new \DateTime(date('Y-m-d'));
            $interval = $this->getDateSubmissionDeadline()->diff($today);

            if ($interval->days <= self::APPROACHING_DEADLINE_DELTA) {
                $result = true;
            }
        }

        return $result;
    }

    public function getDaysBeforeDeadline(): int
    {
        $result = 0;

        if ($this->getDateSubmissionDeadline()) {
            $today = new \DateTime(date('Y-m-d'));
            $interval = $this->getDateSubmissionDeadline()->diff($today);

            if ($interval->days <= self::APPROACHING_DEADLINE_DELTA) {
                $result = $interval->days;
            }
        }

        return $result;
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

        $this->live = false;

        if (
            $this->status == self::STATUS_PUBLISHED
            && (($this->dateStart && $this->dateStart <= $today) || !$this->dateStart)
            && (
                ($this->dateSubmissionDeadline && $this->dateSubmissionDeadline >= $today)
                || !$this->dateSubmissionDeadline
                )
        ) {
            $this->live = true;
        }

        return $this->live;
    }

    public function setLive(bool $live): static
    {
        $this->live = $live;

        return $this;
    }

    public function isOnGoing(): bool
    {
        if ($this->getAidRecurrence() && $this->aidRecurrence->getSlug() == AidRecurrence::SLUG_ONGOING) {
            return true;
        }

        return false;
    }

    public function isRecurring(): bool
    {
        if ($this->getAidRecurrence() && $this->aidRecurrence->getSlug() == AidRecurrence::SLUG_RECURRING) {
            return true;
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

    public function hasCalendar(): bool
    {
        if ($this->isOnGoing()) {
            return false;
        }

        if ($this->dateStart || $this->datePredeposit || $this->dateSubmissionDeadline) {
            return true;
        }

        return false;
    }

    public function isComingSoon(): bool
    {
        if (!$this->dateStart) {
            return false;
        }

        $today = new \DateTime(date('Y-m-d'));
        return $this->dateStart > $today;
    }

    public function hasExpired(): bool
    {
        if (!$this->dateSubmissionDeadline) {
            return false;
        }

        $today = new \DateTime(date('Y-m-d'));
        return $this->dateSubmissionDeadline < $today;
    }
    public function getFinancersPublicOrCorporate(): string
    {
        $corporate = false;
        $public = false;
        foreach ($this->getAidFinancers() as $aidFinancers) {
            if ($aidFinancers->getBacker() && $aidFinancers->getBacker()->isIsCorporate()) {
                $corporate = true;
            } else {
                $public = true;
            }
        }

        if ($corporate && $public) {
            $return = 'PORTEURS D\'AIDE PUBLIC ET PRIVÉ';
        } elseif ($corporate) {
            $return = 'PORTEUR D\'AIDE PRIVÉ';
        } elseif ($public) {
            $return = 'PORTEUR D\'AIDE PUBLIC';
        } else {
            $return = '';
        }

        return $return;
    }

    /**
     * @return ArrayCollection<int, Backer>
     */
    public function getFinancers(): ArrayCollection
    {
        /** @var ArrayCollection<int, Backer> */
        $financers = new ArrayCollection();
        foreach ($this->getAidFinancers() as $aidFinancer) {
            $financers->add($aidFinancer->getBacker());
        }
        $this->financers = $financers;
        return $this->financers;
    }

    /**
     * @param ArrayCollection<int, Backer> $financers
     * @return static
     */
    public function setFinancers(ArrayCollection $financers): static
    {
        $this->financers = $financers;
        return $this;
    }

    /**
     * @return ArrayCollection<int, Backer>
     */
    public function getInstructors(): ArrayCollection
    {
        /** @var ArrayCollection<int, Backer> */
        $instructors = new ArrayCollection();
        foreach ($this->getAidInstructors() as $aidInstructor) {
            $instructors->add($aidInstructor->getBacker());
        }
        $this->instructors = $instructors;
        return $this->instructors;
    }

    /**
     * @param Collection<int, Backer> $instructors
     * @return static
     */
    public function setInstructors(Collection $instructors): static
    {
        $this->instructors = $instructors;
        return $this;
    }

    public function getNbViews(): int
    {
        return $this->nbViews;
    }

    public function setNbViews(int $nbViews): static
    {
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
        if (
            $this->logAidApplicationUrlClicks->removeElement($logAidApplicationUrlClick)
            && $logAidApplicationUrlClick->getAid() === $this
        ) {
            $logAidApplicationUrlClick->setAid(null);
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
        if (
            $this->logAidOriginUrlClicks->removeElement($logAidOriginUrlClick)
            && $logAidOriginUrlClick->getAid() === $this
        ) {
            $logAidOriginUrlClick->setAid(null);
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
        if (
            $this->logAidContactClicks->removeElement($logAidContactClick)
            && $logAidContactClick->getAid() === $this
        ) {
            $logAidContactClick->setAid(null);
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
        if (
            $this->logAidCreatedsFolders->removeElement($logAidCreatedsFolder)
            && $logAidCreatedsFolder->getAid() === $this
        ) {
            $logAidCreatedsFolder->setAid(null);
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
        if (
            $this->logAidEligibilityTests->removeElement($logAidEligibilityTest)
            && $logAidEligibilityTest->getAid() === $this
        ) {
            $logAidEligibilityTest->setAid(null);
        }

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
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

    /**
     * @param Collection<int, ProjectReference> $projectReferences
     * @return static
     */
    public function setProjectReferences(Collection $projectReferences): static
    {
        $this->projectReferences = $projectReferences;

        return $this;
    }

    public function getScoreTotal(): int
    {
        return $this->scoreTotal;
    }

    public function setScoreTotal(int $scoreTotal): static
    {
        $this->scoreTotal = $scoreTotal;

        return $this;
    }

    public function getScoreObjects(): int
    {
        return $this->scoreObjects;
    }

    public function setScoreObjects(int $scoreObjects): static
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

    /**
     * @return ArrayCollection<int, Aid>
     */
    public function getAidsFromGenericLive(): ArrayCollection
    {
        $aids = new ArrayCollection();
        foreach ($this->getAidsFromGeneric() as $aid) {
            if ($aid->isLive()) {
                $aids->add($aid);
            }
        }

        $this->aidsFromGenericLive = $aids;
        return $this->aidsFromGenericLive;
    }

    /**
     * @return Collection<int, AidLock>
     */
    public function getAidLocks(): Collection
    {
        return $this->aidLocks;
    }

    public function addAidLock(AidLock $aidLock): static
    {
        if (!$this->aidLocks->contains($aidLock)) {
            $this->aidLocks->add($aidLock);
            $aidLock->setAid($this);
        }

        return $this;
    }

    public function removeAidLock(AidLock $aidLock): static
    {
        if ($this->aidLocks->removeElement($aidLock)) {
            // set the owning side to null (unless already changed)
            if ($aidLock->getAid() === $this) {
                $aidLock->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, KeywordReferenceSuggested>
     */
    public function getKeywordReferenceSuggesteds(): Collection
    {
        return $this->keywordReferenceSuggesteds;
    }

    public function addKeywordReferenceSuggested(KeywordReferenceSuggested $keywordReferenceSuggested): static
    {
        if (!$this->keywordReferenceSuggesteds->contains($keywordReferenceSuggested)) {
            $this->keywordReferenceSuggesteds->add($keywordReferenceSuggested);
            $keywordReferenceSuggested->setAid($this);
        }

        return $this;
    }

    public function removeKeywordReferenceSuggested(KeywordReferenceSuggested $keywordReferenceSuggested): static
    {
        if ($this->keywordReferenceSuggesteds->removeElement($keywordReferenceSuggested)) {
            // set the owning side to null (unless already changed)
            if ($keywordReferenceSuggested->getAid() === $this) {
                $keywordReferenceSuggested->setAid(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectReference>
     */
    public function getProjectReferencesSearched(): Collection
    {
        if (!$this->projectReferencesSearched) {
            $this->projectReferencesSearched = new ArrayCollection();
        }
        return $this->projectReferencesSearched;
    }

    public function addProjectReferenceSearched(ProjectReference $projectReferenceSearched): self
    {
        if (!$this->projectReferencesSearched) {
            $this->projectReferencesSearched = new ArrayCollection();
        }
        if (!$this->projectReferencesSearched->contains($projectReferenceSearched)) {
            $this->projectReferencesSearched[] = $projectReferenceSearched;
        }

        return $this;
    }

    public function removeProjectReferenceSearched(ProjectReference $projectReferenceSearched): self
    {
        if (!$this->projectReferencesSearched) {
            $this->projectReferencesSearched = new ArrayCollection();
        }
        $this->projectReferencesSearched->removeElement($projectReferenceSearched);
        return $this;
    }

    /**
     * @return Collection<int, SanctuarizedField>
     */
    public function getSanctuarizedFields(): Collection
    {
        return $this->sanctuarizedFields;
    }

    public function addSanctuarizedField(SanctuarizedField $sanctuarizedField): static
    {
        if (!$this->sanctuarizedFields->contains($sanctuarizedField)) {
            $this->sanctuarizedFields->add($sanctuarizedField);
            $sanctuarizedField->addAid($this);
        }

        return $this;
    }

    public function removeSanctuarizedField(SanctuarizedField $sanctuarizedField): static
    {
        if ($this->sanctuarizedFields->removeElement($sanctuarizedField)) {
            $sanctuarizedField->removeAid($this);
        }
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getImportDatas(): ?array
    {
        return $this->importDatas;
    }

    /**
     * @param string[]|null $importDatas
     * @return static
     */
    public function setImportDatas(?array $importDatas): static
    {
        $this->importDatas = $importDatas;
        return $this;
    }

    /**
     * @return array<int, ProjectReference>
     */
    public function getProjectReferencesSuggestions(): array
    {
        return $this->projectReferencesSuggestions;
    }

    /**
     * @param array<int, ProjectReference> $projectReferenceSuggestions
     * @return static
     */
    public function setProjectReferencesSuggestions(array $projectReferenceSuggestions): static
    {
        $this->projectReferencesSuggestions = $projectReferenceSuggestions;
        return $this;
    }

    /**
     * @return Collection<int, ProjectReferenceMissing>
     */
    public function getProjectReferenceMissings(): Collection
    {
        return $this->projectReferenceMissings;
    }

    public function addProjectReferenceMissing(ProjectReferenceMissing $projectReferenceMissing): static
    {
        if (!$this->projectReferenceMissings->contains($projectReferenceMissing)) {
            $this->projectReferenceMissings->add($projectReferenceMissing);
            $projectReferenceMissing->addAid($this);
        }

        return $this;
    }

    public function removeProjectReferenceMissing(ProjectReferenceMissing $projectReferenceMissing): static
    {
        if ($this->projectReferenceMissings->removeElement($projectReferenceMissing)) {
            $projectReferenceMissing->removeAid($this);
        }

        return $this;
    }

    public function getLastEditor(): ?User
    {
        return $this->lastEditor ?? $this->author;
    }

    public function setLastEditor(?User $lastEditor): static
    {
        $this->lastEditor = $lastEditor;

        return $this;
    }

    public function isPrivateEdition(): ?bool
    {
        return $this->privateEdition;
    }

    public function setPrivateEdition(bool $privateEdition): static
    {
        $this->privateEdition = $privateEdition;

        return $this;
    }
}
