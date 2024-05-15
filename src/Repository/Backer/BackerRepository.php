<?php

namespace App\Repository\Backer;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerCategory;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use App\Repository\Aid\AidRepository;
use App\Repository\Perimeter\PerimeterRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Backer>
 *
 * @method Backer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Backer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Backer[]    findAll()
 * @method Backer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Backer::class);
    }

    public static function activeCriteria($alias = 'b.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'active', true))
        ;
    }

    public static function unactiveCriteria($alias = 'b.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'active', false))
        ;
    }

    public function countReviewable(?array $params = null): int
    {
        try {
            $qb = $this->getQueryBuilder($params);
            $qb->select('IFNULL(COUNT(b.id), 0) AS nb');
            $qb->addCriteria(self::unactiveCriteria());

            return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function countBackerWithAidInCounty(array $params = null)
    {

        $queryBuilder = $this->createQueryBuilder('b');

        $queryBuilder
            ->select('COUNT(DISTINCT(b.id)) as nb')
            ->addCriteria(self::activeCriteria())
            ->innerJoin('b.aidFinancers', 'aidFinancers')
            ->innerJoin('aidFinancers.aid', 'aid')
            ->addCriteria(AidRepository::liveCriteria('aid.'))
            ->innerJoin('b.perimeter', 'perimeter')
            ->innerJoin('perimeter.perimetersFrom', 'perimetersFrom')
            ->andWhere('(perimeter.id = :id OR perimetersFrom.id = :id)')
            ->setParameter('id', $params['id'])
            ;

        return $queryBuilder->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findBackerWithAidInCounty(array $params = null)
    {
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $organizationType = $params['organizationType'] ?? null;
        $aidTypeGroup = $params['aidTypeGroup'] ?? null;
        $categoryIds = $params['categoryIds'] ?? null;
        $perimeterScales = $params['perimeterScales'] ?? null;
        $backerCategory = $params['backerCategory'] ?? null;
        $active = isset($params['active']) ? $params['active'] : null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('b');

        if ($active == true) {
            $qb
                ->addCriteria(self::activeCriteria());
        } else if ($active == false) {
            $qb
                ->addCriteria(self::unactiveCriteria());
        }
        
        $qb
            ->innerJoin('b.aidFinancers', 'aidFinancers')
            ->innerJoin('aidFinancers.aid', 'aid')
            // aid live
            ->addCriteria(AidRepository::liveCriteria('aid.'))
            ;
            if ($perimeterFrom instanceof Perimeter && $perimeterFrom->getId()) {
                $qb
                    ->innerJoin('b.perimeter', 'perimeter')
                    ->innerJoin('perimeter.perimetersFrom', 'perimetersFrom')
                    ->andWhere('(perimetersFrom = :perimeter OR perimeter = :perimeter)')
                    ->setParameter('perimeter', $perimeterFrom)
                ;
            }
            

            if ($organizationType instanceof OrganizationType && $organizationType->getId()) {
                $qb
                    ->innerJoin('aid.aidAudiences', 'aidAudiences')
                    ->andWhere('aidAudiences = :organizationType')
                    ->setParameter('organizationType', $organizationType)
                ;
            }

            if ($aidTypeGroup instanceof AidTypeGroup && $aidTypeGroup->getId()) {
                $qb
                    ->innerJoin('aid.aidTypes', 'aidTypes')
                    ->andWhere('aidTypes.aidTypeGroup = :aidTypeGroup')
                    ->setParameter('aidTypeGroup', $aidTypeGroup)
                    ;
            }

            if (is_array($categoryIds) && count($categoryIds) > 0) {
                $qb
                    ->innerJoin('aid.categories', 'categories')
                    ->andWhere('categories.id IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds)
                    ;
            }

            if (is_array($perimeterScales)) {
                $qb
                    ->andWhere('perimeter.scale IN (:perimeterScales)')
                    ->setParameter('perimeterScales', $perimeterScales)
                ;
            }

            if ($backerCategory instanceof BackerCategory && $backerCategory->getId())
            {
                $qb
                    ->innerJoin('b.backerGroup', 'backerGroup')
                    ->innerJoin('backerGroup.backerSubCategory', 'backerSubCategory')
                    ->innerJoin('backerSubCategory.backerCategory', 'backerCategory')
                    ->andWhere('backerCategory = :backerCategory')
                    ->setParameter('backerCategory', $backerCategory)
                ;
            }

            if ($orderBy !== null) {
                $qb
                    ->addOrderBy($orderBy['sort'], $orderBy['order'])
                ;
            }

            return $qb->getQuery()->getResult();
    }

    public function countWithAids(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('IFNULL(COUNT(DISTINCT(b.id)), 0) AS nb')
            ->addCriteria(self::activeCriteria())
            ->innerJoin('b.aidFinancers', 'aidFinancers')
        ;

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }


    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(b.id)), 0) AS nb');
        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findAidLive(array $params = null) : array
    {
        $perimeter = $params['perimeter'] ?? null;

        $qb = $this->createQueryBuilder('b')
        ->innerJoin('b.aidFinancers', 'aidFinancers')
        ->innerJoin('aidFinancers.aid', 'aid')
        ->innerJoin('aid.aidTypes', 'aidTypes')
        ->innerJoin('aidTypes.aidTypeGroup', 'aidTypeGroup')
        ->andWhere('aid.status = :statusPublished')
        ->setParameter('statusPublished', Aid::STATUS_PUBLISHED)
        ->andWhere('(aid.dateStart <= :today OR aid.dateStart IS NULL)')
        ->andWhere('(aid.dateSubmissionDeadline > :today OR aid.dateSubmissionDeadline IS NULL)')
        ->setParameter('today', new \DateTime(date('Y-m-d')))
        ->andWhere('b = :backer')
        ->setParameter('backer', $params['backer'])
        ;

        if ($perimeter instanceof Perimeter && $perimeter->getId()) {
            $qb
                ->innerJoin('aid.perimeter', 'perimeter')
                ->innerJoin('perimeter.perimetersFrom', 'perimetersFrom')
                ->andWhere('perimetersFrom = :perimeter')
                ->setParameter('perimeter', $perimeter)
            ;
        }

        return $qb
        ->getQuery()
        ->getResult()
        ;
    }

    public function findAidFinancersFinancial(array $params = null) : array
    {
        return $this->createQueryBuilder('b')
        ->innerJoin('b.aidFinancers', 'aidFinancers')
        ->innerJoin('aidFinancers.aid', 'aid')
        ->innerJoin('aid.aidTypes', 'aidTypes')
        ->innerJoin('aidTypes.aidTypeGroup', 'aidTypeGroup')
        ->andWhere('aid.status = :statusPublished')
        ->setParameter('statusPublished', Aid::STATUS_PUBLISHED)
        ->andWhere('(aid.dateStart <= :today OR aid.dateStart IS NULL)')
        ->andWhere('(aid.dateSubmissionDeadline > :today OR aid.dateSubmissionDeadline IS NULL)')
        ->setParameter('today', new \DateTime(date('Y-m-d')))
        ->andWhere('aidTypeGroup.slug IN (:slugsFinancial)')
        ->setParameter('slugsFinancial', AidType::TYPE_FINANCIAL_SLUGS)
        ->andWhere('b = :backer')
        ->setParameter('backer', $params['backer'])
        ->getQuery()
        ->getResult()
        ;
    }
    public function findSelectedForHome(array $params = null): array
    {
        $params['hasLogo'] = true;
        $params['isSpotlighted'] = true;
        $params['orderRand'] = true;
        $params['limit'] = 5;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $hasLogo = $params['hasLogo'] ?? null;
        $isSpotlighted = $params['isSpotlighted'] ?? null;
        $orderRand = $params['orderRand'] ?? null;
        $limit = $params['limit'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;
        $limit = $params['limit'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $hasFinancedAids = $params['hasFinancedAids'] ?? null;
        $hasPublishedFinancedAids = $params['hasPublishedFinancedAids'] ?? null;
        $active = isset($params['active']) ? $params['active'] : null;
        $nbAidsLiveMin = $params['nbAidsLiveMin'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        
        $qb = $this->createQueryBuilder('b');

        if ($active === true) {
            $qb
                ->addCriteria(BackerRepository::activeCriteria())
            ;
        } elseif ($active === false) {
            $qb
                ->addCriteria(BackerRepository::unactiveCriteria())
            ;
        }

        if ($nbAidsLiveMin !== null) {
            $qb
                ->andWhere('b.nbAidsLive >= :nbAidsLiveMin')
                ->setParameter('nbAidsLiveMin', $nbAidsLiveMin)
            ;
        }

        if ($hasLogo === true) {
            $qb
                ->andWhere('b.logo IS NOT NULL')
            ;
        }

        if ($isSpotlighted === true) {
            $qb
                ->andWhere('b.isSpotlighted = :isSpotlighted')
                ->setParameter('isSpotlighted', true)
                ;
        }

        if ($nameLike !== null) {
            $qb
                ->andWhere('b.name LIKE :nameLike')
                ->setParameter('nameLike', '%'.$nameLike.'%')
                ;
        }

        if ($hasFinancedAids !== null) {
            if ($hasFinancedAids) {
                $qb
                    ->innerJoin('b.aidFinancers', 'aidFinancersForHasFinancedAids');
            } else {
                $qb
                    ->leftJoin('b.aidFinancers', 'aidFinancersForHasFinancedAids')
                    ->andWhere('aidFinancersForHasFinancedAids.id IS NULL');
            }
        }

        if ($hasPublishedFinancedAids !== null) {
            if ($hasPublishedFinancedAids) {
                $qb
                    ->innerJoin('b.aidFinancers', 'aidFinancersForHasPublishedFinancedAids')
                    ->innerJoin('aidFinancersForHasPublishedFinancedAids.aid', 'aidForHasPublishedFinancedAids')
                    ->addCriteria(AidRepository::liveCriteria('aidForHasPublishedFinancedAids.'))
                    ;
            } else {
                $qb
                    ->leftJoin('b.aidFinancers', 'aidFinancersForHasPublishedFinancedAids')
                    ->leftJoin('aidFinancersForHasPublishedFinancedAids.aid', 'aidForHasPublishedFinancedAids')
                    ->addCriteria(AidRepository::liveCriteria('aidForHasPublishedFinancedAids.'))
                    ->andWhere('aidForHasPublishedFinancedAids.id IS NULL')
                    ;
            }
        }

        if ($orderRand === true) {
            $qb->orderBy('RAND()');
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
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    public function getPrograms(int $backer_id){
        
    }

    public function getCategories(int $backer_id){

    }

}
