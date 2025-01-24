<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sentry\State\HubInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SentryListener
{
    public function __construct(
        private HubInterface $hub,
        private KernelInterface $kernelInterface
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Vérifier si l'environnement est 'prod'
        if ($this->kernelInterface->getEnvironment() !== 'prod') {
            return;
        }

        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            return; // Ne pas envoyer l'erreur 404 à Sentry
        }

        $request = $event->getRequest();

        // Ajouter l'IP et le referer en tant que tags ou extra data
        $this->hub->configureScope(function (\Sentry\State\Scope $scope) use ($request): void {
            $scope->setTag('user_ip', $request->getClientIp());
            $scope->setExtra('referer', $request->headers->get('referer'));
        });


    }
}
