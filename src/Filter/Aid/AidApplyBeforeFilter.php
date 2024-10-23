<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;

final class AidApplyBeforeFilter extends AbstractFilter
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
            'apply_before' => [
                'property' => 'apply_before',
                'type' => 'date',
                'required' => false,
                'description' => 'Candidater avant...',
                'openapi' => [
                    'examples' => [
                        new Example('2021-09-01', null, '2021-09-01'),
                        new Example('2023-11-21', null, '2023-11-21')
                    ],
                ],
            ],
        ];
    }
}
