<?php

namespace App\Controller\Api\Perimeter;

use App\Controller\Api\ApiController;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterData;
use App\Repository\Perimeter\PerimeterDataRepository;
use App\Service\Perimeter\PerimeterService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]

class PerimeterDataController extends ApiController
{
    #[Route('/api/perimeters/data/', name: 'api_perimeters_data', priority: 5)]
    public function index(
        PerimeterDataRepository $perimeterDataRepository,
        PerimeterService $perimeterService
    ): JsonResponse {
        // les filtres
        $params = [];

        $perimeterId = $this->requestStack->getCurrentRequest()->get('perimeter_id', null);
        if (!empty($perimeterId)) {
            $params['perimeter'] = $this->managerRegistry->getRepository(Perimeter::class)->find($perimeterId);
            if (!$params['perimeter'] instanceof Perimeter) {
                return new JsonResponse([
                    'type' => 'about:blank',
                    'title' => 'Not Found',
                    'status' => 404,
                    'detail' => 'Périmètre non trouvé',
                ], 404);
            }
        }

        // requete pour compter sans la pagination
        $count = $perimeterDataRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $perimeterDataRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var PerimeterData $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId(),
                'perimeter' => $result->getPerimeter() ? $perimeterService->getSmartName($result->getPerimeter()) : null,
                'prop' => $result->getProp(),
                'value' => $result->getValue()
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
}
