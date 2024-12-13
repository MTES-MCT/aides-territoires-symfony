<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Service\Aid\AidSearchFormService;
use Doctrine\ORM\QueryBuilder;

final class AidPublishedAfterFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        mixed $value,
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
            AidSearchFormService::QUERYSTRING_KEY_PUBLISHED_AFTER => [
                'property' => AidSearchFormService::QUERYSTRING_KEY_PUBLISHED_AFTER,
                'type' => 'date',
                'required' => false,
                'description' => 'Publiée après... (format YYYY-MM-DD)',
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
