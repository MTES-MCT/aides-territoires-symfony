<?php

namespace App\EventSubscriber;

use App\Entity\User\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BannedUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        $currentRoute = $event->getRequest()->attributes->get('_route');
        $allowedRoutes = ['app_user_banned', 'app_static_terms'];
        // si utilisateur banni on redirige vers la page (sauf si déjà dessus)
        if (
            $user instanceof User
            && in_array(User::ROLE_BANNED, $user->getRoles())
            && !in_array($currentRoute, $allowedRoutes)
        ) {
            $response = new RedirectResponse($this->urlGenerator->generate('app_user_banned'));
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
