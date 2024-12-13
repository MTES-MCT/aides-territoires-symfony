<?php

namespace App\Repository\DataSource;

use App\Entity\DataSource\DataSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DataSource>
 *
 * @method DataSource|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataSource|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataSource[]    findAll()
 * @method DataSource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataSource::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countAids(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        return (int) $qb
            ->select('COUNT(a.id)')
            ->leftJoin('d.aids', 'a')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $id = $params['id'] ?? null;
        $qb = $this->createQueryBuilder('d');

        if ($id !== null) {
            $qb
                ->andWhere('d.id = :id')
                ->setParameter('id', $id)
            ;
        }

        return $qb;
    }
}
