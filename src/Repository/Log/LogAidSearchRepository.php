<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidSearch;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidSearch>
 *
 * @method LogAidSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidSearch[]    findAll()
 * @method LogAidSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidSearch::class);
    }

    public function findKeywordSearchWithFewResults(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }
    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('COUNT(l.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countApiByDay(?array $params = null)
    {
        $params['source'] = 'api';
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(l.id) as nb, DATE_FORMAT(l.timeCreate, \'%Y-%m-%d\') as dateDay');
        $qb->groupBy('dateDay');

        return $qb->getQuery()->getResult();
    }

    public function countByOrganization(?array $params = null)
    {
        $params['source'] = 'api';
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(l.id) as nb, organization.id as organizationId, organization.name as organizationName');
        $qb->innerJoin('l.organization', 'organization');
        $qb->groupBy('organizationId');

        return $qb->getQuery()->getResult();
    }

    public function getSearchOnPerimeterWithoutOrganization($params): array
    {
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;

        $qb = $this->createQueryBuilder('l')
            ->select('perimeter.id, perimeter.name, perimeter.insee')
            ->innerJoin('l.perimeter', 'perimeter')
            ->leftJoin('perimeter.organizations', 'organizations')
            ->where('perimeter.scale IN (:scales)')
            ->andWhere('organizations.id IS NULL')
            ->groupBy('perimeter.id')
            ->orderBy('perimeter.insee')
            ->setParameter('scales', [Perimeter::SCALE_COMMUNE, Perimeter::SCALE_EPCI]);

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

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $hasSearch = $params['hasSearch'] ?? null;
        $source = $params['source'] ?? null;
        $resultsCountMax = $params['resultsCountMax'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('l');

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

        if ($hasSearch) {
            $qb
                ->andWhere('l.search IS NOT NULL');
        }

        if ($source !== null) {
            $qb
                ->andWhere('l.source = :source')
                ->setParameter('source', $source)
            ;
        }

        if ($resultsCountMax) {
            $qb
                ->andWhere('l.resultsCount <= :resultsCountMax')
                ->setParameter('resultsCountMax', $resultsCountMax)
            ;
        }

        if ($orderBy !== null) {
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
        }


        return $qb;
    }
}
