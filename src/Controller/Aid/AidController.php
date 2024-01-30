<?php

namespace App\Controller\Aid;

use App\Controller\Admin\Aid\AidCrudController;
use App\Controller\Admin\DashboardController;
use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidFinancer;
use App\Entity\Aid\AidInstructor;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Form\Aid\AidSearchType;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Aid\SuggestToProjectType;
use App\Form\Alert\AlertCreateType;
use App\Form\Project\AddAidToProjectType;
use App\Repository\Aid\AidRepository;
use App\Repository\Blog\BlogPromotionPostRepository;
use App\Repository\Project\ProjectRepository;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Blog\BlogPromotionPostService;
use App\Service\Email\EmailService;
use App\Service\Log\LogService;
use App\Service\Matomo\MatomoService;
use App\Service\Notification\NotificationService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Pagerfanta\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Pagerfanta;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AidController extends FrontController
{
    public function __construct(
        public Breadcrumb $breadcrumb,
        public TranslatorInterface $translatorInterface,
        public ManagerRegistry $managerRegistry
    ) {
        parent::__construct($breadcrumb, $translatorInterface);
    }
    const NB_AID_BY_PAGE = 18;

    #[Route('/aides/', name: 'app_aid_aid')]
    public function index(
        RequestStack $requestStack,
        AidRepository $aidRepository,
        BlogPromotionPostRepository $blogPromotionPostRepository,
        UserService $userService,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        LogService $logService,
        ReferenceService $referenceService,
        BlogPromotionPostService $blogPromotionPostService
    ): Response
    {
        $requestStack->getCurrentRequest()->getSession()->set('_security.main.target_path', $requestStack->getCurrentRequest()->getRequestUri());
        $user = $userService->getUserLogged();

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        // paramètres du formulaire
        $formAidSearchParams = [
            'method' => 'GET',
            'extended' => true,
        ];
        // paramètre selon url
        // $formAidSearchParams = array_merge(
        //     $formAidSearchParams,
        //     $aidSearchFormService->completeFormAidSearchParams()
        // );

        // formulaire recherche aides
        $aidSearchClass = $aidSearchFormService->getAidSearchClass();
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());

        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
        $query = parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
        // if ($query) {
        //     $aidParams = array_merge($aidParams, $aidSearchFormService->convertQuerystringToParams($query));
        // }

        // transforme le orderBy
        // $aidParams = $aidSearchFormService->handleOrderBy($aidParams);

        // le paginateur
        $aids = $aidService->searchAids($aidParams);
        $adapter = new ArrayAdapter($aids);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_AID_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // Log recherche
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $query ?? null,
            'resultsCount' => $pagerfanta->getNbResults(),
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'perimeter' => $aidParams['perimeter'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
        ];
        $themes = new ArrayCollection();
        if (isset($aidParams['categories']) && is_array($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                if (!$themes->contains($category->getCategoryTheme())) {
                    $themes->add($category->getCategoryTheme());
                }
            }
        }
        $logParams['themes'] = $themes->toArray();
        $logService->log(
            type: LogService::AID_SEARCH,
            params: $logParams,
        );

        // promotions posts
        $blogPromotionPosts = $blogPromotionPostRepository->findPublished($aidParams);
        $blogPromotionPosts = $blogPromotionPostService->handleRequires($blogPromotionPosts, $aidParams);
        
        // page title
        $pageTitle = $pagerfanta->getNbResults() . ' résultat';
        if ($pagerfanta->getNbResults() > 1) {
            $pageTitle .= 's';
        }
        $pageTitle .= ' de recherche : ';
        if ($formAidSearch->get('organizationType')->getData()) {
            $pageTitle .= ' Structure : '.$formAidSearch->get('organizationType')->getData()->getName(). ' ';
        }
        if ($formAidSearch->get('searchPerimeter')->getData()) {
            $pageTitle .= ' - Périmètre : '.$formAidSearch->get('searchPerimeter')->getData()->getName(). ' ';
        }
        $nbCriteria = 0;
        if (is_array($formAidSearch->getData())) {
            foreach ($formAidSearch->getData() as $key => $data) {
                if (in_array($key, ['organizationType', 'searchPerimeter'])) {
                    continue;
                } else if (in_array($key, ['aidTypes', 'categorysearch', 'backers', 'programs', 'aidSteps'])) {
                    if (count($formAidSearch->get($key)->getData()) > 0) {
                        $nbCriteria++;
                    }
                } else {
                    if ($data) {
                        $nbCriteria++;
                    }
                }
            }
        }

        if ($nbCriteria > 0) {
            $pageTitle .= ' - '.$nbCriteria . ' autre';
            if ($nbCriteria > 1) {
                $pageTitle .= 's';
            }
            $pageTitle .= ' critère';
            if ($nbCriteria > 1) {
                $pageTitle .= 's';
            }
        }

        // check si on affiche ou pas le formulaire étendu
        $showExtended = $aidSearchFormService->setShowExtendedV2($aidSearchClass);

        // formulaire creer alerte
        $alert = new Alert();
        $formAlertCreate = $this->createForm(AlertCreateType::class, $alert);
        $formAlertCreate->handleRequest($requestStack->getCurrentRequest());
        if ($formAlertCreate->isSubmitted()) {
            if ($formAlertCreate->isValid()) {
                $user = $userService->getUserLogged();
                if ($user) {
                    try {
                        $alert->setEmail($user->getEmail());
                        $alert->setQuerystring($requestStack->getCurrentRequest()->server->get('QUERY_STRING', null));
                        $alert->setSource(Alert::SOURCE_AIDES_TERRITOIRES);
    
                        $this->managerRegistry->getManager()->persist($alert);
                        $this->managerRegistry->getManager()->flush();

                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'Votre alerte a bien été créée'
                        );
                    } catch (\Exception $e) {
                        $this->addFlash(
                            FrontController::FLASH_ERROR,
                            'Une erreur est survenue lors de la création de votre alerte'
                        );
                    }
                }
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Trouver des aides',
            $requestStack->getCurrentRequest()->getRequestUri()
        );
        $this->breadcrumb->add(
            'Resultats',
            null
        );

        // pour les stats
        $categoriesName = [];
        if (isset($aidParams['categories']) && is_array($aidParams['categories'])) {
            foreach ($aidParams['categories'] as $category) {
                $categoriesName[] = $category->getName();
            }
        }

        // pour avoir la recherche surlignée
        $highlightedWords = $requestStack->getCurrentRequest()->getSession()->get('highlightedWords', []);
        
        if (isset($aidSearchClass) and $aidSearchClass instanceof AidSearchClass) {
            $highlightedWords = [];
            if ($aidSearchClass->getKeyword()) {
                $synonyms = $referenceService->getSynonymes($aidSearchClass->getKeyword());
                if (isset($synonyms['intentions_string'])) {
                    $keywords = str_getcsv($synonyms['intentions_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                } 
                if (isset($synonyms['objects_string'])) {
                    $keywords = str_getcsv($synonyms['objects_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                } 
                if (isset($synonyms['simple_words_string'])) {
                    $keywords = str_getcsv($synonyms['simple_words_string'], ' ', '"');
                    foreach ($keywords as $keyword) {
                        if ($keyword && trim($keyword) !== '') {
                            $highlightedWords[] = $keyword;
                        }
                    }
                }
            }
        }

        $requestStack->getCurrentRequest()->getSession()->set('highlightedWords', $highlightedWords);

        $synonyms = null;
        if ($aidSearchClass->getKeyword()) {
            $synonyms = $referenceService->getSynonymes($aidSearchClass->getKeyword());
        }

        // rendu template
        return $this->render('aid/aid/index.html.twig', [
            'formAidSearch' => $formAidSearch->createView(),
            'myPager' => $pagerfanta,
            'blogPromotionPosts' => $blogPromotionPosts,
            'pageTitle' => $pageTitle,
            'showExtended' => $showExtended,
            'formAlertCreate' => $formAlertCreate->createView(),
            'querystring' => $query,
            'perimeterName' => (isset($aidParams['perimeterFrom']) && $aidParams['perimeterFrom'] instanceof Perimeter) ? $aidParams['perimeterFrom']->getName() : '',
            'categoriesName' => $categoriesName,
            'highlightedWords' => $highlightedWords,
            'synonyms' => $synonyms
        ]);
    }

    #[Route('/aides/dupliquer/{slug}/', name: 'app_aid_generic_to_local', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function genericToLocal(
        $slug,
        AidRepository $aidRepository,
        UserService $userService,
        AidService $aidService
    ): Response
    {
        // charge l'aide et verifie qu'elle soit générique
        $aid = $aidRepository->findOneBy(
            [
                'slug' => $slug,
                'isGeneric' => true
            ]
        );
        if (!$aid instanceof Aid) {
            throw new NotFoundHttpException('Cette aide n\'exite pas');
        }

        // le user si dispo
        $user = $userService->getUserLogged();
        if (!$user) {
            return $this->redirectToRoute($aid->getUrl());
        }

        // duplique l'aide en aide locale
        $newAid = new Aid();
        $newAid->setName($aid->getName());
        // le slug est automatique
        $newAid->setDescription($aid->getDescription());
        $newAid->setStatus(Aid::STATUS_DRAFT);
        $newAid->setOriginUrl($aid->getOriginUrl());
        foreach ($aid->getAidAudiences() as $aidAudience) {
            $newAid->addAidAudience($aidAudience);
        }
        foreach ($aid->getAidTypes() as $aidType) {
            $newAid->addAidType($aidType);
        }
        foreach ($aid->getAidDestinations() as $aidDestination) {
            $newAid->addAidDestination($aidDestination);
        }
        $newAid->setDateStart($aid->getDateStart());
        $newAid->setDatePredeposit($aid->getDatePredeposit());
        $newAid->setDateSubmissionDeadline($aid->getDateSubmissionDeadline());
        $newAid->setContactEmail($aid->getContactEmail());
        $newAid->setContactPhone($aid->getContactPhone());
        $newAid->setContactDetail($aid->getContactDetail());
        $newAid->setAuthor($user);
        foreach ($aid->getAidSteps() as $aidStep) {
            $newAid->addAidStep($aidStep);
        }
        $newAid->setEligibility($aid->getEligibility());
        $newAid->setAidRecurrence($aid->getAidRecurrence());
        $newAid->setPerimeter($aid->getPerimeter());
        $newAid->setApplicationUrl($aid->getApplicationUrl());
        // foce à false
        $newAid->setIsImported(false);
        // force a null
        $newAid->setImportUniqueid(null);
        $newAid->setFinancerSuggestion($aid->getFinancerSuggestion());
        $newAid->setImportDataUrl($aid->getImportDataUrl());
        $newAid->setDateImportLastAccess($aid->getDateImportLastAccess());
        $newAid->setImportShareLicence($aid->getImportShareLicence());
        $newAid->setIsCallForProject($aid->isIsCallForProject());
        $newAid->setAmendedAid($aid->getAmendedAid());
        $newAid->setIsAmendment($aid->isIsAmendment());
        $newAid->setAmendmentAuthorName($aid->getAmendmentAuthorName());
        $newAid->setAmendmentComment($aid->getAmendmentComment());
        $newAid->setAmendmentAuthorEmail($aid->getAmendmentAuthorEmail());
        $newAid->setAmendmentAuthorOrg($aid->getAmendmentAuthorOrg());
        $newAid->setSubventionRateMin($aid->getSubventionRateMin());
        $newAid->setSubventionRateMax($aid->getSubventionRateMax());
        $newAid->setSubventionComment($aid->getSubventionComment());
        $newAid->setContact($aid->getContact());
        $newAid->setInstructorSuggestion($aid->getInstructorSuggestion());
        $newAid->setProjectExamples($aid->getProjectExamples());
        $newAid->setPerimeterSuggestion($aid->getPerimeterSuggestion());
        $newAid->setShortTitle($aid->getShortTitle());
        $newAid->setInFranceRelance($aid->isInFranceRelance());
        $newAid->setGenericAid($aid->getGenericAid());
        $newAid->setLocalCharacteristics($aid->getLocalCharacteristics());
        $newAid->setImportDataSource($aid->getImportDataSource());
        $newAid->setEligibilityTest($aid->getEligibilityTest());
        $newAid->setIsGeneric(false);
        $newAid->setImportRawObject($aid->getImportRawObject());
        $newAid->setLoanAmount($aid->getLoanAmount());
        $newAid->setOtherFinancialAidComment($aid->getOtherFinancialAidComment());
        $newAid->setRecoverableAdvanceAmount($aid->getRecoverableAdvanceAmount());
        $newAid->setNameInitial($aid->getNameInitial());
        $newAid->setAuthorNotification($aid->isAuthorNotification());
        $newAid->setImportRawObjectCalendar($aid->getImportRawObjectCalendar());
        $newAid->setImportRawObjectTemp($aid->getImportRawObjectTemp());
        $newAid->setImportRawObjectTempCalendar($aid->getImportRawObjectTempCalendar());
        $newAid->setEuropeanAid($aid->getEuropeanAid());
        $newAid->setImportDataMention($aid->getImportDataMention());
        $newAid->setHasBrokenLink($aid->isHasBrokenLink());
        $newAid->setIsCharged($aid->isIsCharged());
        $newAid->setImportUpdated($aid->isImportUpdated());
        $newAid->setDsId($aid->getDsId());
        $newAid->setDsMapping($aid->getDsMapping());
        $newAid->setDsSchemaExists($aid->isDsSchemaExists());
        $newAid->setContactInfoUpdated($aid->isContactInfoUpdated());
        // on ne reprends pas timePublished
        foreach ($aid->getCategories() as $category) {
            $newAid->addCategory($category);
        }
        foreach ($aid->getKeywords() as $keyWord) {
            $newAid->addKeyword($keyWord);
        }
        foreach ($aid->getPrograms() as $program) {
            $newAid->addProgram($program);
        }
        foreach ($aid->getAidFinancers() as $aidFinancer) {
            $newAidFinancer = new AidFinancer();
            $newAidFinancer->setBacker($aidFinancer->getBacker());
            $newAidFinancer->setPosition($aidFinancer->getPosition());
            $newAid->addAidFinancer($newAidFinancer);
        }
        foreach ($aid->getAidInstructors() as $aidInstructor) {
            $newAidInstructor = new AidInstructor();
            $newAidInstructor->setBacker($aidInstructor->getBacker());
            $newAidInstructor->setPosition($aidInstructor->getPosition());
            $newAid->addAidInstructor($newAidInstructor);
        }
        // on ne reprends pas aidProjects
        // on ne reprends pas aidSuggestedAidProjects
        foreach ($aid->getBundles() as $bundle) {
            $newAid->addBundle($bundle);
        }
        foreach ($aid->getExcludedSearchPages() as $excludedSearchPage) {
            $newAid->addExcludedSearchPage($excludedSearchPage);
        }
        foreach ($aid->getHighlightedSearchPages() as $highlitedSearchPage) {
            $newAid->addHighlightedSearchPage($highlitedSearchPage);
        }
        // on ne reprends pas tous les logs

        // on met les infos de l'aide generic
        $newAid->setGenericAid($aid);

        // on persiste
        $this->managerRegistry->getManager()->persist($newAid);
        $this->managerRegistry->getManager()->flush();

        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Cette aide a été dupliquée'
        );

        // redirection vers la nouvelle aide
        return $this->redirectToRoute('app_user_aid_edit', ['slug' => $newAid->getSlug()]);
    }

    #[Route('/aides/{slug}/', name: 'app_aid_aid_details', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $slug,
        AidRepository $aidRepository,
        Request $request,
        AidService $aidService,
        UserService $userService,
        RequestStack $requestStack,
        EmailService $emailService,
        ParamService $paramService,
        StringService $stringService,
        MatomoService $matomoService,
        ProjectRepository $projectRepository,
        LogService $logService,
        NotificationService $notificationService
    ): Response
    {
        // le user si dispo
        $user = $userService->getUserLogged();

        if (!$user) {
            $requestStack->getCurrentRequest()->getSession()->set('_security.main.target_path', $requestStack->getCurrentRequest()->getRequestUri());
        }

        // charge l'aide
        $aid = $aidRepository->findOneBy(
            [
            'slug' => $slug
            ]
        );
        if (!$aid) {
            throw $this->createNotFoundException('Cette aide n\'existe pas');
        }
        // regarde si aide publié et utilisateur = auteur ou utilisateur = admin
        if (!$aidService->userCanSee($aid, $user)) {
            throw $this->createNotFoundException('Cette aide n\'existe pas');
        }

        // log
        $logService->log(
            type: LogService::AID_VIEW,
            params: [
                'querystring' => parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null,
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'aid' => $aid,
                'organization' => ($user instanceof User && $user->getDefaultOrganization()) ? $user->getDefaultOrganization() : null,
                'user' => ($user instanceof User) ? $user : null,
            ]
        );

        // formulaire ajouter aux projets
        $formAddToProject = $this->createForm(AddAidToProjectType::class, null, [
            'currentAid' => $aid
        ]);
        $formAddToProject->handleRequest($requestStack->getCurrentRequest());
        if ($formAddToProject->isSubmitted()) {
            if ($formAddToProject->isValid()) {
                // association projects existants
                if ($formAddToProject->has('projects')) {
                    $projects = $formAddToProject->get('projects')->getData();
                    /** @var Project $project */
                    foreach ($projects as $project) {
                        $aidProject = new AidProject();
                        $aidProject->setAid($aid);
                        $aidProject->setCreator($user);
                        $project->addAidProject($aidProject);
                        $this->managerRegistry->getManager()->persist($aidProject);

                        // envoi notification à tous les autres membres de l'oganisation
                        if ($project->getOrganization()) {
                            foreach ($project->getOrganization()->getBeneficiairies() as $beneficiary) {
                                if ($beneficiary->getId() == $user->getId()) {
                                    continue;
                                }
                                $notificationService->addNotification(
                                    $beneficiary,
                                    'Nouvelle aide ajoutée à un projet',
                                    '<p>
                                    '.$user->getFirstname().' '.$user->getLastname().' a ajouté une aide au projet
                                    <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL).'">'.$project->getName().'</a>.
                                    </p>'
                                );
                            }
                        }
                        // message
                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'L’aide a bien été associée au projet <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()]).'">'.$project->getName().'</a>.'
                        );
                    }

                    $this->managerRegistry->getManager()->flush();
                }

                $newProject = $formAddToProject->get('newProject')->getData();
                if ($newProject) {
                    $project = new Project();
                    $project->setName($newProject);
                    $project->setAuthor($user);
                    $project->setStatus(Project::STATUS_DRAFT);
                    $project->setOrganization($user->getDefaultOrganization());

                    // on va voir si on peu associer un projet référent
                    if (isset($aidSearchClass) && $aidSearchClass instanceof AidSearchClass && $aidSearchClass->getKeyword()) {
                        $projectReferent = $this->managerRegistry->getRepository(ProjectReference::class)->findOneBy(
                            [
                                'name' => $aidSearchClass->getKeyword()
                            ]
                        );
                        if ($projectReferent instanceof ProjectReference) {
                            $project->setProjectReference($projectReferent);
                        }
                    }

                    $aidProject = new AidProject();
                    $aidProject->setAid($aid);
                    $aidProject->setCreator($user);
                    $project->addAidProject($aidProject);

                    $this->managerRegistry->getManager()->persist($project);
                    $this->managerRegistry->getManager()->flush();

                    $this->addFlash(
                        FrontController::FLASH_SUCCESS,
                        'L’aide a bien été associée au nouveau projet <a href="'.$this->generateUrl('app_user_project_details_fiche_projet', ['id' => $project->getId(), 'slug' => $project->getSlug()]).'">'.$project->getName().'</a>.'
                    );
                }

                
                // redirection page mes projets
                return $this->redirect($requestStack->getCurrentRequest()->getUri());
                // return $this->redirectToRoute('app_user_project_structure');
            }
        }

        // formulaire suggérer cette aide à un projet
        $formSuggestToProject = $this->createForm(SuggestToProjectType::class);
        $formSuggestToProject->handleRequest($requestStack->getCurrentRequest());
        if ($formSuggestToProject->isSubmitted()) {
            if ($formSuggestToProject->isValid()) {
                $projects = $formSuggestToProject->get('projectFavorites')->getData();
                foreach ($projects as $projectId) {
                    $project = $projectRepository->find($projectId);
                    if (!$project instanceof Project) {
                        continue;
                    }
                    $aidSuggestedAidProject = new AidSuggestedAidProject();
                    $aidSuggestedAidProject->setAid($aid);
                    $aidSuggestedAidProject->setProject($project);
                    $aidSuggestedAidProject->setCreator($user);
                    $this->managerRegistry->getManager()->persist($aidSuggestedAidProject);
                    $message = $stringService->cleanString($formSuggestToProject->get('message')->getData());


                    
                    // notification
                    $message = '<p>'.$message.'</p>
                    <ul>
                        <li><a href="'.$aidService->getUrl($aid).'>"'.$aid->getName().'</a></li>
                    </ul>
                    <p>'.$user->getNotificationSignature().'</p>
                    <p>
                        <a class="fr-btn" href="'.$this->generateUrl('app_project_project_public_details', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL).'">
                            Accepter ou rejeter cette recommandation
                        </a>
                    </p>';
                    $notificationService->addNotification(
                        $project->getAuthor(),
                        'Suggestion d’une aide pour votre projet « '.$project->getName().' »',
                        $message
                    );

                    // envoi mail
                    $suggestedAidFinancerName = '';
                    if ($aid->getAidFinancers()) {
                        $suggestedAidFinancerName = $aid->getAidFinancers()[0]->getBacker()->getName() ?? '';
                    }
                    $emailService->sendEmailViaApi(
                        $project->getAuthor()->getEmail(),
                        'Suggestion d’une aide pour votre projet « '.$project->getName().' »',
                        $paramService->get('sib_new_suggested_aid_template_id'),
                        [
                            'PROJECT_AUTHOR_NAME' => $project->getAuthor()->getFullName(),
                            'SUGGESTER_USER_NAME' => $user->getFullName(),
                            'SUGGESTER_ORGANIZATION_NAME' => $user->getDefaultOrganization() ? $user->getDefaultOrganization()->getName() : '',
                            'PROJECT_NAME' => $project->getName(),
                            'SUGGESTED_AID_NAME' => $aid->getName(),
                            'SUGGESTED_AID_FINANCER_NAME' => $suggestedAidFinancerName,
                            'SUGGESTED_AID_RECURRENCE' => $aid->getAidRecurrence() ? $aid->getAidRecurrence()->getName() : '',
                            'FULL_ACCOUNT_URL' => $this->generateUrl('app_user_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
                            'FULL_PROJECT_URL' => $this->generateUrl('app_project_project_public_details', ['id' => $project->getId(), 'slug' => $project->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
                        ],
                        [],
                        ['aide suggérée', $this->getParameter('kernel.environment')], 
                    );

                    // track goal
                    $matomoService->trackGoal($paramService->get('goal_register_id'));
                }
                $this->managerRegistry->getManager()->flush();

                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'L’aide a bien été suggérée à la collectivité, merci pour elle !'
                );

                return $this->redirectToRoute('app_aid_aid_details', ['slug' => $aid->getSlug()]);
            } else {
                $openModalSuggest = true;
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Trouver des aides',
            $this->generateUrl('app_aid_aid')
        );
        $this->breadcrumb->add(
            'Détail de l’aide',
            null,
        );

        $adminEditUrl = $this->generateUrl('admin', [], UrlGeneratorInterface::ABSOLUTE_URL).'?crudAction=edit&crudControllerFqcn=App%5CController%5CAdmin%5CAid%5CAidCrudController&entityId='.$aid->getId();
        
        return $this->render('aid/aid/details.html.twig', [
            'aid' => $aid,
            'open_modal' => $request->query->get('open_modal', null),
            'dsDatas' => $aidService->getDatasFromDs($aid, $user, ($user ? $user->getDefaultOrganization() : null)),
            'formAddToProject' => $formAddToProject->createView(),
            'formSuggestToProject' => $formSuggestToProject->createView(),
            'aidDetailPage' => true,
            'openModalSuggest' => $openModalSuggest ?? false,
            'highlightedWords' => $requestStack->getCurrentRequest()->getSession()->get('highlightedWords', []),
            'adminEditUrl' => $adminEditUrl
        ]);
    }
}
