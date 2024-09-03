<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\OpenApi\Model\Example;

/**
 * Selects entities where each search term is found somewhere
 * in at least one of the specified properties.
 * Search terms must be separated by spaces.
 * Search is case insensitive.
 * All specified properties type must be string. Nested properties are supported.
 * @package App\Filter
 */
class SimpleSearchFilter extends AbstractFilter
{
    private $searchParameterName;

    /**
     * Add configuration parameter
     * {@inheritdoc}
     * @param string $searchParameterName The parameter whose value this filter searches for
     */
    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null, NameConverterInterface $nameConverter = null, string $searchParameterName = 'simplesearch')
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);

        $this->searchParameterName = $searchParameterName;
    }

    /** {@inheritdoc} */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if (null === $value || $property !== $this->searchParameterName) {
            return;
        }

        $words = explode(' ', $value);
        foreach ($words as $word) {
            if (empty($word)) continue;

            $this->addWhere($queryBuilder, $word, $queryNameGenerator->generateParameterName($property), $queryNameGenerator, $resourceClass);
        }
    }

    private function addWhere($queryBuilder, $word, $parameterName, $queryNameGenerator, $resourceClass)
    {
        // Build OR expression
        $orExp = $queryBuilder->expr()->orX();
        foreach ($this->getProperties() as $prop => $ignoored) {
            $alias = $queryBuilder->getRootAliases()[0];
            // Thanks to Hasbert and Polo
            if ($this->isPropertyNested($prop, $resourceClass)) {
                [$alias, $prop] = $this->addJoinsForNestedProperty($prop, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
            }

            $orExp->add($queryBuilder->expr()->like('LOWER(' . $alias . '.' . $prop . ')', ':' . $parameterName));
        }

        // Add it
        $queryBuilder
            ->andWhere('(' . $orExp . ')')
            ->setParameter($parameterName, '%' . strtolower($word) . '%');
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass): array
    {
        $props = $this->getProperties();
        if (null === $props) {
            throw new InvalidArgumentException('Properties must be specified');
        }

        return [
            $this->searchParameterName => [
                'property' => implode(', ', array_keys($props)),
                'type' => 'string',
                'required' => false,
                'description' => 'Recherche textuelle',
                'openapi' => [
                    'examples' => [
                        new Example('velo', 'velo'),
                        new Example('piste, velo', 'piste OU velo'),
                        new Example('piste velo', 'piste ET velo'),
                    ],
                ]
            ]
        ];
    }
}
