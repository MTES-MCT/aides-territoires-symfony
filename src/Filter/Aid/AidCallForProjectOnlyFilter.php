<?php

namespace App\Filter\Aid;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AidCallForProjectOnlyFilter extends AbstractFilter
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
            'call_for_projects_only' => [
                'property' => 'call_for_projects_only',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Appels à projets / Appels à manifestation d\'intérêt uniquement.',
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
