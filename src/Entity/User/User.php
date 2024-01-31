<?php

namespace App\Entity\User;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Bundle\Bundle;
use App\Entity\Cron\CronExportSpreadsheet;
use App\Entity\DataExport\DataExport;
use App\Entity\DataSource\DataSource;
use App\Entity\Eligibility\EligibilityQuestion;
use App\Entity\Eligibility\EligibilityTest;
use App\Entity\Log\LogAdminAction;
use App\Entity\Log\LogAidCreatedsFolder;
use App\Entity\Log\LogAidSearch;
use App\Entity\Log\LogAidView;
use App\Entity\Log\LogBackerView;
use App\Entity\Log\LogBlogPostView;
use App\Entity\Log\LogProgramView;
use App\Entity\Log\LogProjectValidatedSearch;
use App\Entity\Log\LogPublicProjectSearch;
use App\Entity\Log\LogPublicProjectView;
use App\Entity\Log\LogUserAction;
use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationInvitation;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use App\Entity\Project\Project;
use App\Entity\Search\SearchPage;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Validator as AtAssert;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OrderBy;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email n\'est pas valide')]
#[ORM\Index(columns: ['email'], name: 'email_u')]
#[ORM\Index(columns: ['is_beneficiary'], name: 'is_beneficiary_u')]
#[ORM\Index(columns: ['is_contributor'], name: 'is_contributor_u')]
#[ORM\Index(columns: ['date_create'], name: 'date_create_u')]
#[ORM\Index(columns: ['date_last_login'], name: 'date_last_login_u')]
#[ORM\Index(columns: ['api_token'], name: 'api_token_u')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_USER = 'ROLE_USER';
    
    const NOTIFICATION_DAILY = 'daily';
    const NOTIFICATION_WEEKLY = 'weekly';
    const NOTIFICATION_NEVER = 'never';

    CONST FUNCTION_TYPES = [
        ['slug' => "mayor", 'name' => "Maire"],
        ['slug' => "deputy_mayor", 'name' => "Adjoint au maire"],
        ['slug' => "municipal_councilor", 'name' => "Conseiller municipal"],
        ['slug' => "elected", 'name' => "Élu"],
        ['slug' => "town_clerk", 'name' => "Secrétaire de mairie"],
        ['slug' => "agent", 'name' => "Agent territorial"],
        ['slug' => "other", 'name' => "Autre"],
    ];

    const ACQUISITION_CHANNEL_CHOICES = [
        ['slug' => "webinar", 'name' => "Webinaire"],
        ['slug' => "animator", 'name' => "Animateur local"],
        ['slug' => "trade_press", 'name' => "Presse spécialisée"],
        ['slug' => "word_of_mouth", 'name' => "Bouche-à-oreille"],
        ['slug' => "invited", 'name' => "Invitation à collaborer"],
        ['slug' => "other", 'name' => "Autre"],
    ];
    const ACQUISITION_CHANNEL_ANIMATOR = 'animator';
    // Propriétés scalaires
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column]
    private ?bool $isBeneficiary = false;

    #[ORM\Column]
    private ?bool $isContributor = false;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[AtAssert\Password]
    private ?string $password = null;

    private ?string $rawPassword = null;

    #[ORM\Column]
    private ?bool $isCertified = false;

    #[ORM\Column]
    private ?bool $mlConsent = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeLastLogin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLastLogin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $timeCreate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $dateCreate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $timeUpdate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $invitationTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeJoinOrganization = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $acquisitionChannel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $acquisitionChannelComment = null;

    #[ORM\Column]
    private ?int $notificationCounter = 0;

    #[ORM\Column(length: 32)]
    private ?string $notificationEmailFrequency = self::NOTIFICATION_DAILY;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $contributorContactPhone = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $contributorOrganization = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $contributorRole = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $beneficiaryFunction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $beneficiaryRole = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $apiToken = null;

    // Relations
    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Perimeter $perimeter = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'guests')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $invitationAuthor = null;

    #[ORM\OneToMany(mappedBy: 'invitationAuthor', targetEntity: self::class)]
    private Collection $guests;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogUserLogin::class, orphanRemoval: true)]
    private Collection $logUserLogins;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogUserAction::class, orphanRemoval: true)]
    private Collection $logUserActions;

    #[ORM\ManyToMany(targetEntity: UserGroup::class, mappedBy: 'users')]
    private Collection $userGroups;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: PerimeterImport::class, orphanRemoval: true)]
    private Collection $perimeterImports;

    #[ORM\ManyToOne]
    private ?Organization $proposedOrganization = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: EligibilityTest::class)]
    private Collection $eligibilityTests;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: EligibilityQuestion::class)]
    private Collection $eligibilityQuestions;

    #[ORM\OneToMany(mappedBy: 'contactTeam', targetEntity: DataSource::class)]
    private Collection $dataSourceContactTeams;

    #[ORM\OneToMany(mappedBy: 'aidAuthor', targetEntity: DataSource::class)]
    private Collection $dataSourceAidAuthors;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Aid::class)]
    #[OrderBy(['timeCreate' => 'DESC'])]
    private Collection $aids;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Project::class, orphanRemoval: true)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: AidProject::class)]
    private Collection $aidProjects;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: AidSuggestedAidProject::class)]
    private Collection $aidSuggestedAidProjects;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Bundle::class, orphanRemoval: true)]
    private Collection $bundles;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: DataExport::class)]
    private Collection $dataExports;

    #[ORM\ManyToMany(targetEntity: Organization::class, mappedBy: 'beneficiairies', cascade:['persist'])]
    private Collection $organizations;

    #[ORM\OneToMany(mappedBy: 'administrator', targetEntity: SearchPage::class)]
    private Collection $searchPages;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: \App\Entity\Directory\Directory::class, orphanRemoval: true)]
    private Collection $directories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRegisterConfirmation::class, orphanRemoval: true)]
    private Collection $userRegisterConfirmations;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: OrganizationInvitation::class)]
    private Collection $organizationInvitations;

    #[ORM\OneToMany(mappedBy: 'guest', targetEntity: OrganizationInvitation::class)]
    private Collection $organizationGuests;

    #[ORM\OneToMany(mappedBy: 'admin', targetEntity: LogAdminAction::class)]
    private Collection $logAdminActions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogAidView::class)]
    private Collection $logAidViews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogAidCreatedsFolder::class)]
    private Collection $logAidCreatedsFolders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogAidSearch::class)]
    private Collection $logAidSearches;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogBackerView::class)]
    private Collection $logBackerViews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogBlogPostView::class)]
    private Collection $logBlogPostViews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogProgramView::class)]
    private Collection $logProgramViews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogPublicProjectSearch::class)]
    private Collection $logPublicProjectSearches;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogPublicProjectView::class)]
    private Collection $logPublicProjectViews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: LogProjectValidatedSearch::class)]
    private Collection $logProjectValidatedSearches;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiTokenAsk::class)]
    private Collection $apiTokenAsks;

    // Pas en base
    private int $nbAids = 0;
    private int $nbAidsLive = 0;
    private string $notificationSignature;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CronExportSpreadsheet::class)]
    private Collection $cronExportSpreadsheets;

    public function __construct()
    {
        $this->logUserLogins = new ArrayCollection();
        $this->logUserActions = new ArrayCollection();
        $this->guests = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->perimeterImports = new ArrayCollection();
        $this->eligibilityTests = new ArrayCollection();
        $this->eligibilityQuestions = new ArrayCollection();
        $this->dataSourceContactTeams = new ArrayCollection();
        $this->dataSourceAidAuthors = new ArrayCollection();
        $this->aids = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->aidProjects = new ArrayCollection();
        $this->aidSuggestedAidProjects = new ArrayCollection();
        $this->bundles = new ArrayCollection();
        $this->dataExports = new ArrayCollection();
        $this->organizations = new ArrayCollection();
        $this->searchPages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->logAdminActions = new ArrayCollection();
        $this->directories = new ArrayCollection();
        $this->userRegisterConfirmations = new ArrayCollection();
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
        $this->organizationGuests = new ArrayCollection();
        $this->apiTokenAsks = new ArrayCollection();
        $this->cronExportSpreadsheets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function addRole($role)
    {
        $this->roles[] = $role;
        $this->roles = array_unique($this->roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRawPassword(): string
    {
        return $this->rawPassword;
    }

    public function setRawPassword(string $rawPassword): self
    {
        $this->rawPassword = $rawPassword;

        return $this;
    }
    
    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, LogUserLogin>
     */
    public function getLogUserLogins(): Collection
    {
        return $this->logUserLogins;
    }

    public function addLogUserLogin(LogUserLogin $logUserLogin): static
    {
        if (!$this->logUserLogins->contains($logUserLogin)) {
            $this->logUserLogins->add($logUserLogin);
            $logUserLogin->setUser($this);
        }

        return $this;
    }

    public function removeLogUserLogin(LogUserLogin $logUserLogin): static
    {
        if ($this->logUserLogins->removeElement($logUserLogin)) {
            // set the owning side to null (unless already changed)
            if ($logUserLogin->getUser() === $this) {
                $logUserLogin->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogUserAction>
     */
    public function getLogUserActions(): Collection
    {
        return $this->logUserActions;
    }

    public function addLogUserAction(LogUserAction $logUserAction): static
    {
        if (!$this->logUserActions->contains($logUserAction)) {
            $this->logUserActions->add($logUserAction);
            $logUserAction->setUser($this);
        }

        return $this;
    }

    public function removeLogUserAction(LogUserAction $logUserAction): static
    {
        if ($this->logUserActions->removeElement($logUserAction)) {
            // set the owning side to null (unless already changed)
            if ($logUserAction->getUser() === $this) {
                $logUserAction->setUser(null);
            }
        }

        return $this;
    }

    public function getTimeLastLogin(): ?\DateTimeInterface
    {
        return $this->timeLastLogin;
    }

    public function setTimeLastLogin(?\DateTimeInterface $timeLastLogin): static
    {
        $this->timeLastLogin = $timeLastLogin;

        return $this;
    }

    public function getDateLastLogin(): ?\DateTimeInterface
    {
        return $this->dateLastLogin;
    }

    public function setDateLastLogin(?\DateTimeInterface $dateLastLogin): static
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function isIsBeneficiary(): ?bool
    {
        return $this->isBeneficiary;
    }

    public function setIsBeneficiary(bool $isBeneficiary): static
    {
        $this->isBeneficiary = $isBeneficiary;

        return $this;
    }

    public function isIsContributor(): ?bool
    {
        return $this->isContributor;
    }

    public function setIsContributor(bool $isContributor): static
    {
        $this->isContributor = $isContributor;

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

    public function getPerimeter(): ?Perimeter
    {
        return $this->perimeter;
    }

    public function setPerimeter(?Perimeter $perimeter): static
    {
        $this->perimeter = $perimeter;

        return $this;
    }

    public function isIsCertified(): ?bool
    {
        return $this->isCertified;
    }

    public function setIsCertified(bool $isCertified): static
    {
        $this->isCertified = $isCertified;

        return $this;
    }

    public function isMlConsent(): ?bool
    {
        return $this->mlConsent;
    }

    public function setMlConsent(bool $mlConsent): static
    {
        $this->mlConsent = $mlConsent;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getInvitationAuthor(): ?self
    {
        return $this->invitationAuthor;
    }

    public function setInvitationAuthor(?self $invitationAuthor): static
    {
        $this->invitationAuthor = $invitationAuthor;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getGuests(): Collection
    {
        return $this->guests;
    }

    public function addGuest(self $guest): static
    {
        if (!$this->guests->contains($guest)) {
            $this->guests->add($guest);
            $guest->setInvitationAuthor($this);
        }

        return $this;
    }

    public function removeGuest(self $guest): static
    {
        if ($this->guests->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getInvitationAuthor() === $this) {
                $guest->setInvitationAuthor(null);
            }
        }

        return $this;
    }

    public function getInvitationTime(): ?\DateTimeInterface
    {
        return $this->invitationTime;
    }

    public function setInvitationTime(?\DateTimeInterface $invitationTime): static
    {
        $this->invitationTime = $invitationTime;

        return $this;
    }

    public function getAcquisitionChannel(): ?string
    {
        return $this->acquisitionChannel;
    }

    public function setAcquisitionChannel(?string $acquisitionChannel): static
    {
        $this->acquisitionChannel = $acquisitionChannel;

        return $this;
    }

    public function getAcquisitionChannelComment(): ?string
    {
        return $this->acquisitionChannelComment;
    }

    public function setAcquisitionChannelComment(?string $acquisitionChannelComment): static
    {
        $this->acquisitionChannelComment = $acquisitionChannelComment;

        return $this;
    }

    public function getNotificationCounter(): ?int
    {
        return $this->notificationCounter;
    }

    public function setNotificationCounter(int $notificationCounter): static
    {
        $this->notificationCounter = $notificationCounter;

        return $this;
    }

    public function getNotificationEmailFrequency(): ?string
    {
        return $this->notificationEmailFrequency;
    }

    public function setNotificationEmailFrequency(string $notificationEmailFrequency): static
    {
        $this->notificationEmailFrequency = $notificationEmailFrequency;

        return $this;
    }

    /**
     * @return Collection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    public function addUserGroup(UserGroup $userGroup): static
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
            $userGroup->addUser($this);
        }

        return $this;
    }

    public function removeUserGroup(UserGroup $userGroup): static
    {
        if ($this->userGroups->removeElement($userGroup)) {
            $userGroup->removeUser($this);
        }

        return $this;
    }

    public function getContributorContactPhone(): ?string
    {
        return $this->contributorContactPhone;
    }

    public function setContributorContactPhone(?string $contributorContactPhone): static
    {
        $this->contributorContactPhone = $contributorContactPhone;

        return $this;
    }

    public function getContributorOrganization(): ?string
    {
        return $this->contributorOrganization;
    }

    public function setContributorOrganization(?string $contributorOrganization): static
    {
        $this->contributorOrganization = $contributorOrganization;

        return $this;
    }

    public function getContributorRole(): ?string
    {
        return $this->contributorRole;
    }

    public function setContributorRole(?string $contributorRole): static
    {
        $this->contributorRole = $contributorRole;

        return $this;
    }

    public function getBeneficiaryFunction(): ?string
    {
        return $this->beneficiaryFunction;
    }

    public function setBeneficiaryFunction(?string $beneficiaryFunction): static
    {
        $this->beneficiaryFunction = $beneficiaryFunction;

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
            $perimeterImport->setAuthor($this);
        }

        return $this;
    }

    public function removePerimeterImport(PerimeterImport $perimeterImport): static
    {
        if ($this->perimeterImports->removeElement($perimeterImport)) {
            // set the owning side to null (unless already changed)
            if ($perimeterImport->getAuthor() === $this) {
                $perimeterImport->setAuthor(null);
            }
        }

        return $this;
    }

    public function getBeneficiaryRole(): ?string
    {
        return $this->beneficiaryRole;
    }

    public function setBeneficiaryRole(?string $beneficiaryRole): static
    {
        $this->beneficiaryRole = $beneficiaryRole;

        return $this;
    }

    public function getProposedOrganization(): ?Organization
    {
        return $this->proposedOrganization;
    }

    public function setProposedOrganization(?Organization $proposedOrganization): static
    {
        $this->proposedOrganization = $proposedOrganization;

        return $this;
    }

    public function getTimeJoinOrganization(): ?\DateTimeInterface
    {
        return $this->timeJoinOrganization;
    }

    public function setTimeJoinOrganization(?\DateTimeInterface $timeJoinOrganization): static
    {
        $this->timeJoinOrganization = $timeJoinOrganization;

        return $this;
    }

    /**
     * @return Collection<int, EligibilityTest>
     */
    public function getEligibilityTests(): Collection
    {
        return $this->eligibilityTests;
    }

    public function addEligibilityTest(EligibilityTest $eligibilityTest): static
    {
        if (!$this->eligibilityTests->contains($eligibilityTest)) {
            $this->eligibilityTests->add($eligibilityTest);
            $eligibilityTest->setAuthor($this);
        }

        return $this;
    }

    public function removeEligibilityTest(EligibilityTest $eligibilityTest): static
    {
        if ($this->eligibilityTests->removeElement($eligibilityTest)) {
            // set the owning side to null (unless already changed)
            if ($eligibilityTest->getAuthor() === $this) {
                $eligibilityTest->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EligibilityQuestion>
     */
    public function getEligibilityQuestions(): Collection
    {
        return $this->eligibilityQuestions;
    }

    public function addEligibilityQuestion(EligibilityQuestion $eligibilityQuestion): static
    {
        if (!$this->eligibilityQuestions->contains($eligibilityQuestion)) {
            $this->eligibilityQuestions->add($eligibilityQuestion);
            $eligibilityQuestion->setAuthor($this);
        }

        return $this;
    }

    public function removeEligibilityQuestion(EligibilityQuestion $eligibilityQuestion): static
    {
        if ($this->eligibilityQuestions->removeElement($eligibilityQuestion)) {
            // set the owning side to null (unless already changed)
            if ($eligibilityQuestion->getAuthor() === $this) {
                $eligibilityQuestion->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DataSource>
     */
    public function getDataSourceContactTeams(): Collection
    {
        return $this->dataSourceContactTeams;
    }

    public function addDataSourceContactTeam(DataSource $dataSourceContactTeam): static
    {
        if (!$this->dataSourceContactTeams->contains($dataSourceContactTeam)) {
            $this->dataSourceContactTeams->add($dataSourceContactTeam);
            $dataSourceContactTeam->setContactTeam($this);
        }

        return $this;
    }

    public function removeDataSourceContactTeam(DataSource $dataSourceContactTeam): static
    {
        if ($this->dataSourceContactTeams->removeElement($dataSourceContactTeam)) {
            // set the owning side to null (unless already changed)
            if ($dataSourceContactTeam->getContactTeam() === $this) {
                $dataSourceContactTeam->setContactTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DataSource>
     */
    public function getDataSourceAidAuthors(): Collection
    {
        return $this->dataSourceAidAuthors;
    }

    public function addDataSourceAidAuthor(DataSource $dataSourceAidAuthor): static
    {
        if (!$this->dataSourceAidAuthors->contains($dataSourceAidAuthor)) {
            $this->dataSourceAidAuthors->add($dataSourceAidAuthor);
            $dataSourceAidAuthor->setAidAuthor($this);
        }

        return $this;
    }

    public function removeDataSourceAidAuthor(DataSource $dataSourceAidAuthor): static
    {
        if ($this->dataSourceAidAuthors->removeElement($dataSourceAidAuthor)) {
            // set the owning side to null (unless already changed)
            if ($dataSourceAidAuthor->getAidAuthor() === $this) {
                $dataSourceAidAuthor->setAidAuthor(null);
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
            $aid->setAuthor($this);
        }

        return $this;
    }

    public function removeAid(Aid $aid): static
    {
        if ($this->aids->removeElement($aid)) {
            // set the owning side to null (unless already changed)
            if ($aid->getAuthor() === $this) {
                $aid->setAuthor(null);
            }
        }

        return $this;
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setAuthor($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getAuthor() === $this) {
                $project->setAuthor(null);
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
            $aidProject->setCreator($this);
        }

        return $this;
    }

    public function removeAidProject(AidProject $aidProject): static
    {
        if ($this->aidProjects->removeElement($aidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidProject->getCreator() === $this) {
                $aidProject->setCreator(null);
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
            $aidSuggestedAidProject->setCreator($this);
        }

        return $this;
    }

    public function removeAidSuggestedAidProject(AidSuggestedAidProject $aidSuggestedAidProject): static
    {
        if ($this->aidSuggestedAidProjects->removeElement($aidSuggestedAidProject)) {
            // set the owning side to null (unless already changed)
            if ($aidSuggestedAidProject->getCreator() === $this) {
                $aidSuggestedAidProject->setCreator(null);
            }
        }

        return $this;
    }

    // public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    // {
    //     dump($user);
    //     // set the new hashed password on the User object
    //     $user->setPassword($newHashedPassword);

    //     // ... store the new password
    // }

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
            $bundle->setOwner($this);
        }

        return $this;
    }

    public function removeBundle(Bundle $bundle): static
    {
        if ($this->bundles->removeElement($bundle)) {
            // set the owning side to null (unless already changed)
            if ($bundle->getOwner() === $this) {
                $bundle->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DataExport>
     */
    public function getDataExports(): Collection
    {
        return $this->dataExports;
    }

    public function addDataExport(DataExport $dataExport): static
    {
        if (!$this->dataExports->contains($dataExport)) {
            $this->dataExports->add($dataExport);
            $dataExport->setAuthor($this);
        }

        return $this;
    }

    public function removeDataExport(DataExport $dataExport): static
    {
        if ($this->dataExports->removeElement($dataExport)) {
            // set the owning side to null (unless already changed)
            if ($dataExport->getAuthor() === $this) {
                $dataExport->setAuthor(null);
            }
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
            $organization->addBeneficiairy($this);
        }

        return $this;
    }

    public function removeOrganization(Organization $organization): static
    {
        if ($this->organizations->removeElement($organization)) {
            $organization->removeBeneficiairy($this);
        }

        return $this;
    }

    public function getToptpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(string $totpSecret): static
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpSecret ? true : false;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
    // You could persist the other configuration options in the user entity to make it individual per user.
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    /**
     * @return Collection<int, SearchPage>
     */
    public function getSearchPages(): Collection
    {
        return $this->searchPages;
    }

    public function addSearchPage(SearchPage $searchPage): static
    {
        if (!$this->searchPages->contains($searchPage)) {
            $this->searchPages->add($searchPage);
            $searchPage->setAdministrator($this);
        }

        return $this;
    }

    public function removeSearchPage(SearchPage $searchPage): static
    {
        if ($this->searchPages->removeElement($searchPage)) {
            // set the owning side to null (unless already changed)
            if ($searchPage->getAdministrator() === $this) {
                $searchPage->setAdministrator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LogAdminAction>
     */
    public function getLogAdminActions(): Collection
    {
        return $this->logAdminActions;
    }

    public function addLogAdminAction(LogAdminAction $logAdminAction): static
    {
        if (!$this->logAdminActions->contains($logAdminAction)) {
            $this->logAdminActions->add($logAdminAction);
            $logAdminAction->setAdmin($this);
        }

        return $this;
    }

    public function removeLogAdminAction(LogAdminAction $logAdminAction): static
    {
        if ($this->logAdminActions->removeElement($logAdminAction)) {
            // set the owning side to null (unless already changed)
            if ($logAdminAction->getAdmin() === $this) {
                $logAdminAction->setAdmin(null);
            }
        }

        return $this;
    }

    /*****************************************
     * SPECIFIC
     */

    public function __toString(): string
    {
        return $this->email;
    }

    public function getDefaultOrganization(): ?Organization
    {
        return $this->organizations[0] ?? null;
    }

    /**
     * @return Collection<int, \App\Entity\Directory\Directory>
     */
    public function getDirectories(): Collection
    {
        return $this->directories;
    }

    public function addDirectory(\App\Entity\Directory\Directory $directory): static
    {
        if (!$this->directories->contains($directory)) {
            $this->directories->add($directory);
            $directory->setAuthor($this);
        }

        return $this;
    }

    public function removeDirectory(\App\Entity\Directory\Directory $directory): static
    {
        if ($this->directories->removeElement($directory)) {
            // set the owning side to null (unless already changed)
            if ($directory->getAuthor() === $this) {
                $directory->setAuthor(null);
            }
        }

        return $this;
    }


    public function getSearchPreferences() : array {
        $preferences = [];

        if ($this->getDefaultOrganization()) {
            if ($this->getDefaultOrganization()->getOrganizationType()) {
                $preferences['targeted_audiences'] = $this->getDefaultOrganization()->getOrganizationType()->getSlug();
            }

            if ($this->getDefaultOrganization()->getPerimeter()) {
                $preferences['perimeter'] = $this->getDefaultOrganization()->getPerimeter()->getId();
            }
        }

        return $preferences;
    }

    public function getSearchPreferencesString() : string {
        return join('&', $this->getSearchPreferences());
    }

    /**
     * @return Collection<int, UserRegisterConfirmation>
     */
    public function getUserRegisterConfirmations(): Collection
    {
        return $this->userRegisterConfirmations;
    }

    public function addUserRegisterConfirmation(UserRegisterConfirmation $userRegisterConfirmation): static
    {
        if (!$this->userRegisterConfirmations->contains($userRegisterConfirmation)) {
            $this->userRegisterConfirmations->add($userRegisterConfirmation);
            $userRegisterConfirmation->setUser($this);
        }

        return $this;
    }

    public function removeUserRegisterConfirmation(UserRegisterConfirmation $userRegisterConfirmation): static
    {
        if ($this->userRegisterConfirmations->removeElement($userRegisterConfirmation)) {
            // set the owning side to null (unless already changed)
            if ($userRegisterConfirmation->getUser() === $this) {
                $userRegisterConfirmation->setUser(null);
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
            $logAidView->setUser($this);
        }

        return $this;
    }

    public function removeLogAidView(LogAidView $logAidView): static
    {
        if ($this->logAidViews->removeElement($logAidView)) {
            // set the owning side to null (unless already changed)
            if ($logAidView->getUser() === $this) {
                $logAidView->setUser(null);
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
            $logAidCreatedsFolder->setUser($this);
        }

        return $this;
    }

    public function removeLogAidCreatedsFolder(LogAidCreatedsFolder $logAidCreatedsFolder): static
    {
        if ($this->logAidCreatedsFolders->removeElement($logAidCreatedsFolder)) {
            // set the owning side to null (unless already changed)
            if ($logAidCreatedsFolder->getUser() === $this) {
                $logAidCreatedsFolder->setUser(null);
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
            $logAidSearch->setUser($this);
        }

        return $this;
    }

    public function removeLogAidSearch(LogAidSearch $logAidSearch): static
    {
        if ($this->logAidSearches->removeElement($logAidSearch)) {
            // set the owning side to null (unless already changed)
            if ($logAidSearch->getUser() === $this) {
                $logAidSearch->setUser(null);
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
            $logBackerView->setUser($this);
        }

        return $this;
    }

    public function removeLogBackerView(LogBackerView $logBackerView): static
    {
        if ($this->logBackerViews->removeElement($logBackerView)) {
            // set the owning side to null (unless already changed)
            if ($logBackerView->getUser() === $this) {
                $logBackerView->setUser(null);
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
            $logBlogPostView->setUser($this);
        }

        return $this;
    }

    public function removeLogBlogPostView(LogBlogPostView $logBlogPostView): static
    {
        if ($this->logBlogPostViews->removeElement($logBlogPostView)) {
            // set the owning side to null (unless already changed)
            if ($logBlogPostView->getUser() === $this) {
                $logBlogPostView->setUser(null);
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
            $logProgramView->setUser($this);
        }

        return $this;
    }

    public function removeLogProgramView(LogProgramView $logProgramView): static
    {
        if ($this->logProgramViews->removeElement($logProgramView)) {
            // set the owning side to null (unless already changed)
            if ($logProgramView->getUser() === $this) {
                $logProgramView->setUser(null);
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
            $logPublicProjectSearch->setUser($this);
        }

        return $this;
    }

    public function removeLogPublicProjectSearch(LogPublicProjectSearch $logPublicProjectSearch): static
    {
        if ($this->logPublicProjectSearches->removeElement($logPublicProjectSearch)) {
            // set the owning side to null (unless already changed)
            if ($logPublicProjectSearch->getUser() === $this) {
                $logPublicProjectSearch->setUser(null);
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
            $logPublicProjectView->setUser($this);
        }

        return $this;
    }

    public function removeLogPublicProjectView(LogPublicProjectView $logPublicProjectView): static
    {
        if ($this->logPublicProjectViews->removeElement($logPublicProjectView)) {
            // set the owning side to null (unless already changed)
            if ($logPublicProjectView->getUser() === $this) {
                $logPublicProjectView->setUser(null);
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
            $logProjectValidatedSearch->setUser($this);
        }

        return $this;
    }

    public function removeLogProjectValidatedSearch(LogProjectValidatedSearch $logProjectValidatedSearch): static
    {
        if ($this->logProjectValidatedSearches->removeElement($logProjectValidatedSearch)) {
            // set the owning side to null (unless already changed)
            if ($logProjectValidatedSearch->getUser() === $this) {
                $logProjectValidatedSearch->setUser(null);
            }
        }

        return $this;
    }

    public function getNbAids() : int {
        try {
            return count($this->getAids());
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getNbAidsLive() : int {
        $live = 0;
        try {
            foreach ($this->getAids() as $aid) {
                if ($aid->isLive()) {
                    $live++;
                }
            }
            return $live;
        } catch (\Exception $e) {
            return 0;
        }
    }


    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getNotificationSignature(): string
    {
        $signature = $this->getFirstname() . ' ' . $this->getLastname();
        if ($this->getDefaultOrganization()) {
            if ($this->getDefaultOrganization()->getOrganizationType() == OrganizationType::SLUG_PRIVATE_PERSON) {
                $signature .= ' (particulier)';
            } else {
                $signature .= ' (' . $this->getDefaultOrganization()->getName() . ')';
            }
        }
        return $signature;
    }

    public function getFullName() : string {
        return $this->getFirstname() . ' ' . $this->getLastname();
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
            $organizationInvitation->setAuthor($this);
        }

        return $this;
    }

    public function removeOrganizationInvitation(OrganizationInvitation $organizationInvitation): static
    {
        if ($this->organizationInvitations->removeElement($organizationInvitation)) {
            // set the owning side to null (unless already changed)
            if ($organizationInvitation->getAuthor() === $this) {
                $organizationInvitation->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrganizationInvitation>
     */
    public function getOrganizationGuests(): Collection
    {
        return $this->organizationGuests;
    }

    public function addOrganizationGuest(OrganizationInvitation $organizationGuest): static
    {
        if (!$this->organizationGuests->contains($organizationGuest)) {
            $this->organizationGuests->add($organizationGuest);
            $organizationGuest->setGuest($this);
        }

        return $this;
    }

    public function removeOrganizationGuest(OrganizationInvitation $organizationGuest): static
    {
        if ($this->organizationGuests->removeElement($organizationGuest)) {
            // set the owning side to null (unless already changed)
            if ($organizationGuest->getGuest() === $this) {
                $organizationGuest->setGuest(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiTokenAsk>
     */
    public function getApiTokenAsks(): Collection
    {
        return $this->apiTokenAsks;
    }

    public function addApiTokenAsk(ApiTokenAsk $apiTokenAsk): static
    {
        if (!$this->apiTokenAsks->contains($apiTokenAsk)) {
            $this->apiTokenAsks->add($apiTokenAsk);
            $apiTokenAsk->setUser($this);
        }

        return $this;
    }

    public function removeApiTokenAsk(ApiTokenAsk $apiTokenAsk): static
    {
        if ($this->apiTokenAsks->removeElement($apiTokenAsk)) {
            // set the owning side to null (unless already changed)
            if ($apiTokenAsk->getUser() === $this) {
                $apiTokenAsk->setUser(null);
            }
        }

        return $this;
    }

    public function getLastestAidPublished(): ?Aid
    {
        foreach ($this->aids as $aid) {
            if ($aid->isPublished()) {
                return $aid;
            }
        }
        return null;
    }

    public function getLastestAidDraft(): ?Aid
    {
        foreach ($this->aids as $aid) {
            if (!$aid->isDraft()) {
                return $aid;
            }
        }
        return null;
    }

    public function getLatestAidExpired(): ?Aid
    {
        foreach ($this->aids as $aid) {
            if ($aid->hasExpired()) {
                return $aid;
            }
        }
        return null;
    }

    /**
     * @return Collection<int, CronExportSpreadsheet>
     */
    public function getCronExportSpreadsheets(): Collection
    {
        return $this->cronExportSpreadsheets;
    }

    public function addCronExportSpreadsheet(CronExportSpreadsheet $cronExportSpreadsheet): static
    {
        if (!$this->cronExportSpreadsheets->contains($cronExportSpreadsheet)) {
            $this->cronExportSpreadsheets->add($cronExportSpreadsheet);
            $cronExportSpreadsheet->setUser($this);
        }

        return $this;
    }

    public function removeCronExportSpreadsheet(CronExportSpreadsheet $cronExportSpreadsheet): static
    {
        if ($this->cronExportSpreadsheets->removeElement($cronExportSpreadsheet)) {
            // set the owning side to null (unless already changed)
            if ($cronExportSpreadsheet->getUser() === $this) {
                $cronExportSpreadsheet->setUser(null);
            }
        }

        return $this;
    }

    public function getBeneficiaryFunctionDisplay(): ?string
    {
        foreach (self::FUNCTION_TYPES as $function) {
            if ($function['slug'] == $this->beneficiaryFunction) {
                return $function['name'];
            }
        }
        return '';
    }
}
