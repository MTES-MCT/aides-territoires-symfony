<?php

namespace App\Service\Security;

use Symfony\Component\HttpFoundation\RequestStack;

class SecurityService
{
    public function validHostOrgin(RequestStack $requestStack): bool
    {
        // vérification requête interne
        $request = $requestStack->getCurrentRequest();
        $origin = $request->headers->get('origin');
        if ($origin) {
            $infosOrigin = parse_url($origin);
            $hostOrigin = $infosOrigin['host'] ?? null;
        } else {
            // Utiliser l'en-tête Host si Origin n'est pas défini
            $hostOrigin = $request->headers->get('host');
            // Supprimer le port si présent
            $hostOrigin = explode(':', $hostOrigin)[0];
        }
        $serverName = $request->getHost();

        return $hostOrigin === $serverName;
    }
}
