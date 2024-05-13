<?php

namespace App\EventSubscriber;

use App\Entity\User\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class BannedUserSubscriber implements EventSubscriberInterface
{
    private $urlGenerator;
    private $security;

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $user = $this->security->getUser();
        $currentRoute = $event->getRequest()->attributes->get('_route');
    
        if ($user && in_array(User::ROLE_BANNED, $user->getRoles()) && $currentRoute !== 'app_user_banned') {
            $response = new RedirectResponse($this->urlGenerator->generate('app_user_banned'));
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
