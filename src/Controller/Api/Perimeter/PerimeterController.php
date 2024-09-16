<?php

namespace App\Controller\Api\Perimeter;

use App\Controller\Api\ApiController;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Perimeter\PerimeterService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class PerimeterController extends ApiController
{
    #[Route('/api/perimeters/', name: 'api_perimeters', priority: 5)]
    public function index(
        PerimeterRepository $perimeterRepository,
        PerimeterService $perimeterService
    ): JsonResponse {
        $query = parse_url($this->requestStack->getCurrentRequest()->getRequestUri(), PHP_URL_QUERY) ?? null;
        $queryItems = explode('&', $query);
        $queryParams = [];
        if (is_array($queryItems)) {
            foreach ($queryItems as $queyItem) {
                $param = explode('=', urldecode($queyItem));
                if (isset($param[0]) && isset($param[1])) {
                    if (isset($queryParams[$param[0]]) && is_array($queryParams[$param[0]])) {
                        $queryParams[$param[0]][] = $param[1];
                    } elseif (isset($queryParams[$param[0]]) && !is_array($queryParams[$param[0]])) {
                        $queryParams[$param[0]] = [$queryParams[$param[0]]];
                        $queryParams[$param[0]][] = $param[1];
                    } else {
                        $queryParams[$param[0]] = $param[1];
                    }
                }
            }
        }

        // les filtres
        $params = [];
        if (isset($queryParams['q'])) {
            if (is_array($queryParams['q'])) {
                $q = implode(' ', $queryParams['q']);
            } else {
                $q = $queryParams['q'];
            }
            $q = trim(strip_tags((string) $q));
            $params['nameMatchAgainst'] = $q;
        }

        if (isset($queryParams['searchLike'])) {
            $params['searchLike'] = trim(strip_tags((string) $queryParams['searchLike']));
        }

        if (isset($queryParams['scale'])) {
            if (is_array($queryParams['scale'])) {
                $scale = implode(' ', $queryParams['scale']);
            } else {
                $scale = $queryParams['scale'];
            }
            $scale = trim(strip_tags((string) $scale));
            $params['scale'] = $perimeterService->getScaleFromSlug($scale)['scale'] ?? null;
        }

        if (isset($queryParams['zipcodes'])) {
            if (!is_array($queryParams['zipcodes'])) {
                $zipcodes = [trim(strip_tags((string) $queryParams['zipcodes']))];
            } else {
                $zipcodes = $queryParams['zipcodes'];
            }
            $params['zipcodes'] = $zipcodes;
        }

        if (isset($queryParams['insees'])) {
            if (!is_array($queryParams['insees'])) {
                $insees = [trim(strip_tags((string) $queryParams['insees']))];
            } else {
                $insees = $queryParams['insees'];
            }
            $params['insees'] = $insees;
        }

        // requete pour compter sans la pagination
        $count = $perimeterRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $perimeterRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var Perimeter $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId() . '-' . $this->stringService->getSlug($result->getName()),
                'text' => $perimeterService->getSmartName($result),
                'name' => $result->getName(),
                'scale' => $perimeterService->getScale($result->getScale())['name'] ?? null,
                'zipcodes' => $result->getZipcodes() ?? [],
                'code' => $result->getCode()
            ];
        }

        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => $resultsSpe
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }

    #[Route('/api/perimeters/scales/', name: 'api_perimeters_scales', priority: 5)]
    public function scales(): JsonResponse
    {
        // tableau des échelles
        $scales = [];
        foreach (Perimeter::SCALES_TUPLE as $scale) {
            $scales[] = [
                'id' => $scale['slug'],
                'name' => $scale['name']
            ];
        }

        // requete pour compter sans la pagination
        $count = count($scales);

        $scales = $this->serializerInterface->serialize($scales, static::SERIALIZE_FORMAT);

        // le retour
        $data = [
            'count' => $count,
            'previous' => null,
            'next' => null,
            'results' => json_decode($scales)
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }

    #[Route('/api/perimeters/{id}/', name: 'api_perimeters_details', priority: 5, requirements: ['id' => '[A-Za-z0-9\-]+'])]
    public function details(
        string $id,
        PerimeterRepository $perimeterRepository,
        PerimeterService $perimeterService
    ): JsonResponse {
        $perimeter = $perimeterRepository->find((int) $id);
        if (!$perimeter instanceof Perimeter) {
            return new JsonResponse([
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Périmètre non trouvé',
            ], 404);
        }

        // spécifique
        $data = [
            'id' => $perimeter->getId() . '-' . $this->stringService->getSlug($perimeter->getName()),
            'text' => $perimeterService->getSmartName($perimeter),
            'name' => $perimeter->getName(),
            'scale' => $perimeterService->getScale($perimeter->getScale())['name'] ?? null,
            'zipcodes' => $perimeter->getZipcodes() ?? [],
            'code' => $perimeter->getCode()
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }
}
