<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidSearchTemp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidSearchTemp>
 */
class LogAidSearchTempRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidSearchTemp::class);
    }

    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        return (int) $qb
            ->select('IFNULL(COUNT(l.id), 0)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $dateCreate = $params['dateCreate'] ?? null;

        $qb = $this->createQueryBuilder('l');

        if ($dateCreate instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate = :dateCreate')
                ->setParameter('dateCreate', $dateCreate)
            ;
        }

        return $qb;
    }
}
