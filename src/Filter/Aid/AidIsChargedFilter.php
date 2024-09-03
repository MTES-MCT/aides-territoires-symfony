<?php

namespace App\Filter\Aid;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidIsChargedFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void {}

    public function getDescription(string $resourceClass): array
    {
        return [
            'is_charged' => [
                'property' => 'is_charged',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Aides payantes uniquement.',
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
