<?php

namespace App\Filter\Backer;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class HasPublishedFinancedAidsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'has_published_financed_aids') {
            return;
        }

        $queryBuilder
            ->innerJoin('o.aidFinancers', 'aidFinancers');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'has_published_financed_aids' => [
                'property' => 'has_published_financed_aids',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Renvoyer seulement les porteurs d\'aides avec des aides publiées.',
                'openapi' => [
                    'examples' => [
                        new Example('true', '', true),
                        new Example('false', '', false),
                    ],
                ],
            ],
        ];
    }
}
