<?php

namespace App\Security\Voter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class InternalRequestVoter extends Voter
{
    public const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    public const IDENTIFIER = 'INTERNAL_REQUEST';
    public const CSRF_TOKEN_NAME = 'csrf_internal';

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

        // Vérifier le token CSRF
        if (!$this->validateCsrfToken($request)) {
            return false;
        }

        return true;
    }

    private function validateCsrfToken($request): bool
    {
        $csrfToken = $request->request->get('_token') ?? $request->query->get('_token');
        if (!$csrfToken) {
            return false;
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_NAME, $csrfToken));
    }
}
