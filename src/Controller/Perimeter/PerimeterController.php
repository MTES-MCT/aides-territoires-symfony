<?php

namespace App\Controller\Perimeter;

use App\Controller\FrontController;
use App\Service\Api\InternalApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class PerimeterController extends FrontController
{
    #[Route('perimeter/ajax-datas', name: 'app_perimeter_ajax_datas', options: ['expose' => true])]
    public function ajaxDatas(
        RequestStack $requestStack,
        InternalApiService $internalApiService
    ): JsonResponse
    {
        try {
        // recuperer id du perimetre
        $perimeterId = $requestStack->getCurrentRequest()->get('perimeter_id', null);
        if (!$perimeterId) {
            throw new \Exception('Id périmètre manquant');
        }

        // appel l'api pour avoir les datas
        $perimeterDatas = $internalApiService->callApi(
            url: '/perimeters/data/',
            params: ['perimeter_id' => (int) $perimeterId]
        );
        $perimeterDatas = json_decode($perimeterDatas);
        $results = $perimeterDatas->results;
        $return = [];
        foreach ($results as $result) {
            $return[] = [
                'prop' => $result->prop,
                'value' => $result->value
            ];
        }

        return new JsonResponse([
            'success' => 1,
            'results' => $return
        ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => 0,
            ]);
        }
    }
}