<?php

namespace App\Repository\Log;

use App\Entity\Log\LogUrlRedirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUrlRedirect>
 */
class LogUrlRedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUrlRedirect::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array{oldUrl: string, newUrl: string, nb: string}>
     */
    public function findGroupByUrl(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->innerJoin('l.urlRedirect', 'urlRedirect');
        $qb->select('urlRedirect.oldUrl, urlRedirect.newUrl, COUNT(l.id) as nb');
        $qb->groupBy('urlRedirect.oldUrl, urlRedirect.newUrl');
        $qb->orderBy('nb', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;

        $qb = $this->createQueryBuilder('l');

        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('l.timeCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('l.timeCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax)
            ;
        }

        return $qb;
    }
}
