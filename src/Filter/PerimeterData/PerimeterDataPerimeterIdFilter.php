<?php

namespace App\Filter\PerimeterData;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class PerimeterDataPerimeterIdFilter extends AbstractFilter
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
            'perimeter_id' => [
                'property' => 'perimeter_id',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Show the extra data for the specified perimeter',
                'openapi' => [
                    'examples' => [
                        new Example('...', null, null),
                        new Example('Sigean', null, '75056'),
                        new Example('Fontenay-les-Briis', null, '105876'),
                    ],
                ],
            ],
        ];
    }
}
