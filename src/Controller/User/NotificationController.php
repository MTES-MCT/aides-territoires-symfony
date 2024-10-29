<?php

namespace App\Controller\User;

use App\Controller\FrontController;
use App\Form\User\Notification\NotificationDeleteAllType;
use App\Form\User\Notification\NotificationDeleteType;
use App\Form\User\Notification\NotificationSettingType;
use App\Repository\User\NotificationRepository;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends FrontController
{
    public const NB_NOTIFICATION_BY_PAGE = 20;

    #[Route('/comptes/notifications/', name: 'app_user_user_notification')]
    public function notification(
        UserService $userService,
        NotificationRepository $notificationRepository,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    ): Response {
        // gestion pagination
        $currentPage = (int) $requestStack->getCurrentRequest()->get('page', 1);

        // user
        $user = $userService->getUserLogged();

        // notifications
        $notifications = $notificationRepository->getQueryBuilder(
            [
                'user' => $user,
                'orderBy' => [
                    'sort' => 'n.timeCreate',
                    'order' => 'DESC'
                ]
            ]
        );
        $adapter = new QueryAdapter($notifications);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(self::NB_NOTIFICATION_BY_PAGE);
        $pagerfanta->setCurrentPage($currentPage);

        // formulaire suppression
        $formDelete = $this->createForm(NotificationDeleteType::class);
        $formDelete->handleRequest($requestStack->getCurrentRequest());
        if ($formDelete->isSubmitted()) {
            if ($formDelete->isValid()) {
                // suppression
                $managerRegistry->getManager()->remove($notificationRepository
                ->find($formDelete->get('idNotification')->getData()));

                // décrémente le compteur user
                $user->setNotificationCounter($user->getNotificationCounter() - 1);

                // sauvegarde
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'La notification a bien été supprimée.'
                );

                // redirection
                return $this->redirectToRoute('app_user_user_notification');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Vous ne pouvez pas supprimer cette notification.'
                );
            }
        }

        // formulaire suppression toutes les notifications
        $formDeleteAll = $this->createForm(NotificationDeleteAllType::class);
        $formDeleteAll->handleRequest($requestStack->getCurrentRequest());
        if ($formDeleteAll->isSubmitted()) {
            if ($formDeleteAll->isValid()) {
                // suppression
                $notifications = $notificationRepository->findCustom(
                    [
                        'user' => $user,
                    ]
                );

                foreach ($notifications as $notification) {
                    $managerRegistry->getManager()->remove($notification);
                }

                // met le compteur user à 0
                $user->setNotificationCounter(0);
                $managerRegistry->getManager()->persist($user);

                // sauvegarde
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos notifications ont bien été supprimées.'
                );

                // redirection
                return $this->redirectToRoute('app_user_user_notification');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Impossible de supprimer vos notifications.'
                );
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_parameter_profil')
        );
        $this->breadcrumb->add(
            'Mes notifications',
        );

        // rendu template
        return $this->render('user/notification/notification.html.twig', [
            'myPager' => $pagerfanta,
            'formDelete' => $formDelete,
            'formDeleteAll' => $formDeleteAll
        ]);
    }

    #[Route('/comptes/notifications/preferences/', name: 'app_user_user_notification_settings')]
    public function notificationSettings(
        UserService $userService,
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack
    ): Response {
        // le user
        $user = $userService->getUserLogged();

        // le formulaire
        $form = $this->createForm(NotificationSettingType::class, $user);
        $form->handleRequest($requestStack->getCurrentRequest());
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // enregistrement
                $managerRegistry->getManager()->persist($user);
                $managerRegistry->getManager()->flush();

                // message
                $this->addFlash(
                    FrontController::FLASH_SUCCESS,
                    'Vos préférences de notifications ont bien été enregistrées.'
                );

                // redirection
                return $this->redirectToRoute('app_user_user_notification_settings');
            } else {
                $this->addFlash(
                    FrontController::FLASH_ERROR,
                    'Impossible d’enregistrer vos préférences de notifications.'
                );
            }
        }

        // fil arianne
        $this->breadcrumb->add(
            'Mon compte',
            $this->generateUrl('app_user_parameter_profil')
        );
        $this->breadcrumb->add(
            'Mes notifications',
            $this->generateUrl('app_user_user_notification')
        );
        $this->breadcrumb->add(
            'Mes préférences de notifications',
        );

        // rendu template
        return $this->render('user/notification/notification-settings.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/comptes/notifications/tout-marquer-comme-vu/', name: 'app_user_user_notification_mark_all_read')]
    public function markAllRead(
        UserService $userService,
        NotificationRepository $notificationRepository,
        ManagerRegistry $managerRegistry,
    ): Response {
        // user
        $user = $userService->getUserLogged();

        // notifications non lues
        $notifications = $notificationRepository->findCustom(
            [
                'user' => $user,
                'notRead' => true,
                'orderBy' => [
                    'sort' => 'n.timeCreate',
                    'order' => 'DESC'
                ]
            ]
        );

        // passe les notifications en lues
        $now = new \DateTime();
        foreach ($notifications as $notification) {
            $notification->setTimeRead($now);
            $managerRegistry->getManager()->persist($notification);
        }

        // met le compteur à 0
        $user->setNotificationCounter(0);
        $managerRegistry->getManager()->persist($user);

        // sauvegarde
        $managerRegistry->getManager()->flush();
        // message
        $this->addFlash(
            FrontController::FLASH_SUCCESS,
            'Toutes les notifications ont bien été marquées comme lues.'
        );

        // retour
        return $this->redirectToRoute('app_user_user_notification');
    }

    #[Route('/comptes/notifications/tout-supprimer/', name: 'app_user_user_notification_delete_all')]
    public function deleteAll(): Response
    {
        return $this->render('user/notification/notification.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
}
