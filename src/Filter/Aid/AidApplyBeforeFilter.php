<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;

final class AidApplyBeforeFilter extends AbstractFilter
{
    // empty method
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            AidSearchFormService::QUERYSTRING_KEY_APPLY_BEFORE => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_APPLY_BEFORE,
                'type' => 'date',
                'required' => false,
                'description' => 'Candidater avant... (format YYYY-MM-DD)',
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
