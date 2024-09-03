<?php

namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Entity\Category\Category;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidCategoriesFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        // pour extends AbstractFilter, on doit implémenter cette méthode
    }

    public function getDescription(string $resourceClass): array
    {
        $categories = $this->managerRegistry->getRepository(Category::class)->findBy(
            [],
            ['name' => 'ASC']
        );
        $examples = [];
        foreach ($categories as $category) {
            $examples[] = new Example($category->getName(), null, $category->getSlug());
        }
        return [
            'categories' => [
                'property' => 'categories',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>Les thématiques pour lesquelles vous recherchez des aides. Vous pouvez passer plusieurs fois ce paramètre pour rechercher sur plusieurs thématiques, ex : ...&categories=eau-potable&categories=eau-souterraine...<br><br>Voir aussi <code>/api/aids/themes/</code> pour la liste complète.</p></div>',
                'openapi' => [
                    'examples' => $examples,
                ],
            ],
        ];
    }
}
