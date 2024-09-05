<?php

namespace App\Security\Voter;

use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
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

    public function __construct(
        private RequestStack $requestStack,
        private ParamService $paramService,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->requestStack = $requestStack;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // On vérifie que l'attribut est bien 'INTERNAL_REQUEST'
        return $attribute === self::IDENTIFIER;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // si pas de requete
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        // regarde si il y a bien le token csrf en session
        $session = new Session();
        $csrfTokenSession = $session->get(self::CSRF_TOKEN_SESSION_NAME);
        if (!$csrfTokenSession) {
            return false;
        }

        // vérifie le token csrf
        $csrfToken = new CsrfToken(self::CSRF_TOKEN_NAME, $csrfTokenSession);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            return false;
        }

        return true;
    }
}
