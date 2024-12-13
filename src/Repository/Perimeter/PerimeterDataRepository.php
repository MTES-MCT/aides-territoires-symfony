<?php

namespace App\Repository\Perimeter;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerimeterData>
 *
 * @method PerimeterData|null find($id, $lockMode = null, $lockVersion = null)
 * @method PerimeterData|null findOneBy(array $criteria, array $orderBy = null)
 * @method PerimeterData[]    findAll()
 * @method PerimeterData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PerimeterDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerimeterData::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(pd.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, PerimeterData>
     */
    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $perimeter = $params['perimeter'] ?? null;

        $qb = $this->createQueryBuilder('pd');

        if ($perimeter instanceof Perimeter && $perimeter->getId()) {
            $qb
                ->andWhere('pd.perimeter = :perimeter')
                ->setParameter('perimeter', $perimeter)
            ;
        }
        return $qb;
    }
}
