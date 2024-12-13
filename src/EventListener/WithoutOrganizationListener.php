<?php

namespace App\EventListener;

use App\Controller\FrontController;
use App\Entity\Organization\OrganizationInvitation;
use App\Repository\Organization\OrganizationInvitationRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

final class WithoutOrganizationListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private UserService $userService,
        private RouterInterface $routerInterface,
        private RequestStack $requestStack,
    ) {
    }

    public function onKernelRequest(
        RequestEvent $event
    ): void {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        if (
            strpos((string) $routeName, 'app_user_aid') === 0
            || strpos((string) $routeName, 'app_user_project') === 0
        ) {
            // utilisateur
            $user = $this->userService->getUserLogged();

            // utilisateur connecté sans organization et qui veut publier des aides
            if ($user && !$user->getDefaultOrganization()) {
                /** @var Session $session */
                $session = $this->requestStack->getSession();
                $session->getFlashBag()->add(
                    FrontController::FLASH_ERROR,
                    'Vous devez renseigner les informations de votre structure ou accepter '
                        . 'une invitation avant de pouvoir accéder à cette page.'
                );

                // regarde si cet utilisateur a été invité à rejoindre une structure
                /** @var OrganizationInvitationRepository $organizationInvitationRepo */
                $organizationInvitationRepo = $this->entityManagerInterface
                    ->getRepository(OrganizationInvitation::class);
                $hasPendingInvitations = $organizationInvitationRepo->userHasPendingInvitation($user);
                if ($hasPendingInvitations) {
                    $event->setResponse(
                        new RedirectResponse(
                            $this->routerInterface->generate('app_organization_invitations')
                        )
                    );
                    return;
                }

                // sinon on redirige vers la page d'information de la structure
                $event->setResponse(
                    new RedirectResponse(
                        $this->routerInterface->generate(
                            'app_organization_structure_information',
                            ['id' => 0]
                        )
                    )
                );
            }
        }
    }
}
