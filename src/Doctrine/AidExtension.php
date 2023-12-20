<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Aid\Aid;
use App\Repository\Aid\AidRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final class AidExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($operation->getName() == Aid::API_OPERATION_GET_COLLECTION_PUBLISHED)
        {
            $this->addWhere($queryBuilder, $resourceClass);
        }
        
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        if ($operation->getName() == Aid::API_OPERATION_GET_BY_SLUG) 
        {
            $this->addWhere($queryBuilder, $resourceClass);
        }
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Aid::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->addCriteria(AidRepository::liveCriteria($rootAlias.'.'))
        ;
    }
}