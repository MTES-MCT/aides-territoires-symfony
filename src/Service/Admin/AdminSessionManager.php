<?php

namespace App\Service\Admin;

use Symfony\Component\HttpFoundation\Session\Session;

class AdminSessionManager
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function extendSessionLifetime()
    {
        $this->session->migrate(false, 7200); // 2 heures en secondes
    }
}
