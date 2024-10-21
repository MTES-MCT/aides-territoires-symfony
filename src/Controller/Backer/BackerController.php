<?php

namespace App\Controller\Backer;

use App\Controller\FrontController;
use App\Entity\Backer\Backer;
use App\Repository\Aid\AidRepository;
use App\Repository\Backer\BackerRepository;
use App\Security\Voter\InternalRequestVoter;
use App\Service\Api\InternalApiService;
use App\Service\Backer\BackerService;
use App\Service\Log\LogService;
use App\Service\User\UserService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackerController extends FrontController
{
    #[Route('/partenaires/', name: 'app_backer_backer')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_cartography_cartography');
    }

    #[Route(
        '/partenaires/{id}-{slug}/',
        name: 'app_backer_details',
        requirements: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9\-_]+']
    )]
    public function details(
        $id,
        $slug,
        BackerRepository $backerRepository,
        AidRepository $aidRepository,
        LogService $logService,
        UserService $userService,
        RequestStack $requestStack,
        BackerService $backerService
    ): Response {
        $user = $userService->getUserLogged();

        // charge backer
        $backer = $backerRepository->findOneBy(
            [
                'id' => $id,
                'slug' => $slug,
            ]
        );
        if (!$backer instanceof Backer) {
            return $this->redirectToRoute('app_home');
        }

        // si la fiche n'est pas active, seuls les membres de l'organization ou les admins peuvent la voir
        if (!$backer->isActive() && !$backerService->userCanPreview($user, $backer)) {
            return $this->redirectToRoute('app_home');
        }

        $aidsParams = [
            'showInSearch' => true,
            'backer' => $backer,
        ];

        // défini les aides lives, à partir de quoi on pourra récupérer les financières, techniques, les thématiques
        $backer->setAidsLive($aidRepository->findCustom($aidsParams));

        // log
        $logService->log(
            type: LogService::BACKER_VIEW,
            params: [
                'host' => $requestStack->getCurrentRequest()->getHost(),
                'backer' => $backer,
                'organization' => $userService->getUserLogged()
                    ? $userService->getUserLogged()->getDefaultOrganization()
                    : null,
                'user' => $userService->getUserLogged(),
            ]
        );

        //foreach $backer->getAidsLive()
        $categories_by_theme = [];
        $programs_list = [];
        foreach ($backer->getAidsLive() as $aid) {
            foreach ($aid->getCategories() as $category) {
                if (!isset($categories_by_theme[$category->getCategoryTheme()->getId()])) {
                    $categories_by_theme[$category->getCategoryTheme()->getId()] = [
                        'categoryTheme' => $category->getCategoryTheme(),
                        'categories' => new ArrayCollection()
                    ];
                }
                if (!$categories_by_theme[$category->getCategoryTheme()->getId()]['categories']->contains($category)) {
                    $categories_by_theme[$category->getCategoryTheme()->getId()]['categories']->add($category);
                }
            }

            foreach ($aid->getPrograms() as $program) {
                if (!isset($programs_list[$program->getId()])) {
                    $programs_list[$program->getId()] = [
                        'program' => $program,
                    ];
                }
            }
        }


        // fil arianne
        $this->breadcrumb->add(
            $backer->getName(),
            null
        );

        // rendu template
        return $this->render('backer/backer/details.html.twig', [
            'backer' => $backer,
            'forceDisplayAidAsList' => true,
            'aids' => $backer->getAidsLive(),
            'categories_by_theme' => $categories_by_theme,
            'programs_list' => $programs_list
        ]);
    }

    #[Route('partenaires/ajax-search', name: 'app_backer_ajax_search', options: ['expose' => true])]
    public function ajaxSearch(
        RequestStack $requestStack,
        InternalApiService $internalApiService
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

            // recuperer id du perimetre
            $search = $requestStack->getCurrentRequest()->get('search', null);
            if (!$search) {
                throw new \Exception('Paramètre search manquant');
            }

            // appel l'api pour avoir les datas
            $backers = $internalApiService->callApi(
                url: '/backers/',
                params: ['q' => (string) $search]
            );
            $backers = json_decode($backers);
            $results = $backers->results;
            $return = [];
            foreach ($results as $result) {
                $return[] = $result;
            }

            return new JsonResponse([
                'success' => 1,
                'results' => $return
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }
}
