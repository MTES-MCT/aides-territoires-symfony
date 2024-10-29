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
use App\Service\Page\FaqService;
use App\Service\Aid\AidSearchClass;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\Log\LogService;
use App\Service\Reference\ReferenceService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(priority: 1)]
class ProgramController extends FrontController
{
    public const NB_AID_BY_PAGE = 18;

    #[Route('/programmes/', name: 'app_program_program')]
    public function index(
        ProgramRepository $programRepository
    ): Response {
        // les programmes
        $programs = $programRepository->findBy([], ['slug' => 'ASC']);

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
        ReferenceService $referenceService,
        FaqService $faqService
    ): Response {
        // si onglet selectionne
        $tabSelected = $requestStack->getCurrentRequest()->get('tab', null);

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        $user = $userService->getUserLogged();

        // le programe
        $program = $programRepository->findOneBy(['slug' => $slug]);
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

        // parametres pour requetes aides
        $aidParams = [
            'showInSearch' => true,
            'programs' => [$program],
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
        $showExtended = $aidSearchFormService->setShowExtended($aidSearchClass);

        // le paginateur
        $aids = $aidService->searchAids($aidParams);
        try {
            $adapter = new ArrayAdapter($aids);
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage(self::NB_AID_BY_PAGE);
            $pagerfanta->setCurrentPage($currentPage);
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
        $query = parse_url($requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;

        // Log recherche
        $logParams = [
            'organizationTypes' => (isset($aidParams['organizationType'])) ? [$aidParams['organizationType']] : null,
            'querystring' => $query ?? null,
            'resultsCount' => $pagerfanta->getNbResults(),
            'host' => $requestStack->getCurrentRequest()->getHost(),
            'perimeter' => $aidParams['perimeterFrom'] ?? null,
            'search' => $aidParams['keyword'] ?? null,
            'organization' => ($user instanceof User && $user->getDefaultOrganization())
                ? $user->getDefaultOrganization()
                : null,
            'backers' => $aidParams['backers'] ?? null,
            'categories' => $aidParams['categories'] ?? null,
            'programs' => $aidParams['programs'] ?? null,
            'source' => $program->getSlug(),
            'user' => $user ?? null
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
                'organization' => $userService->getUserLogged()
                    ? $userService->getUserLogged()->getDefaultOrganization()
                    : null,
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
        $synonyms = null;
        $highlightedWords = [];
        if ($aidSearchClass->getKeyword()) {
            $synonyms = $referenceService->getSynonymes($aidSearchClass->getKeyword());
            $highlightedWords = $referenceService->setHighlightedWords($synonyms, $aidSearchClass->getKeyword());
        }

        // date de dernière mise à jour des FAQ
        foreach ($program->getPageTabs() as $pageTab) {
            foreach ($pageTab->getFaqs() as $faq) {
                $faq->setLatestUpdateTime($faqService->getLatestUpdateTime($faq));
            }
        }

        // rendu template
        return $this->render('program/program/details.html.twig', [
            'program' => $program,
            'myPager' => $pagerfanta,
            'formAidSearch' => $formAidSearch->createView(),
            'formAidSearchNoOrder' => true,
            'showExtended' => $showExtended,
            'querystring' => $query,
            'perimeterName' =>
                (
                    isset($aidParams['perimeterFrom'])
                    && $aidParams['perimeterFrom'] instanceof Perimeter
                )
                ? $aidParams['perimeterFrom']->getName()
                : '',
            'categoriesName' => $categoriesName,
            'tabSelected' => $tabSelected,
            'highlightedWords' => $highlightedWords
        ]);
    }

    private function compareByPosition($a, $b)
    {
        if ($a['position'] == $b['position']) {
            return 0;
        }
        return ($a['position'] < $b['position']) ? -1 : 1;
    }
}
