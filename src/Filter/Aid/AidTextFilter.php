<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidTextFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'text' => [
                'property' => 'text',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Recherche textuelle.',
                'openapi' => [
                    'examples' => [
                        new Example('velo', null, 'velo'),
                        new Example('piste OU velo', null, 'piste, velo'),
                        new Example('piste ET velo', null, 'piste velo')
                    ],
                ],
            ],
        ];
    }
}
