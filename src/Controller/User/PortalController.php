<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\User\SearchPage\SearchPageEditType;
use App\Repository\Search\SearchPageRepository;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PortalController extends FrontController
{
    #[Route('/comptes/portails/{id}/', name: 'app_user_portal_edit', requirements: ['id' => '[0-9]+'], methods: ['GET', 'POST'])]
    public function edit(
        $id,
        SearchPageRepository $searchPageRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService
    ): Response
    {
        // verification user
        $user = $userService->getUserLogged();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // verification searchPage et administrateur
        $searchPage = $searchPageRepository->find((int) $id);
        if (!$searchPage instanceof SearchPage || $searchPage->getAdministrator() !== $user) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // compte les aides
        $queryString = null;
        $query = parse_url($searchPage->getSearchQuerystring())['query'] ?? null;
        $queryString = $query ?? $searchPage->getSearchQuerystring();
        $aidSearchClass = $aidSearchFormService->getAidSearchClass(
            params: [
                'querystring' => $queryString,
                'forceOrganizationType' => null,
                'dontUseUserPerimeter' => true
                ]
        );
        $aidParams = [
            'showInSearch' => true,
        ];
        $aidParams = array_merge($aidParams, $aidSearchFormService->convertAidSearchClassToAidParams($aidSearchClass));
        $aidParams['searchPage'] = $searchPage;
        $aids = $aidService->searchAids($aidParams);
        $nbAids = count($aids);
        $nbLocals = 0;
        /** @var Aid $aid */
        foreach ($aids as $aid) {
            if ($aid->isLocal()) {
                $nbLocals++;
            }
        }
        // formulaire edition
        $form = $this->createForm(SearchPageEditType::class, $searchPage);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // sauvegarde
                $managerRegistry->getManager()->persist($searchPage);
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(FrontController::FLASH_SUCCESS, 'Vos modifications ont bien été enregistrées');

                // redirection
                return $this->redirectToRoute('app_user_portal_edit', ['id' => $id]);
            } else {
                $this->addFlash(FrontController::FLASH_ERROR, 'Le formulaire contient des erreurs');
            }
        }

        // fil arianne
        $this->breadcrumb->add('Comptes', $this->generateUrl('app_user_dashboard'));
        $this->breadcrumb->add('Portail '.$searchPage->getName(), $this->generateUrl('app_user_portal_edit', ['id' => $id]));

        // rendu template
        return $this->render('user/searchpage/edit.html.twig', [
            'form' => $form,
            'searchPage' => $searchPage,
            'nbAids' => $nbAids,
            'nbLocals' => $nbLocals
        ]);
    }
}