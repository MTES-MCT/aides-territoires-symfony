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

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(lav.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countAidsViews(?array $params = null): int
    {
        $distinctAids = $params['distinctAids'] ?? null;
        $qb = $this->getQueryBuilder($params);

        $qb
            ->innerJoin('lav.aid', 'aidForCount');
        if ($distinctAids !== null) {
            $qb->select('IFNULL(COUNT(DISTINCT(aidForCount.id)), 0) AS nb');
        } else {
            $qb->select('IFNULL(COUNT(aidForCount.id), 0) AS nb');
        }
        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countApiByDay(?array $params = null)
    {
        $params['source'] = 'api';
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(lav.id) as nb, DATE_FORMAT(lav.timeCreate, \'%Y-%m-%d\') as dateDay');
        $qb->groupBy('dateDay');

        return $qb->getQuery()->getResult();
    }

    public function countByOrganization(?array $params = null)
    {
        $params['source'] = 'api';
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(lav.id) as nb, organization.id as organizationId, organization.name as organizationName');
        $qb->innerJoin('lav.organization', 'organization');
        $qb->groupBy('organizationId');

        return $qb->getQuery()->getResult();
    }

    public function countTop(?array $params = null): array
    {
        $maxResults = $params['maxResults'] ?? null;
        $aidIds = $params['aidIds'] ?? null;
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(lav.id), 0) AS nb, aid.id AS id, aid.name AS name, aid.slug AS slug')
            ->innerJoin('lav.aid', 'aid')
            ->groupBy('aid.id')
            ->orderBy('nb', 'DESC')
        ;

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

        if (is_array($aidIds) && count($aidIds) > 0) {
            $qb
                ->andWhere('lav.aid IN (:aidIds)')
                ->setParameter('aidIds', $aidIds)
            ;
        }

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    public function countOrganizationTypes(?array $params = null): array
    {
        $aidIds = $params['aidIds'] ?? null;
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(DISTINCT(organization.id)), 0) AS nb, organizationType.id AS id, organizationType.name AS name, organizationType.slug AS slug')
            ->leftJoin('lav.organization', 'organization')
            ->leftJoin('organization.organizationType', 'organizationType')
            ->groupBy('organizationType.id')
            ->orderBy('nb', 'DESC')
        ;

        if (is_array($aidIds) && count($aidIds) > 0) {
            $qb
                ->andWhere('lav.aid IN (:aidIds)')
                ->setParameter('aidIds', $aidIds)
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

        return $qb->getQuery()->getResult();
    }

    public function countByMonth(?array $params = null): array
    {
        $aidIds = $params['aidIds'] ?? null;
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(lav.id), 0) AS nb, DATE_FORMAT(lav.dateCreate, \'%Y-%m\') AS monthCreate')
            ->groupBy('monthCreate')
            ->orderBy('monthCreate', 'ASC')
        ;

        if (is_array($aidIds) && count($aidIds) > 0) {
            $qb
                ->andWhere('lav.aid IN (:aidIds)')
                ->setParameter('aidIds', $aidIds)
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

        return $qb->getQuery()->getResult();
    }

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
        $notSource = $params['notSource'] ?? null;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('lav');

        if (is_array($excludeSources) && count($excludeSources) > 0) {
            $qb
                ->andWhere('lav.source NOT IN (:excludeSources)')
                ->setParameter('excludeSources', $excludeSources)
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

        if (is_array($aidIds) && count($aidIds) > 0) {
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
