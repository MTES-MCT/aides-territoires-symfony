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

    /**
     * @param array<string, mixed>|null $params
     * @return integer
     */
    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        return (int) $qb
            ->select('IFNULL(COUNT(l.id), 0)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Nombre de recherche par source
     *
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, source: string}>
     */
    public function countBySource(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('COUNT(l.id) as nb, l.source');
        $qb->groupBy('l.source');
        $qb->orderBy('nb', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $dateCreate = $params['dateCreate'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $sources = $params['sources'] ?? null;
        $noPageInQuery = $params['noPageInQuery'] ?? false;

        $qb = $this->createQueryBuilder('l');

        if ($dateCreate instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate = :dateCreate')
                ->setParameter('dateCreate', $dateCreate)
            ;
        }

        if ($noPageInQuery) {
            $qb->andWhere('l.querystring NOT LIKE \'%page%\'');
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax)
            ;
        }

        if (is_array($sources) && !empty($sources)) {
            $qb
                ->andWhere('l.source IN (:sources)')
                ->setParameter('sources', $sources)
            ;
        }

        return $qb;
    }
}
