<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(priority:1)]
class TrafficAdviceController extends FrontController
{
    #[Route('/.well-known/traffic-advice', name: 'app_static_traffic_advice')]
    public function index() : JsonResponse {

        return new JsonResponse([
            'message' => 'No traffic advice available.'
        ]);
    }
}
