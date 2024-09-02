<?php

namespace App\Controller\Perimeter;

use App\Controller\FrontController;
use App\Security\Voter\InternalRequestVoter;
use App\Service\Api\InternalApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class PerimeterController extends FrontController
{
    #[Route('perimeter/ajax-search', name: 'app_perimeter_ajax_search', options: ['expose' => true])]
    public function ajaxSearch(
        RequestStack $requestStack,
        InternalApiService $internalApiService
    ): JsonResponse
    {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

            // recuperer id du perimetre
            $search = $requestStack->getCurrentRequest()->get('search', null);
            if (!$search) {
                throw new \Exception('Paramètre search manquant');
            }

            // appel l'api pour avoir les datas
            $perimeters = $internalApiService->callApi(
                url: '/perimeters/',
                params: ['searchLike' => (string) $search]
            );
            $perimeters = json_decode($perimeters);
            $results = $perimeters->results;
            $return = [];
            foreach ($results as $result) {
                $return[] = $result;
            }

            return new JsonResponse([
                'success' => 1,
                'results' => $return
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    #[Route('perimeter/ajax-datas', name: 'app_perimeter_ajax_datas', options: ['expose' => true])]
    public function ajaxDatas(
        RequestStack $requestStack,
        InternalApiService $internalApiService
    ): JsonResponse
    {
        try {
            // verification requête interne
            if (!$this->isGranted(InternalRequestVoter::IDENTIFIER)) {
                throw $this->createAccessDeniedException(InternalRequestVoter::MESSAGE_ERROR);
            }

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
                'message' => $e->getMessage()
            ]);
        }
    }
}