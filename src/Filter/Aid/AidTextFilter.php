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
            'keyword' => [
                'property' => 'keyword',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Recherche textuelle.',
                'openapi' => [
                    'examples' => [
                        new Example('vélo', null, 'vélo'),
                        new Example('piscine', null, 'piscine'),
                        new Example('piscine, bassin municipal', null, 'piscine, bassin municipal'),
                        new Example('Construction d\'une piscine', null, 'Construction d\'une piscine')
                    ],
                ],
            ],
        ];
    }
}
