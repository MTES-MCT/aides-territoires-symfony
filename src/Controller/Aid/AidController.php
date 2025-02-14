<?php

namespace App\Controller\Aid;

use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use App\Entity\Aid\AidSuggestedAidProject;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Exception\NotFoundException\AidNotFoundException;
use App\Form\Aid\AidSearchTypeV2;
use App\Form\Aid\SuggestToProjectType;
use App\Form\Alert\AlertCreateType;
use App\Form\Project\AddAidToProjectType;
use App\Repository\Aid\AidRepository;
use App\Repository\Blog\BlogPromotionPostRepository;
use App\Repository\Project\ProjectRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Api\VappApiService;
use App\Service\Blog\BlogPromotionPostService;
use App\Service\Email\EmailService;
use App\Service\Export\SpreadsheetExporterService;
use App\Service\Log\LogService;
use App\Service\Matomo\MatomoService;
use App\Service\Notification\NotificationService;
use App\Service\Reference\ReferenceService;
use App\Service\Site\AbTestService;
use App\Service\User\UserService;
use App\Service\Various\Breadcrumb;
use App\Service\Various\ParamService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AidController extends FrontController
{
    public const NB_AID_BY_PAGE = 18;

    public function __construct(
        public Breadcrumb $breadcrumb,
        public TranslatorInterface $translatorInterface,
        public ManagerRegistry $managerRegistry,
    ) {
        parent::__construct($breadcrumb, $translatorInterface);
    }

    #[Route('/aides/', name: 'app_aid_aid')]
    public function index(
        RequestStack $requestStack,
        BlogPromotionPostRepository $blogPromotionPostRepository,
        UserService $userService,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        LogService $logService,
        ReferenceService $referenceService,
        BlogPromotionPostService $blogPromotionPostService,
        VappApiService $vappApiService,
        AbTestService $abTestService,
    ): Response {
        $timeStart = microtime(true);

        // la session
        $session = $requestStack->getCurrentRequest()->getSession();

        // est ce que l'ab test vapp est activé
        $isVappFormulaire = $abTestService->shouldShowTestVersion(AbTestService::VAPP_FORMULAIRE);

        $requestStack
            ->getCurrentRequest()
            ->getSession()
            ->set(
                '_security.main.target_path',
                $requestStack->getCurrentRequest()->getRequestUri()
            )
        ;
        $user = $userService->getUserLogged();

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        // paramètres du formulaire
        $formAidSearchParams = [
            'method' => 'GET',
            'extended' => true,
            'isPageAid' => true
        ];

        // formulaire recherche aides
        $aidSearchClass = $aidSearchFormService->getAidSearchClass();
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );

        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
        ];

        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
        if (isset($aidParams['projectReference']) && $aidParams['projectReference'] instanceof ProjectReference) {
            $session->set('aidParamsPrId', $aidParams['projectReference']->getId());
        }

        $query = parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;

        // le paginateur
        $timeAidStart = microtime(true);
        $aids = $aidService->searchAidsV3($aidParams);

        // ************************************* */
        // TEST VAPP
        if ($isVappFormulaire) {
            // réinitialise la page courange
            $session->set(VappApiService::SESSION_CURRENT_PAGE_SCORE_VAPP, 0);

            $vappAidsById = [];
            foreach ($aids as $aid) {
                $vappAidsById[$aid->getId()] = [
                    'id' => $aid->getId(),
                    'score_total' => $aid->getScoreTotal(),
                ];
            }

            // on met $vappAidsById dans la session
            $requestStack->getCurrentRequest()->getSession()->set(VappApiService::SESSION_AIDS_SCORES, $vappAidsById);

            // créar un nouveau projet si besoin
            $vappApiService->getProjectUuid(
                description: $aidSearchClass->getVappDescription(),
                porteur: strtolower($aidSearchClass->getOrganizationTypeSlug()),
                zonesGeographiques: [
                    [
                        'type' => ucfirst(
                            Perimeter::SCALES_FOR_SEARCH[$aidSearchClass->getPerimeterId()->getScale()]['name']
                        ),
                        // 'code' => (int) $aidSearchClass->getPerimeterId()->getZipCodes()[0] ?? ''
                        'code' => 69266,
                    ],
                ],
                force: true
            );
        }
        // ************************************* */

        $timeAidEnd = microtime(true);
        $executionTimeAid = $timeAidEnd - $timeAidStart;
        try {
            $adapter = new ArrayAdapter($aids);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage(self::NB_AID_BY_PAGE);
            $pagerfanta->setCurrentPage($currentPage);

            // Recharger les entités complètes pour la page courante
            $pageResults = $aidService->hydrateLightAids(
                lightAids: $pagerfanta->getCurrentPageResults(),
                aidParams: $aidParams
            );
        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                FrontController::FLASH_ERROR,
                'Le numéro de page demandé n\'existe pas'
            );
            $newUrl = preg_replace(
                '/(page=)[^\&]+/',
                'page=' . $pagerfanta->getNbPages(),
                $requestStack->getCurrentRequest()->getRequestUri()
            );

            return new RedirectResponse($newUrl);
        }

        // Log recherche
        $logService->log(
            type: LogService::AID_SEARCH,
            params: $logService->getLogAidSearchParams(
                aidParams: $aidParams,
                resultsCount: $pagerfanta->getNbResults(),
            ),
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
        if ($formAidSearch->get(AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG)->getData()) {
            $pageTitle .= ' Structure : '
                . $formAidSearch->get(AidSearchFormService::QUERYSTRING_KEY_ORGANIZATION_TYPE_SLUG)
                    ->getData()->getName()
                . ' ';
        }
        if ($formAidSearch->get(AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER)->getData()) {
            $pageTitle .= ' - Périmètre : '
                . $formAidSearch->get(AidSearchFormService::QUERYSTRING_KEY_SEARCH_PERIMETER)->getData()->getName()
                . ' ';
        }

        /** @var AidSearchClass $data */
        $data = $formAidSearch->getData();
        $nbCriteria = $aidSearchFormService->countNbCriteriaFromAidSearchClass($data);

        if ($nbCriteria > 0) {
            $pageTitle .= ' - ' . $nbCriteria . ' autre';
            if ($nbCriteria > 1) {
                $pageTitle .= 's';
            }
            $pageTitle .= ' critère';
            if ($nbCriteria > 1) {
                $pageTitle .= 's';
            }
        }

        // check si on affiche ou pas le formulaire étendu
        $showExtended = $aidSearchFormService->setShowExtended($aidSearchClass);

        // formulaire creer alerte
        $alert = new Alert();
        $formAlertCreate = $this->createForm(AlertCreateType::class, $alert);
        $formAlertCreate->handleRequest($requestStack->getCurrentRequest());
        if ($formAlertCreate->isSubmitted()) {
            if ($formAlertCreate->isValid()) {
                /** @var User $user */
                $user = $userService->getUserLogged();
                if ($user instanceof User && $user->getId()) {
                    try {
                        $queryString = trim($aidSearchFormService->convertAidSearchClassToQueryString($aidSearchClass));
                        if ('' == $queryString) {
                            throw new \Exception('Veuillez sélectionner au moins un critère de recherche');
                        }
                        $alert->setEmail($user->getEmail());
                        $alert->setQuerystring($queryString);
                        $alert->setSource(Alert::SOURCE_AIDES_TERRITOIRES);

                        $this->managerRegistry->getManager()->persist($alert);
                        $this->managerRegistry->getManager()->flush();

                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'Votre alerte a bien été créée'
                        );
                    } catch (\Exception $e) {
                        $message = 'Veuillez sélectionner au moins un critère de recherche' ==
                            $e->getMessage()
                            ? 'Veuillez sélectionner au moins un critère de recherche'
                            : 'Une erreur est survenue lors de la création de votre alerte';
                        $this->addFlash(
                            FrontController::FLASH_ERROR,
                            $message
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
        $synonyms = null;
        $highlightedWords = [];
        if ($aidSearchClass->getKeyword()) {
            $synonyms = $referenceService->getSynonymes($aidSearchClass->getKeyword());
            $highlightedWords = $referenceService->setHighlightedWords($synonyms, $aidSearchClass->getKeyword());
        }

        $timeEnd = microtime(true);
        $executionTime = $timeEnd - $timeStart;

        // rendu template
        return $this->render('aid/aid/index.html.twig', [
            'formAidSearch' => $formAidSearch->createView(),
            'myPager' => $pagerfanta,
            'blogPromotionPosts' => $blogPromotionPosts,
            'pageTitle' => $pageTitle,
            'showExtended' => $showExtended,
            'formAlertCreate' => $formAlertCreate->createView(),
            'querystring' => $query,
            'perimeterName' => (isset($aidParams['perimeterFrom']) && $aidParams['perimeterFrom'] instanceof Perimeter)
                    ? $aidParams['perimeterFrom']->getName()
                    : '',
            'categoriesName' => $categoriesName,
            'highlightedWords' => $highlightedWords,
            'synonyms' => $synonyms,
            'executionTime' => round($executionTime * 1000),
            'memoryUsage' => round(memory_get_peak_usage() / 1024 / 1024),
            'executionTimeAid' => round($executionTimeAid * 1000),
            'pageResults' => $pageResults,
            'isVappFormulaire' => $isVappFormulaire,
        ]);
    }

    #[Route('/aides/ajax-call-vapp/', name: 'app_aid_ajax_call_vapp', options: ['expose' => true])]
    public function ajaxCallVapp(
        RequestStack $requestStack,
        VappApiService $vappApiService,
        AidService $aidService
    ): JsonResponse {
        $session = $requestStack->getCurrentRequest()->getSession();

        //  le nombre d'aides qu'on va score à chaque appel
        $nbToScore = 10;

        // la page courante depuis la session
        $currentPageScoreVapp = $session->get(VappApiService::SESSION_CURRENT_PAGE_SCORE_VAPP, 0);

        if (null === $currentPageScoreVapp) {
            // si non trouvé on le met en session
            $currentPageScoreVapp = 0;
        }

        // recupère le résultat de la recherche en session
        $vappAidsById = $session->get(VappApiService::SESSION_AIDS_SCORES, []);

        // on fait un nouveau tableau des aides par paquet pour envoyer à Vapp en plusieur fois
        $aidsChunks = array_chunk($vappAidsById, $nbToScore, true);

        $aidsChunksToScore = $aidsChunks[$currentPageScoreVapp] ?? null;
        if (!$aidsChunksToScore) {
            // il n'y a plus d'aides à score
            return new JsonResponse(['status' => 'done']);
        }

        // recupere les infos pour vapp
        $aidsToScore = $aidService->hydrateLightAidsForVapp(
            lightAids: $aidsChunksToScore
        );

        // on score les aides
        $vappScores = $vappApiService->scoreAids($aidsToScore);

        // Transformation du tableau
        $scores = array_combine(
            array_column($vappScores, 'id'),
            array_column($vappScores, 'scoreCompatibilite')
        );

        // on met à jour le tableau en session
        $vappAidsById = $session->get(VappApiService::SESSION_AIDS_SCORES, []);
        foreach ($scores as $id => $score) {
            if (isset($vappAidsById[$id])) {
                $vappAidsById[$id]['score_vapp'] = $score;
            }
        }
        $session->set(VappApiService::SESSION_AIDS_SCORES, $vappAidsById);

        // on met le numero de page suivante en session
        $session->set(VappApiService::SESSION_CURRENT_PAGE_SCORE_VAPP, $currentPageScoreVapp + 1);

        // on complete aidsChunksToScore avec le score Vapp
        foreach ($aidsChunksToScore as $key => $aid) {
            $aidsChunksToScore[$key]['score_vapp'] = $scores[$aid['id']] ?? 0;
        }

        // retjour json des aides
        return new JsonResponse([
            'status' => 'success',
            'aidsChunksToScore' => $aidsChunksToScore,
        ]);
    }

    #[Route('/aides/ajax-render-aid-card/', name: 'app_aid_ajax_render_aid_card', options: ['expose' => true])]
    public function ajaxRenderAidCard(
        RequestStack $requestStack,
        AidRepository $aidRepository,
        AidService $aidService,
        UserService $userService,
        ProjectReferenceRepository $projectReferenceRepository,
    ): JsonResponse {
        try {
            // verifie id aide
            $aidId = $requestStack->getCurrentRequest()->get('aidId', null);
            $scoreVapp = $requestStack->getCurrentRequest()->get('scoreVapp', 0);

            if (!$aidId) {
                throw new \Exception('Id Aide manquant');
            }

            // charge aide
            $aid = $aidRepository->find($aidId);
            if (!$aid) {
                throw new \Exception('Aide non trouvée');
            }

            // regarde si aide consultable
            $user = $userService->getUserLogged();
            if (!$aidService->userCanSee($aid, $user)) {
                throw new \Exception('Aide non trouvée');
            }

            // essaye de recuperer les aidParams si disponibles
            $session = $requestStack->getCurrentRequest()->getSession();
            $aidParamsPrId = $session->get('aidParamsPrId', null);
            if ($aidParamsPrId) {
                $projectRefence = $projectReferenceRepository->find($aidParamsPrId);
                if ($projectRefence instanceof ProjectReference) {
                    foreach ($aid->getProjectReferences() as $projectReferenceAid) {
                        if ($projectReferenceAid->getId() == $projectRefence->getId()) {
                            $aid->addProjectReferenceSearched($projectRefence);
                        }
                    }
                }
            }

            // rendu template
            $cardHtml = $this->renderView('aid/aid/_aid_result.html.twig', [
                'aid' => $aid,
                'scoreVapp' => $scoreVapp,
                'dsDatas' => $aidService->getDatasFromDs($aid, $user, $user ? $user->getDefaultOrganization() : null),
            ]);

            return new JsonResponse([
                'status' => 'success',
                'cardHtml' => $cardHtml,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    #[Route('/aides/exporter/', name: 'app_aid_export')]
    public function exportList(
        AidService $aidService,
        AidSearchFormService $aidSearchFormService,
        SpreadsheetExporterService $spreadsheetExporterService,
    ): StreamedResponse {
        ini_set('memory_limit', '1G');

        // recupere les parametres de recherche
        $aidSearchClass = $aidSearchFormService->getAidSearchClass();

        $aidParams = [
            'showInSearch' => true,
        ];

        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));

        // recupere les aides
        $aids = $aidService->searchAidsV3($aidParams);
        $aids = $aidService->hydrateLightAids(
            lightAids: $aids,
            aidParams: $aidParams
        );

        return new StreamedResponse(function () use ($aids, $spreadsheetExporterService) {
            return $spreadsheetExporterService->getXlsxFromArray(
                $aids,
                Aid::class,
                'export_recherche_aides_' . date('Y-m-d')
            );
        });
    }

    #[Route('/aides/dupliquer/{slug}/', name: 'app_aid_generic_to_local', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function genericToLocal(
        string $slug,
        AidRepository $aidRepository,
        UserService $userService,
        AidService $aidService,
    ): Response {
        // charge l'aide et verifie qu'elle soit générique
        $aid = $aidRepository->findOneBy(
            [
                'slug' => $slug,
                'isGeneric' => true,
            ]
        );
        if (!$aid instanceof Aid) {
            throw new NotFoundHttpException('Cette aide n\'exite pas');
        }

        // le user si dispo
        $user = $userService->getUserLogged();
        if (!$user) {
            return $this->redirect($aid->getUrl());
        }

        // duplique l'aide
        $newAid = $aidService->duplicateAid($aid, $user);

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
        string $slug,
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
        NotificationService $notificationService,
    ): Response {
        // le user si dispo
        $user = $userService->getUserLogged();

        if (!$user) {
            $requestStack
                ->getCurrentRequest()
                ->getSession()
                ->set(
                    '_security.main.target_path',
                    $requestStack->getCurrentRequest()->getRequestUri()
                )
            ;
        }

        // charge l'aide
        $aid = $aidRepository->findOneBy(
            [
                'slug' => $slug,
            ]
        );
        if (!$aid) {
            throw new AidNotFoundException('Cette aide n\'existe pas');
        }
        // regarde si aide publié et utilisateur = auteur ou utilisateur = admin
        if (!$aidService->userCanSee($aid, $user)) {
            throw new AidNotFoundException('Cette aide n\'existe pas');
        }

        // log seulement si l'aide à le statut publiée
        if (Aid::STATUS_PUBLISHED == $aid->getStatus()) {
            $logService->log(
                type: LogService::AID_VIEW,
                params: [
                    'querystring' => parse_url(
                        $requestStack->getCurrentRequest()->getRequestUri(),
                        PHP_URL_QUERY
                    )
                        ?? null,
                    'host' => $requestStack->getCurrentRequest()->getHost(),
                    'aid' => $aid,
                    'organization' => ($user instanceof User && $user->getDefaultOrganization())
                            ? $user->getDefaultOrganization()
                            : null,
                    'user' => ($user instanceof User) ? $user : null,
                ]
            );
        }

        // formulaire ajouter aux projets
        $formAddToProject = $this->createForm(AddAidToProjectType::class, null, [
            'currentAid' => $aid,
        ]);
        $formAddToProject->handleRequest($requestStack->getCurrentRequest());
        if ($formAddToProject->isSubmitted()) {
            if (!$user->getDefaultOrganization()) {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Vous devez renseigner les informations de votre structure ou accepter une invitation '
                        . 'avant de pouvoir accéder à cette page.'
                );
            } else {
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
                                        ' . $user->getFirstname()
                                        . ' '
                                        . $user->getLastname()
                                        . ' a ajouté une aide au projet
                                        <a href="'
                                        . $this->generateUrl(
                                            'app_user_project_details_fiche_projet',
                                            ['id' => $project->getId(), 'slug' => $project->getSlug()],
                                            UrlGeneratorInterface::ABSOLUTE_URL
                                        )
                                            . '">' . $project->getName() . '</a>.
                                        </p>'
                                    );
                                }
                            }

                            // message
                            $this->addFlash(
                                FrontController::FLASH_SUCCESS,
                                'L’aide a bien été associée au projet <a href="'
                                . $this->generateUrl(
                                    'app_user_project_details_fiche_projet',
                                    ['id' => $project->getId(), 'slug' => $project->getSlug()]
                                )
                                . '">' . $project->getName() . '</a>.'
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

                        $aidProject = new AidProject();
                        $aidProject->setAid($aid);
                        $aidProject->setCreator($user);
                        $project->addAidProject($aidProject);

                        $this->managerRegistry->getManager()->persist($project);
                        $this->managerRegistry->getManager()->flush();

                        $this->addFlash(
                            FrontController::FLASH_SUCCESS,
                            'L’aide a bien été associée au nouveau projet <a href="'
                            . $this->generateUrl(
                                'app_user_project_details_fiche_projet',
                                ['id' => $project->getId(), 'slug' => $project->getSlug()]
                            )
                            . '">' . $project->getName() . '</a>.'
                        );
                    }

                    // redirection page mes projets
                    return $this->redirect($requestStack->getCurrentRequest()->getUri());
                } else {
                    $this->addFlash(
                        FrontController::FLASH_ERROR,
                        'Une erreur est survenue lors de l\'association de l\'aide au projet'
                    );
                }
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
                    $message = $stringService->cleanString((string) $formSuggestToProject->get('message')->getData());

                    // notification
                    $message = '<p>' . $message . '</p>
                    <ul>
                        <li><a href="' . $aidService->getUrl($aid) . '>"' . $aid->getName() . '</a></li>
                    </ul>
                    <p>' . $user->getNotificationSignature() . '</p>
                    <p>
                        <a class="fr-btn" href="'
                            . $this->generateUrl(
                                'app_project_project_public_details',
                                ['id' => $project->getId(), 'slug' => $project->getSlug()],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            )
                            . '">
                            Accepter ou rejeter cette recommandation
                        </a>
                    </p>';
                    $notificationService->addNotification(
                        $project->getAuthor(),
                        'Suggestion d’une aide pour votre projet « ' . $project->getName() . ' »',
                        $message
                    );

                    // envoi mail
                    $suggestedAidFinancerName = '';
                    if (!$aid->getAidFinancers()->isEmpty()) {
                        $suggestedAidFinancerName = $aid->getAidFinancers()[0]->getBacker()->getName() ?? '';
                    }
                    $emailService->sendEmailViaApi(
                        $project->getAuthor()->getEmail(),
                        'Suggestion d’une aide pour votre projet « ' . $project->getName() . ' »',
                        (int) $paramService->get('sib_new_suggested_aid_template_id'),
                        [
                            'PROJECT_AUTHOR_NAME' => $project->getAuthor()->getFullName(),
                            'SUGGESTER_USER_NAME' => $user->getFullName(),
                            'SUGGESTER_ORGANIZATION_NAME' => $user->getDefaultOrganization() ?
                                $user->getDefaultOrganization()->getName()
                                : '',
                            'PROJECT_NAME' => $project->getName(),
                            'SUGGESTED_AID_NAME' => $aid->getName(),
                            'SUGGESTED_AID_FINANCER_NAME' => $suggestedAidFinancerName,
                            'SUGGESTED_AID_RECURRENCE' => $aid->getAidRecurrence()
                                ? $aid->getAidRecurrence()->getName()
                                : '',
                            'FULL_ACCOUNT_URL' => $this->generateUrl(
                                'app_user_dashboard',
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            'FULL_PROJECT_URL' => $this->generateUrl(
                                'app_project_project_public_details',
                                ['id' => $project->getId(), 'slug' => $project->getSlug()],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                        ],
                        [],
                        ['aide suggérée', $this->getParameter('kernel.environment')],
                    );

                    // track goal
                    $matomoService->trackGoal((int) $paramService->get('goal_register_id'));
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

        $adminEditUrl = $this->generateUrl(
            'admin',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        )
        . '?crudAction=edit&crudControllerFqcn=App%5CController%5CAdmin%5CAid%5CAidCrudController&entityId='
        . $aid->getId();

        return $this->render('aid/aid/details.html.twig', [
            'aid' => $aid,
            'open_modal' => $request->query->get('open_modal', null),
            'dsDatas' => $aidService->getDatasFromDs($aid, $user, $user ? $user->getDefaultOrganization() : null),
            'formAddToProject' => $formAddToProject->createView(),
            'formSuggestToProject' => $formSuggestToProject->createView(),
            'aidDetailPage' => true,
            'openModalSuggest' => $openModalSuggest ?? false,
            'highlightedWords' => $requestStack->getCurrentRequest()->getSession()->get('highlightedWords', []),
            'adminEditUrl' => $adminEditUrl,
        ]);
    }
}
