<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sentry\State\HubInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SentryListener
{
    private $hub;

    public function __construct(HubInterface $hub, private KernelInterface $kernelInterface)
    {
        $this->hub = $hub;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Vérifier si l'environnement est 'prod'
        if ($this->kernelInterface->getEnvironment() !== 'prod') {
            return;
        }

        $request = $event->getRequest();

        // Ajouter l'IP et le referer en tant que tags ou extra data
        $this->hub->configureScope(function (\Sentry\State\Scope $scope) use ($request): void {
            $scope->setTag('user_ip', $request->getClientIp());
            $scope->setExtra('referer', $request->headers->get('referer'));
        });
    }
}
