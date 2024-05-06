<?php

namespace App\Controller\Api\Program;

use App\Controller\Api\ApiController;
use App\Entity\Program\Program;
use App\Repository\Program\ProgramRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ProgramController extends ApiController
{
    #[Route('/api/programs/', name: 'api_programs', priority: 5)]
    public function index(
        ProgramRepository $programRepository
    ): JsonResponse
    {
        // les filtres
        $params = [];

        // requete pour compter sans la pagination
        $count = $programRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $programRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var Program $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
                'slug' => $result->getSlug(),
                'perimeter' => $result->getPerimeter() ? $result->getPerimeter()->getName() : null,
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
