<?php

namespace App\Controller\Program;

use App\Controller\FrontController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\User\User;
use App\Form\Aid\AidSearchType;
use App\Form\Aid\AidSearchTypeV2;
use App\Repository\Aid\AidRepository;
use App\Repository\Program\ProgramRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
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
        AidRepository $aidRepository,
        RequestStack $requestStack,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        UserService $userService,
        LogService $logService
    ): Response
    {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);
        
        $user = $userService->getUserLogged();

        // le programe
        $program = $programRepository->findOneBy(['slug'=> $slug]);
        if (!$program instanceof Program) {
            return $this->redirectToRoute('app_program_program');
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
            
            // 'removes' => ['programs', 'eurdopeanAid', 'orderBy']
        ];
        // parametre selon url
        // $formAidSearchParams = array_merge(
        //     $formAidSearchParams,
        //     $aidSearchFormService->completeFormAidSearchParams()
        // );

        $aidSearchClass = $aidSearchFormService->getAidSearchClass(
            params: [
                'forceOrganizationType' => null,
                'dontUseUserPerimeter' => true,
                'forcePrograms' => [$program]
                ]
        );

        // formulaire recherche aides
        // $formAidSearch = $this->createForm(
        //     AidSearchType::class,
        //     null,
        //     $formAidSearchParams
        // );
        $formAidSearch = $this->createForm(
            AidSearchTypeV2::class,
            $aidSearchClass,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());

        // check si on affiche ou pas le formulaire étendu
        // $showExtended = $aidSearchFormService->setShowExtended($formAidSearch);
        
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
            'categoriesName' => $categoriesName
        ]);
    }

    private function compareByPosition($a, $b) {
        if ($a['position'] == $b['position']) {
            return 0;
        }
        return ($a['position'] < $b['position']) ? -1 : 1;
    }
}
