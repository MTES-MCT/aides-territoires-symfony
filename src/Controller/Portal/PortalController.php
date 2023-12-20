<?php

namespace App\Controller\Portal;

use App\Controller\Aid\AidController;
use App\Controller\FrontController;
use App\Entity\Alert\Alert;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Search\SearchPage;
use App\Form\Aid\AidSearchType;
use App\Form\Alert\AlertCreateType;
use App\Repository\Aid\AidRepository;
use App\Repository\Portal\PortalRepository;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PortalController extends FrontController
{
    // c'est une page custom
    #[Route('/portails/', name: 'app_portal_portal')]
    // public function index(): Response
    // {
    //     return $this->render('portal/portal/index.html.twig', [
    //         'controller_name' => 'PortalController',
    //     ]);
    // }

    #[Route('/portails/{slug}/', name: 'app_portal_portal_details')]
    public function details(
        $slug,
        SearchPageRepository $searchPageRepository,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        RequestStack $requestStack,
        AidRepository $aidRepository,
        UserService $userService,
        ManagerRegistry $managerRegistry
    ): Response
    {

        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);


        // charge le portail
        $search_page = $searchPageRepository->findOneBy(
            [
                'slug' => $slug
            ]
            );
        if (!$search_page instanceof SearchPage) {
            return $this->redirectToRoute('app_portal_portal');
        }
;
        // converti la querystring en parametres
        $aidParams = [
            'showInSearch' => true,
        ];
        $queryString = null;
        try {
            // certaines pages ont un querystring avec https://... d'autres directement les parametres
            $query = parse_url($search_page->getSearchQuerystring())['query'] ?? null;
            $queryString = $query ?? $search_page->getSearchQuerystring();

            $aidParams = array_merge($aidParams, $aidSearchFormService->convertQuerystringToParams($queryString));
            
        } catch (\Exception $e) {
        }

         // form recherche d'aide
        $formAidSearchParams = [
            'method' => 'GET',
            'action' => '#aid-list',
            'extended' => true,
            'forceOrganizationType' => null,
            'dontUseUserPerimeter' => true
        ];

        // parametre selon url
        $formAidSearchParams = array_merge(
            $formAidSearchParams,
            $aidSearchFormService->completeFormAidSearchParams($queryString)
        );
        // formulaire recherche aides
        $formAidSearch = $this->createForm(
            AidSearchType::class,
            null,
            $formAidSearchParams
        );
        $formAidSearch->handleRequest($requestStack->getCurrentRequest());

        // check si on affiche ou pas le formulaire étendu
        $showExtended = $aidSearchFormService->setShowExtended($formAidSearch);

        // parametres pour requetes aides
        $aidParams = array_merge($aidParams, $aidSearchFormService->completeAidParams($formAidSearch));
        // transforme le orderBy
        $aidParams = $aidSearchFormService->handleOrderBy($aidParams);

        // le paginateur
        $aids = $aidRepository->findCustom($aidParams);
        $aidParams['searchPage'] = $search_page;
        $aids = $aidService->postPopulateAids($aids, $aidParams);
        $adapter = new ArrayAdapter($aids);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(AidController::NB_AID_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // fil arianne
        $this->breadcrumb->add(
            'Portails',
            $this->generateUrl('app_portal_portal')
        );
        $this->breadcrumb->add(
            $search_page->getName(),
            null
        );

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
                        $alertlQueryString = $requestStack->getCurrentRequest()->server->get('QUERY_STRING', null);
                        if (!$alertlQueryString) {
                            $alertlQueryString = $queryString;
                        }
                        $alert->setQuerystring($alertlQueryString);
                        $alert->setSource(Alert::SOURCE_AIDES_TERRITOIRES);
    
                        $managerRegistry->getManager()->persist($alert);
                        $managerRegistry->getManager()->flush();

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

        return $this->render('portal/portal/details.html.twig', [
            'search_page' => $search_page,
            'myPager' => $pagerfanta,
            'formAidSearch' => $formAidSearch->createView(),
            'showExtended' => $showExtended,
            'formAlertCreate' => $formAlertCreate
        ]);
    }
}
