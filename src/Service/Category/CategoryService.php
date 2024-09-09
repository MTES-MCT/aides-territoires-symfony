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

        // trie des ['categoryThemesById'] par ordre alphabétique sur le categoryTheme name
        usort($categoryThemesById, function ($a, $b) {
            return $a['categoryTheme']->getName() <=> $b['categoryTheme']->getName();
        });

        // tri des ['categories'] par ordre alphabétique
        foreach ($categoryThemesById as $key => $categoryTheme) {
            usort($categoryTheme['categories'], function ($a, $b) {
                return $a->getName() <=> $b->getName();
            });
            $categoryThemesById[$key]['categories'] = $categoryTheme['categories'];
        }

        return $categoryThemesById;
    }
}
