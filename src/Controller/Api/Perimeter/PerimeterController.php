<?php

namespace App\Controller\Api\Perimeter;

use App\Controller\Api\ApiController;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterDataRepository;
use App\Repository\Perimeter\PerimeterRepository;
use App\Service\Perimeter\PerimeterService;
use App\Service\Various\StringService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class PerimeterController extends ApiController
{
    #[Route('/api/perimeters/', name: 'api_perimeters', priority: 5)]
    public function index(
        PerimeterRepository $perimeterRepository,
        PerimeterService $perimeterService
    ): JsonResponse
    {
        // les filtres
        $params = [];
        $q = $this->requestStack->getCurrentRequest()->get('q', null);
        if ($q) {
            $params['nameMatchAgainst'] = $q;
        }
        $scale = $this->requestStack->getCurrentRequest()->get('scale', null);
        if ($scale) {
            $params['scale'] = $perimeterService->getScaleFromSlug($scale)['scale'] ?? null;
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
                'id' => $result->getId().'-'.$this->stringService->getSlug($result->getName()),
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
    public function scales(

    ): JsonResponse
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

    #[Route('/api/perimeters/{id}/', name: 'api_perimeters_details', priority: 5, requirements: ['id' => '[0-9]+'])]
    public function details(
        int $id,
        PerimeterRepository $perimeterRepository,
        PerimeterService $perimeterService
    ): JsonResponse
    {
        $perimeter = $perimeterRepository->find($id);
        if (!$perimeter instanceof Perimeter) {
            return new JsonResponse('Périmètre non trouvé', 404);
        }

        // spécifique
        $data = [
            'id' => $perimeter->getId().'-'.$this->stringService->getSlug($perimeter->getName()),
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