<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sentry\State\HubInterface;

class SentryListener
{
    private $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ajouter l'IP et le referer en tant que tags ou extra data
        $this->hub->configureScope(function (\Sentry\State\Scope $scope) use ($request): void {
            $scope->setTag('user_ip', $request->getClientIp());
            $scope->setExtra('referer', $request->headers->get('referer'));
        });
    }
}
