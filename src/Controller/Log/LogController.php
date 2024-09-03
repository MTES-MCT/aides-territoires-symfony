<?php

namespace App\Controller\Log;

use App\Controller\FrontController;
use App\Security\Voter\InternalRequestVoter;
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
    ): JsonResponse {
        $request = $requestStack->getCurrentRequest();

        // verification requête interne
        if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
            throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
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
