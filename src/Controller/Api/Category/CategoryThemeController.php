<?php

namespace App\Controller\Api\Category;

use App\Controller\Api\ApiController;
use App\Entity\Category\CategoryTheme;
use App\Repository\Category\CategoryThemeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class CategoryThemeController extends ApiController
{
    #[Route('/api/themes/', name: 'api_category_theme', priority: 5)]
    public function index(
        CategoryThemeRepository $categoryThemeRepository
    ): JsonResponse
    {
        // les filtres
        $params = [];

        // requete pour compter sans la pagination
        $count = $categoryThemeRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $categoryThemeRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var CategoryTheme $result */
        foreach ($results as $result) {
            $categories = [];
            foreach ($result->getCategories() as $category) {
                $categories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                ];
            }
            $resultsSpe[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
                'slug' => $result->getSlug(),
                'categories' => $categories
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