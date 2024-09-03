<?php

namespace App\Filter\Backer;


use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Example;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class HasFinancedAidsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($property !== 'has_financed_aids') {
            return;
        }

        $queryBuilder
            ->innerJoin('o.aidFinancers', 'aidFinancers')
            ->innerJoin('aidFinancers.aid', 'aid')
            // aid live
            ->addCriteria(AidRepository::liveCriteria('aid.'))
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'has_financed_aids' => [
                'property' => 'has_financed_aids',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Renvoyer seulement les porteurs d\'aides avec des aides.',
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
