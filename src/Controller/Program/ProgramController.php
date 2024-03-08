<?php

namespace App\Controller\Program;

use App\Controller\FrontController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\Aid\AidSearchTypeV2;
use App\Repository\Aid\AidRepository;
use App\Repository\Program\ProgramRepository;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority:1)]
class ProgramController extends FrontController
{
    const NB_AID_BY_PAGE = 18;

    #[Route('/programmes/', name: 'app_program_program')]
    public function index(
        ProgramRepository $programRepository
    ): Response
    {
        // les programmes
        $programs = $programRepository->findBy([],['slug'=> 'ASC']);

        // fil arianne
        $this->breadcrumb->add(
            'Tous les programmes d’aides',
            null
        );

        // rendu template
        return $this->render('program/program/index.html.twig', [
            'programs' => $programs
        ]);
    }

    #[Route('/programmes/{slug}/', name: 'app_program_details', requirements: ['slug' => '[a-zA-Z0-9\-_]+'])]
    public function details(
        $slug,
        ProgramRepository $programRepository,
        SearchPageRepository $searchPageRepository,
        RequestStack $requestStack,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        UserService $userService,
        LogService $logService,
        ReferenceService $referenceService
    ): Response
    {
        // si onglet selectionne
        $tabSelected = $requestStack->getCurrentRequest()->get('tab', null);

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        
        $user = $userService->getUserLogged();

        // le programe
        $program = $programRepository->findOneBy(['slug'=> $slug]);
        if (!$program instanceof Program) {
            return $this->redirectToRoute('app_program_program');
        }

        // redirection ecoquartier
        if ($program->getSlug() == 'ecoquartier') {
            $searchPage = $searchPageRepository->findOneBy(['slug' => 'ecoquartier']);
            if ($searchPage instanceof SearchPage) {
                return $this->redirectToRoute('app_portal_portal_details', ['slug' => $searchPage->getSlug()]);
            }
        }

        // FAQ
        $faq_questions_answers_date_updated = date('2000-01-01');

        $faqsCategoriesById = [];

        foreach($program->getFaqQuestionAnswsers() as $faq){
            if($faq->getTimeUpdate()>$faq_questions_answers_date_updated){
                $faq_questions_answers_date_updated = $faq->getTimeUpdate();
            }
            if(!isset($faqsCategoriesById[$faq->getFaqCategory()->getId()])){
                $faqsCategoriesById[$faq->getFaqCategory()->getId()] = array(
                    'position' => $faq->getFaqCategory()->getPosition(),
                    'category' => $faq->getFaqCategory(),
                    'faqs'  => []
                );
            }
            $faqsCategoriesById[$faq->getFaqCategory()->getId()]['faqs'][] = $faq;
        }

        usort($faqsCategoriesById, [$this, 'compareByPosition']);

        // form recherche d'aide
        $formAidSearchParams = [
            'method' => 'GET',
            'action' => '#aid-list',
            'extended' => true,
            'removes' => ['orderBy'],
        ];

        // parametre selon url
        $aidSearchClass = $aidSearchFormService->getAidSearchClass(
            params: [
                'forceOrganizationType' => null,
                'dontUseUserPerimeter' => true,
                'forcePrograms' => [$program]
                ]
        );

        // formulaire recherche aides
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());
        
        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
            'programs' => [$program],
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
        $showExtended = $aidSearchFormService->setShowExtendedV2($aidSearchClass);
        
        // le paginateur
        $aids = $aidService->searchAids($aidParams);
        $adapter = new ArrayAdapter($aids);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_AID_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        $query = parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;

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
            'source' => $program->getSlug()
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
        
        // log vue programme
        $logService->log(
            type: LogService::PROGRAM_VIEW,
            params: [
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'program' => $program,
                'organization' => $userService->getUserLogged() ? $userService->getUserLogged()->getDefaultOrganization() : null,
                'user' => $userService->getUserLogged(),
            ]
        );
        
        // fil arianne
        $this->breadcrumb->add(
            'Tous les programmes d’aides',
            $this->generateUrl('app_program_program')
        );

        // fil arianne
        $this->breadcrumb->add(
            $program->getName()
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
                        // on met la recherche dans les highlights
                        $keywords = explode(' ', $aidSearchClass->getKeyword());
                        foreach ($keywords as $keyword) {
                            if ($keyword && trim($keyword) !== '') {
                                $highlightedWords[] = $keyword;
                            }
                        }
                        // on va chercher les synonymes
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
            
        // rendu template
        return $this->render('program/program/details.html.twig', [
            'program' => $program,
            'faq_questions_answers_date_updated' => $faq_questions_answers_date_updated,
            'faqsCategoriesById'   => $faqsCategoriesById,
            'myPager' => $pagerfanta,
            'formAidSearch' => $formAidSearch->createView(),
            'formAidSearchNoOrder' => true,
            'showExtended' => $showExtended,
            'querystring' => $query,
            'perimeterName' => (isset($aidParams['perimeterFrom']) && $aidParams['perimeterFrom'] instanceof Perimeter) ? $aidParams['perimeterFrom']->getName() : '',
            'categoriesName' => $categoriesName,
            'tabSelected' => $tabSelected
        ]);
    }

    private function compareByPosition($a, $b) {
        if ($a['position'] == $b['position']) {
            return 0;
        }
        return ($a['position'] < $b['position']) ? -1 : 1;
    }
}
