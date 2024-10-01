<?php

namespace App\Repository\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidProject>
 *
 * @method AidProject|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidProject|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidProject[]    findAll()
 * @method AidProject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidProject::class);
    }

    public function countDistinctAids(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb
            ->innerJoin('ap.aid', 'aid')
            ->select('IFNULL(COUNT(DISTINCT(aid.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countProjectByAid(Aid $aid, array $params = null): int
    {
        $params['aid'] = $aid;
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(ap.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countProjectByAidByDay(Aid $aid, array $params = null): array
    {
        $params['aid'] = $aid;
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('IFNULL(COUNT(ap.id), 0) AS nb')
            ->addSelect('DATE_FORMAT(ap.dateCreate, \'%Y-%m-%d\') as dateDay')
            ->groupBy('dateDay')
            ->orderBy('dateDay', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(ap.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countCreatedByMonth(array $params = []): array
    {
        $organizationTypeSlug = $params['organizationTypeSlug'] ?? null;

        $qb = $this->getQueryBuilder($params);
        $qb
            ->select('COUNT(DISTINCT(ap.id)) as nb, DATE_FORMAT(ap.dateCreate, \'%Y-%m\') as mois')
            ->innerJoin('ap.project', 'project')
            ->innerJoin('project.organization', 'organization')
        ;
        if ($organizationTypeSlug) {
            $qb
                ->innerJoin('organization.organizationType', 'organizationType')
                ->andWhere('organizationType.slug = :organizationTypeSlug')
                ->setParameter('organizationTypeSlug', $organizationTypeSlug)
            ;
        }
        $qb
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
        ;
        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $aid = $params['aid'] ?? null;
        $projectPublic = $params['projectPublic'] ?? null;
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $dateCreate = $params['dateCreate'] ?? null;

        $qb = $this->createQueryBuilder('ap');

        if ($aid instanceof Aid && $aid->getId()) {
            $qb
                ->andWhere('ap.aid = :aid')
                ->setParameter('aid', $aid)
            ;
        }

        if ($projectPublic !== null) {
            $qb
                ->innerJoin('ap.project', 'projectForPublic')
                ->andWhere('projectForPublic.isPublic = :projectPublic')
                ->setParameter('projectPublic', $projectPublic)
            ;
        }

        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('ap.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('ap.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        if ($dateCreate instanceof \DateTime) {
            $qb
                ->andWhere('ap.dateCreate = :dateCreate')
                ->setParameter('dateCreate', $dateCreate)
            ;
        }

        return $qb;
    }
}
