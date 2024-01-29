<?php

namespace App\Repository\Perimeter;

use App\Entity\Aid\Aid;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Perimeter>
 *
 * @method Perimeter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Perimeter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Perimeter[]    findAll()
 * @method Perimeter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PerimeterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Perimeter::class);
    }

    public function getIdPerimetersContainedIn(array $params = null) : array {
        $ids = [];
        
        $qb = $this->createQueryBuilder('p')
        ->select('perimetersFrom.id')
        ->innerJoin('p.perimetersFrom', 'perimetersFrom')
        ->andWhere('p = :perimeter')
        ->setParameter('perimeter', $params['perimeter']);

        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        $qb = $this->createQueryBuilder('p')
        ->select('perimetersTo.id')
        ->innerJoin('p.perimetersTo', 'perimetersTo')
        ->andWhere('p = :perimeter')
        ->setParameter('perimeter', $params['perimeter']);

        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        array_unique($ids);
        return $ids;
    }


    public function findNbBackersByCounty(array $params = null): array
    {
        $qb = $this->createQueryBuilder('p')
        // ->select('IFNULL(COUNT(DISTINCT(backer.id)), 0) AS nbBacker, p.name, p.code')
        ->select('backersFrom.id as backersFromId, backersTo.id as backersToId, p.name, p.code, perimetersFrom.name as nameTo')
        ->innerJoin('p.perimetersFrom', 'perimetersFrom')
        ->innerJoin('perimetersFrom.aids', 'aidsFrom')
        
        ->innerJoin('perimetersFrom.backers', 'backersFrom')
        ->innerJoin('p.perimetersTo', 'perimetersTo')
        ->innerJoin('perimetersTo.backers', 'backersTo')
        ->innerJoin('perimetersTo.aids', 'aidsTo')
        // ->innerJoin('aids.aidFinancers', 'aidFinancers')
        // ->innerJoin('aidFinancers.backer', 'backer')
        // aid live
        ->andWhere('aidsFrom.status = :statusPublished')
        ->setParameter('statusPublished', Aid::STATUS_PUBLISHED)
        ->andWhere('(aidsFrom.dateStart <= :today OR aidsFrom.dateStart IS NULL)')
        ->andWhere('(aidsFrom.dateSubmissionDeadline > :today OR aidsFrom.dateSubmissionDeadline IS NULL)')
        ->setParameter('today', new \DateTime(date('Y-m-d')))

        ->andWhere('aidsTo.status = :statusPublished')
        ->andWhere('(aidsTo.dateStart <= :today OR aidsTo.dateStart IS NULL)')
        ->andWhere('(aidsTo.dateSubmissionDeadline > :today OR aidsTo.dateSubmissionDeadline IS NULL)')

        // departement
        ->andWhere('p.scale = :scaleCounty')
        ->setParameter('scaleCounty', Perimeter::SCALE_COUNTY)
        // ->groupBy('p.code')
        ->andWhere('p.code = :code')->setParameter('code', '91')
        ;


        $results = $qb->getQuery()->getResult();

        $resultsByCode = [];
        foreach ($results as $result) {
            if (!isset($resultsByCode[$result['code']])) {
                $resultsByCode[$result['code']] = [
                    'nbBackers' => 0,
                    'backersFrom' => [],
                    'backersTo' => [],
                    'backers' => [],
                    'name' => $result['name'],
                    'code' => $result['code']
                ];
            }

            if ($result['backersFromId']) {
                $resultsByCode[$result['code']]['backersFrom'][] = $result['backersFromId'];
            }
            if ($result['backersToId']) {
                $resultsByCode[$result['code']]['backersTo'][] = $result['backersToId'];
            }
        }

        foreach ($resultsByCode as $key => $resultByCode) {
            $resultsByCode[$key]['backersFrom'] = array_unique($resultByCode['backersFrom']);
            $resultsByCode[$key]['backersTo'] = array_unique($resultByCode['backersTo']);
            $resultsByCode[$key]['backers'] = array_merge($resultByCode['backersFrom'], $resultByCode['backersTo']);
            $resultsByCode[$key]['backers'] = array_unique($resultsByCode[$key]['backers']);
        }

        return $resultsByCode;
    }

    
    public function findCounties(array $params = null): array
    {
        $params['scale'] = Perimeter::SCALE_COUNTY;
        $params['orderBy'] = [
            'sort' => 'p.code',
            'order' => 'ASC'
        ];
        $qb = $this->getQueryBuilder($params);

        $results = $qb->getQuery()->getResult();
        return $results;
    }

    public function countEcpi(?array $params = null) : int
    {
        $params['scale'] = Perimeter::SCALE_EPCI;
        $params['isObsolete'] = false;
        return $this->countCustom($params);        
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(p.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $scale = $params['scale'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        $codes = $params['codes'] ?? null;
        $insees = $params['insees'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;
        $nameMatchAgainst = $params['nameMatchAgainst'] ?? null;
        $isVisibleToUsers = $params['isVisibleToUsers'] ?? null;
        $scaleLowerThan = $params['scaleLowerThan'] ?? null;
        $isObsolete = $params['isObsolete'] ?? null;
        $ids = $params['ids'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if (is_array($ids) && count($ids) > 0) {
            $qb
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;
        }
        if ($isObsolete !== null) {
            $qb->andWhere('p.isObsolete = :isObsolete')
                ->setParameter('isObsolete', $isObsolete);
        }
        if ($nameMatchAgainst !== null)
        {
            $qb
            ->andWhere('MATCH_AGAINST(p.name) AGAINST (:nameMatchAgainst IN BOOLEAN MODE) > 5')
            ->setParameter('nameMatchAgainst', $nameMatchAgainst);
            
        }
        
        if (is_array($insees)) {
            $qb
                ->andWhere('p.insee IN (:insees)')
                ->setParameter('insees', $insees)
            ;
        }

        if ($isVisibleToUsers !== null)
        {
            $qb
            ->andWhere('p.isVisibleToUsers = :isVisibleToUsers')
            ->setParameter('isVisibleToUsers', $isVisibleToUsers);
            
        }

        if ($scaleLowerThan !== null)
        {
            $qb
            ->andWhere('p.scale < :scaleLowerThan')
            ->setParameter('scaleLowerThan', $scaleLowerThan);
            
        }

        if ($scale !== null) {
            $qb
                ->andWhere('p.scale = :scale')
                ->setParameter('scale', $scale)
            ;
        }

        if (is_array($codes) && count($codes) > 0) {
            $qb
                ->andWhere('p.code IN (:codes)')
                ->setParameter('codes', $codes)
            ;
        }

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($firstResult !== null) {
            $qb->setFirstResult($firstResult);
        }
        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
