<?php

namespace App\EventListener;

use App\Security\Voter\InternalRequestVoter;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CsrfTokenSessionListener
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // recupere la session
        $session = new Session();
        $csrfTokenSession = $session->get(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME);
        // si pas de token csrf en session, on le genere
        if (!$csrfTokenSession) {
            $csrfTokenSession = $this->csrfTokenManager->getToken(InternalRequestVoter::CSRF_TOKEN_NAME)->getValue();
            $session->set(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME, $csrfTokenSession);
        }
    }
}
