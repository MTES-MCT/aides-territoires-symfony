<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Entity\Aid\Aid;
use App\Entity\Search\SearchPage;
use App\Entity\User\User;
use App\Form\User\SearchPage\SearchPageEditType;
use App\Repository\Search\SearchPageRepository;
use App\Security\Voter\InternalRequestVoter;
use App\Security\Voter\User\UserCanEditSearchPageVoter;
use App\Service\Aid\AidSearchFormService;
use App\Service\Aid\AidService;
use App\Service\SearchPage\SearchPageService;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PortalController extends FrontController
{
    #[Route(
        '/comptes/portails/{id}/',
        name: 'app_user_portal_edit',
        requirements: ['id' => '[0-9]+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        $id,
        SearchPageRepository $searchPageRepository,
        RequestStack $requestStack,
        UserService $userService,
        ManagerRegistry $managerRegistry,
        AidSearchFormService $aidSearchFormService,
        AidService $aidService,
        SearchPageService $searchPageService
    ): Response {
        // verification user
        $user = $userService->getUserLogged();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // verification searchPage
        $searchPage = $searchPageRepository->find((int) $id);
        if (!$searchPage instanceof SearchPage) {
            return $this->redirectToRoute('app_user_dashboard');
        }

        // vérification accès
        if (!$this->isGranted(UserCanEditSearchPageVoter::IDENTIFIER, $searchPage)) {
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
        $this->breadcrumb->add(
            'Portail ' . $searchPage->getName(),
            $this->generateUrl('app_user_portal_edit', ['id' => $id])
        );

        // rendu template
        return $this->render('user/searchpage/edit.html.twig', [
            'form' => $form,
            'searchPage' => $searchPage,
            'nbAids' => $nbAids,
            'nbLocals' => $nbLocals,
            'isLocked' => $searchPageService->isLocked($searchPage),
            'lock' => $searchPageService->getLock($searchPage)
        ]);
    }

    #[Route('/comptes/portails/ajax-lock/', name: 'app_user_portal_ajax_lock', options: ['expose' => true])]
    public function ajaxLock(
        RequestStack $requestStack,
        SearchPageRepository $searchPageRepository,
        SearchPageService $searchPageService,
        UserService $userService
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

            // recupere id
            $id = (int) $requestStack->getCurrentRequest()->get('id', 0);
            if (!$id) {
                throw new \Exception('Id manquant');
            }

            // le user
            $user = $userService->getUserLogged();

            // charge portal
            $searchPage = $searchPageRepository->find($id);
            if (!$searchPage instanceof SearchPage) {
                throw new \Exception('Portail manquant');
            }

            // verifie que le user peut lock
            if (!$this->isGranted(UserCanEditSearchPageVoter::IDENTIFIER, $searchPage)) {
                throw new \Exception('Vous ne pouvez pas bloquer ce portail');
            }

            // regarde si deja lock
            $isLockedByAnother = $searchPageService->isLockedByAnother($searchPage, $user);
            if ($isLockedByAnother) {
                throw new \Exception('Portail déjà bloqué');
            }

            // le bloque
            $searchPageService->lock($searchPage, $user);

            // retour
            return new JsonResponse([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false
            ]);
        }
    }

    #[Route('/comptes/portails/{id}/unlock/', name: 'app_user_portal_unlock', requirements: ['id' => '\d+'])]
    public function unlock(
        $id,
        SearchPageRepository $searchPageRepository,
        UserService $userService,
        SearchPageService $searchPageService
    ): Response {
        try {
            // le user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User invalid');
            }

            // le portail
            $searchPage = $searchPageRepository->find($id);
            if (!$searchPage instanceof SearchPage) {
                throw new \Exception('Portail invalide');
            }

            // verifie que le user peut lock
            $canLock = $searchPageService->canUserLock($searchPage, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas bloquer ce portail');
            }

            // suppression du lock
            $searchPageService->unlock($searchPage);

            // message
            $this->addFlash(FrontController::FLASH_SUCCESS, 'Portail débloqué');

            // retour
            return $this->redirectToRoute('app_user_dashboard');
        } catch (\Exception $e) {
            // message
            $this->addFlash(FrontController::FLASH_ERROR, 'Impossible de débloquer le portail');

            // retour
            return $this->redirectToRoute('app_user_portal_edit', ['id' => $searchPage->getId()]);
        }
    }

    #[Route('/comptes/portails/ajax-unlock/', name: 'app_user_portal_ajax_unlock', options: ['expose' => true])]
    public function ajaxUnlock(
        RequestStack $requestStack,
        SearchPageRepository $searchPageRepository,
        SearchPageService $searchPageService,
        UserService $userService
    ): JsonResponse {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

            // recupere id
            $id = (int) $requestStack->getCurrentRequest()->get('id', 0);
            if (!$id) {
                throw new \Exception('Id manquant');
            }

            // user
            $user = $userService->getUserLogged();
            if (!$user instanceof User) {
                throw new \Exception('User manquant');
            }

            // charge portail
            $searchPage = $searchPageRepository->find($id);
            if (!$searchPage instanceof SearchPage) {
                throw new \Exception('Portail manquant');
            }

            // verifie que le user peut lock
            $canLock = $searchPageService->canUserLock($searchPage, $user);
            if (!$canLock) {
                throw new \Exception('Vous ne pouvez pas débloquer ce portail');
            }

            // le débloque
            $searchPageService->unlock($searchPage);

            // retour
            return new JsonResponse([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false
            ]);
        }
    }
}
