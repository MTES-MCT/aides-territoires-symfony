<?php

namespace App\Security\Voter;

use App\Service\Various\ParamService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InternalRequestVoter extends Voter
{
    const MESSAGE_ERROR = 'Vous n\'êtes pas autorisé à accéder à cette ressource.';
    const IDENTIFIER = 'INTERNAL_REQUEST';

    private array $allowedIps;
    public function __construct(
        private RequestStack $requestStack,
        private ParamService $paramService
    )
    {
        $this->requestStack = $requestStack;
        $this->allowedIps = explode(',', $this->paramService->get('allowed_internal_ips'));
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
        return in_array($clientIp, $this->allowedIps, true);
    }
}
