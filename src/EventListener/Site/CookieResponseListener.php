<?php

namespace App\EventListener\Site;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CookieResponseListener
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Vérifier si le request existe et si la route est stateless
        if (!$request || $request->attributes->get('_stateless') === true) {
            return;
        }
        
        // Vérifier si la session existe et est démarrée avant d'y accéder
        if (!$request->hasSession() || !$request->getSession()->isStarted()) {
            return;
        }
        
        $response = $event->getResponse();
        $session = $request->getSession();
        $cookies = $session->get('pending_cookies', []);

        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $cookieData) {
            $cookie = new Cookie(
                $cookieData['name'],
                $cookieData['value'],
                $cookieData['expire'],
                $cookieData['path'],
                $cookieData['domain'],
                $cookieData['secure'],
                $cookieData['httpOnly'],
                false,
                $cookieData['sameSite']
            );

            $response->headers->setCookie($cookie);
        }

        // Nettoyer la session après application des cookies
        $session->remove('pending_cookies');
    }
}
