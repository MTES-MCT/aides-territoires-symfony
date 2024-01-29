<?php

namespace App\Repository\Log;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidView;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidView>
 *
 * @method LogAidView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidView[]    findAll()
 * @method LogAidView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidView::class);
    }

    public function countLastWeek(array $params = null)
    {
        $lastWeek = new \DateTime(date('Y-m-d'));
        $lastWeek->sub(new \DateInterval('P7D'));
        $params['dateMin'] = $lastWeek;

        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(lav.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(lav.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countAidsViews(?array $params = null): int
    {
        $distinctAids = $params['distinctAids'] ?? null;
        $qb = $this->getQueryBuilder($params);

        $qb
            ->innerJoin('lav.aid', 'aidForCount')
        ;
        if ($distinctAids !== null) {
            $qb->select('IFNULL(COUNT(DISTINCT(aidForCount.id)), 0) AS nb');
        } else {
            $qb->select('IFNULL(COUNT(aidForCount.id), 0) AS nb');
        }
        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;
        $excludeSources = $params['excludeSources'] ?? null;

        $qb = $this->createQueryBuilder('lav');

        if (is_array($excludeSources) && count($excludeSources) > 0) {
            $qb
                ->andWhere('lav.source NOT IN (:excludeSources)')
                ->setParameter('excludeSources', $excludeSources)
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
        
        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('lav.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb;
    }
}
