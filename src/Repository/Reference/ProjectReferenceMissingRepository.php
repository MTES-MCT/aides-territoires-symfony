<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ProjectReferenceMissing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectReferenceMissing>
 */
class ProjectReferenceMissingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReferenceMissing::class);
    }

    public function findAllOrderByNbAids() : array
    {
        $qb = $this->getQueryBuilder();
        $qb->addSelect('COUNT(a) as HIDDEN nbAids')
            ->leftJoin('pr.aids', 'a')
            ->groupBy('pr.id')
            ->orderBy('nbAids', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $nameLike = $params['nameLike'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('pr');

        if ($nameLike) {
            $qb->andWhere('pr.name LIKE :nameLike')
                ->setParameter('nameLike', '%' . $nameLike . '%');
        }

        if ($orderBy !== null) {
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
