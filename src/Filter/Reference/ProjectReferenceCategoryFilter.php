<?php

namespace App\Filter\Reference;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class ProjectReferenceCategoryFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        // ajouté pour être conforme à l'extends
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'project_reference_category_id' => [
                'property' => 'project_reference_category_id',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => '<div class="renderedMarkdown"><p>'
                                    . 'Rechercher par id de catégorie de projet référent.<br><br>'
                                    . 'Note : se référer à la liste.</p></div>',
                'openapi' => [
                    'examples' => [
                        new Example(1, null, 1),
                        new Example(2, null, 2),
                    ],
                ],
            ],
        ];
    }
}
