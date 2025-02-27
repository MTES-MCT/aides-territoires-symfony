<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidViewTemp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidViewTemp>
 */
class LogAidViewTempRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidViewTemp::class);
    }

    /**
     * Nombre de vues par source
     *
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, source: string}>
     */
    public function countBySource(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('COUNT(lav.id) as nb, lav.source');
        $qb->groupBy('lav.source');
        $qb->orderBy('nb', 'DESC');

        return $qb->getQuery()->getResult();
    }

        /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $dateCreate = $params['dateCreate'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;
        $aidIds = $params['aidIds'] ?? null;
        $excludeSources = $params['excludeSources'] ?? null;
        $sources = $params['sources'] ?? null;
        $notSource = $params['notSource'] ?? null;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('lav');

        if (is_array($excludeSources) && !empty($excludeSources)) {
            $qb
                ->andWhere('lav.source NOT IN (:excludeSources)')
                ->setParameter('excludeSources', $excludeSources)
            ;
        }

        if (is_array($sources) && !empty($sources)) {
            $qb
                ->andWhere('lav.source IN (:sources)')
                ->setParameter('sources', $sources)
            ;
        }

        if ($notSource !== null) {
            $qb
                ->andWhere('lav.source != :notSource')
                ->setParameter('notSource', $notSource)
            ;
        }
        if ($author instanceof User && $author->getId()) {
            $qb
                ->innerJoin('lav.aid', 'aid')
                ->andWhere('aid.author = :author')
                ->setParameter('author', $author)
            ;
        }

        if ($aid instanceof Aid && $aid->getId()) {
            $qb
                ->andWhere('lav.aid = :aid')
                ->setParameter('aid', $aid)
            ;
        }

        if (is_array($aidIds) && !empty($aidIds)) {
            $qb
                ->andWhere('lav.aid IN (:aidIds)')
                ->setParameter('aidIds', $aidIds)
            ;
        }

        if ($dateCreate instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate = :dateCreate')
                ->setParameter('dateCreate', $dateCreate)
            ;
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin->format('Y-m-d'))
            ;
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax->format('Y-m-d'))
            ;
        }

        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin->format('Y-m-d'))
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax->format('Y-m-d'))
            ;
        }

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
