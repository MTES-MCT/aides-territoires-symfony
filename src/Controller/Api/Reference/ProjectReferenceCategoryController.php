<?php

namespace App\Controller\Api\Reference;

use App\Controller\Api\ApiController;
use App\Entity\Reference\ProjectReferenceCategory;
use App\Repository\Reference\ProjectReferenceCategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class ProjectReferenceCategoryController extends ApiController
{
    #[Route('/api/project-reference-categories/', name: 'api_project_reference_categories', priority: 5)]
    public function index(
        ProjectReferenceCategoryRepository $projectReferenceCategoryRepository,
    ): JsonResponse {
        // requete pour compter sans la pagination
        $count = $projectReferenceCategoryRepository->countCustom();

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $projectReferenceCategoryRepository->findCustom();

        // on serialize pour ne garder que les champs voulus
        $results = $this->serializerInterface->serialize($results, static::SERIALIZE_FORMAT, ['groups' => ProjectReferenceCategory::API_GROUP_LIST]);

        // le retour
        $data = [
            'count' => $count,
            'previous' => $this->getPrevious(),
            'next' => $this->getNext($count),
            'results' => json_decode($results)
        ];

        // la réponse
        $response =  new JsonResponse($data, 200, [], false);
        // pour eviter que les urls ne soient ecodées
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $response;
    }
}
