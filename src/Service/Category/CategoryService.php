<?php

namespace App\Service\Category;

use App\Entity\Category\Category;
use App\Repository\Category\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {
    }

    public function groupCategoriesByTheme(ArrayCollection $categories): array
    {
        $categoryThemesById = [];
        foreach ($categories as $category) {
            if (!isset($categoryThemesById[$category->getCategoryTheme()->getId()])) {
                $categoryThemesById[$category->getCategoryTheme()->getId()] = [
                    'categoryTheme' => $category->getCategoryTheme(),
                    'categories' => []
                ];
            }

            $categoryThemesById[$category->getCategoryTheme()->getId()]['categories'][] = $category;
        }

        return $categoryThemesById;
    }

    public function categoriesToMetas(ArrayCollection|array $categories): array
    {
        $categoryThemesById = [];
        foreach ($categories as $category) {
            if ($category instanceof Category) {
                if (!isset($categoryThemesById[$category->getCategoryTheme()->getId()])) {
                    $categoryThemesById[$category->getCategoryTheme()->getId()] = [
                        'categoryTheme' => $category->getCategoryTheme(),
                        'categories' => []
                    ];
                }

                $categoryThemesById[$category->getCategoryTheme()->getId()]['categories'][] = $category;
            }
        }

        return $categoryThemesById;
    }
}
