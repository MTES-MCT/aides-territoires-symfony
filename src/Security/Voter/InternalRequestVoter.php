<?php

namespace App\Security\Voter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class InternalRequestVoter extends Voter
{
    const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    const IDENTIFIER = 'INTERNAL_REQUEST';
    const CSRF_TOKEN_NAME = 'internal_action';
    const CSRF_TOKEN_SESSION_NAME = 'csrf_token_session';

    private RequestStack $requestStack;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        RequestStack $requestStack,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::IDENTIFIER;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        // Récupérer la session via la requête
        $session = $request->getSession();
        if (!$session) {
            return false;
        }

        $csrfTokenSession = $session->get(self::CSRF_TOKEN_SESSION_NAME);
        if (!$csrfTokenSession) {
            return false;
        }

        // Valider le token CSRF
        $csrfToken = new CsrfToken(self::CSRF_TOKEN_NAME, $csrfTokenSession);
        return $this->csrfTokenManager->isTokenValid($csrfToken);
    }
}
