<?php

namespace App\Controller\Api\Category;

use App\Controller\Api\ApiController;
use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use App\Repository\Category\CategoryRepository;
use App\Repository\Category\CategoryThemeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class CategoryController extends ApiController
{
    #[Route('/api/categories/', name: 'api_categories', priority: 5)]
    public function index(
        CategoryRepository $categoryRepository,
        CategoryThemeRepository $categoryThemeRepository
    ): JsonResponse {
        // les filtres
        $params = [];

        $q = $this->requestStack->getCurrentRequest()->get('q', null);
        if ($q) {
            $params['nameLike'] = $q;
        }

        $category_theme_id = $this->requestStack->getCurrentRequest()->get('category_theme_id', null);
        if ($category_theme_id) {
            $categoryTheme = $categoryThemeRepository->find($category_theme_id);
            if ($categoryTheme instanceof CategoryTheme) {
                $params['categoryTheme'] = $categoryTheme;
            }
        }

        // requete pour compter sans la pagination
        $count = $categoryRepository->countCustom($params);

        // requete pour les résultats avec la pagination
        $params['firstResult'] = ($this->getPage() - 1) * $this->getItemsPerPage();
        $params['maxResults'] = $this->getItemsPerPage();

        $results = $categoryRepository->findCustom($params);

        // spécifique
        $resultsSpe = [];
        /** @var Category $result */
        foreach ($results as $result) {
            $resultsSpe[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
                'slug' => $result->getSlug(),
                'category_theme' => $result->getCategoryTheme()
                    ? [
                        'id' => $result->getCategoryTheme()->getId(),
                        'name' => $result->getCategoryTheme()->getName(),
                        'slug' => $result->getCategoryTheme()->getSlug()
                    ]
                    : null
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
