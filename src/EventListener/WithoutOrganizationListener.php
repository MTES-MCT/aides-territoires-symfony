<?php

namespace App\EventListener;

use App\Controller\FrontController;
use App\Entity\Organization\OrganizationInvitation;
use App\Repository\Page\PageRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

final class WithoutOrganizationListener
{
    public function __construct(
        private EntityManagerInterface $entityManagerInterface,
        private PageRepository $pageRepository,
        private UserService $userService,
        private RouterInterface $routerInterface,
    ) {
    }

    public function onKernelRequest(
        RequestEvent $event
    ): void {
        // url demandée
        $url = urldecode($event->getRequest()->getRequestUri()) ?? null;
        $urlsAuthorized = [
            '/comptes/structure/information/',
            '/comptes/structure/invitations/',
        ];
        // on est dans la partie mon compte
        if (preg_match('/comptes/', $url)) {
            foreach ($urlsAuthorized as $urlAuthorized) {
                if (preg_match('$' . $urlAuthorized . '$', $url)) {
                    return;
                }
            }
            // utilisateur
            $user = $this->userService->getUserLogged();

            // utilisateur connecté sans organization
            if ($user && !$user->getDefaultOrganization()) {
                $session =  new Session();
                $session->getFlashBag()->add(
                    FrontController::FLASH_ERROR,
                    'Vous devez renseigner les informations de votre structure ou accepter '
                        . 'une invitation avant de pouvoir accéder à cette page.'
                );

                // regarde si cet utilisateur à été invité à rejoindre une structure
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
