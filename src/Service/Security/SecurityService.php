<?php

namespace App\Service\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class SecurityService
{
    public function validHostOrgin(RequestStack $requestStack): bool
    {
            // verification requete interne
            $request = $requestStack->getCurrentRequest();
            $origin = $request->headers->get('origin');
            $infosOrigin = parse_url($origin);
            $hostOrigin = $infosOrigin['host'] ?? null;
            $serverName = $request->getHost();

            return $hostOrigin === $serverName;
    }
}
