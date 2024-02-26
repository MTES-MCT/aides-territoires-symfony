<?php

namespace App\Controller\Log;

use App\Controller\FrontController;
use App\Service\Log\LogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends FrontController
{
    #[Route(name: 'app_log_ajax', path: '/log/ajax', options: ['expose' => true])]
    public function ajaxLog(
        RequestStack $requestStack,
        LogService $logService
    ): JsonResponse
    {
        $request = $requestStack->getCurrentRequest();
        $origin = $request->headers->get('origin');
        $infosOrigin = parse_url($origin);
        $hostOrigin = $infosOrigin['host'] ?? null;
        $serverName = $request->getHost();

        if ($hostOrigin !== $serverName) {
            // La requête n'est pas interne, retourner une erreur
            throw $this->createAccessDeniedException('This action can only be performed by the server itself.');
        }

        // log
        $logService->log(
            type: $request->get('type'),
            params: $request->get('params'),
        );

        // retour
        return new JsonResponse([
            'success' => true,
        ]);
    }
}