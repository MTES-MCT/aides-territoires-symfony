<?php

namespace App\Service\Site;

use Symfony\Component\HttpFoundation\RequestStack;

class CookieService
{
    public function __construct(
        private RequestStack $requestStack
    )
    {
    }


    public function setCookie(string $name, string $value, int $expire = 3600): void
    {
        $cookieData = [
            'name' => $name,
            'value' => $value,
            'expire' => time() + $expire,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'httpOnly' => true,
            'sameSite' => 'lax',
        ];

        // Stocke le cookie temporairement en session
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->set('pending_cookies', array_merge(
            $session->get('pending_cookies', []),
            [$cookieData]
        ));
    }
}
