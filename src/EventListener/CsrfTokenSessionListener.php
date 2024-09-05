<?php

namespace App\EventListener;

use App\Security\Voter\InternalRequestVoter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CsrfTokenSessionListener
{
    // Durée de vie du token CSRF en secondes
    private const CSRF_TOKEN_LIFETIME = 10;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private RequestStack $requestStack;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        RequestStack $requestStack
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Récupère la session via la requête
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        // Si aucune session active ou session non initialisée, ne rien faire
        if (!$session || !$session->isStarted()) {
            return;
        }

        // Vérifier si un token CSRF est déjà en session
        $csrfTokenSession = $session->get(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME);
        $csrfTokenCreationTime = $session->get(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME . '_created_at');

        // Si aucun token ou token expiré, régénérer un nouveau token
        if (!$csrfTokenSession || !$csrfTokenCreationTime || (time() - $csrfTokenCreationTime) > self::CSRF_TOKEN_LIFETIME) {
            $csrfTokenSession = $this->csrfTokenManager->getToken(InternalRequestVoter::CSRF_TOKEN_NAME)->getValue();
            $session->set(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME, $csrfTokenSession);
            $session->set(InternalRequestVoter::CSRF_TOKEN_SESSION_NAME . '_created_at', time());
        }
    }
}
