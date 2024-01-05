<?php
// src/EventListener/RemoveCspListener.php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class RemoveCspListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Vérifiez si la route commence par /admin
        if (strpos($request->getPathInfo(), '/admin') === 0) {
            // Supprimez l'en-tête CSP
            $response->headers->remove('Content-Security-Policy');
        }
    }
}
?>