<?php

namespace App\EventListener\Site;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CookieResponseListener
{
    public function __construct(
        private RequestStack $requestStack
    )
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $cookies = $session->get('pending_cookies', []);

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
