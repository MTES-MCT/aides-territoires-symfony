<?php

namespace App\Security\Voter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InternalRequestVoter extends Voter
{
    const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    const IDENTIFIER = 'INTERNAL_REQUEST';

    private array $allowedIps;
    public function __construct(
        private RequestStack $requestStack
    )
    {
        $this->requestStack = $requestStack;
        $this->allowedIps = [
            '127.0.0.1',
            '::1',
            '172.27.0.1',
            '172.27.0.4'
        ];
    }

    protected function supports(string $attribute, $subject): bool
    {
        // On vérifie que l'attribut est bien 'INTERNAL_REQUEST'
        return $attribute === self::IDENTIFIER;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }
        
        // Vérification de l'adresse IP du client
        $clientIp = $request->getClientIp();
        if (in_array($clientIp, $this->allowedIps, true)) {
            return true;
        }

        // Vérification de l'en-tête X-Internal-Request
        $internalRequestHeader = $request->headers->get('X-Internal-Request');
        if ($internalRequestHeader === 'true') {
            return true;
        }

        return false;
    }
}
