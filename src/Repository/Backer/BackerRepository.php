<?php

namespace App\Repository\Backer;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerCategory;
use App\Entity\Backer\BackerGroup;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
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

    /**
     * @param string $alias
     * @return Criteria
     */
    public static function activeCriteria(string $alias = 'b.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'active', true))
        ;
    }

    /**
     * @param string $alias
     * @return Criteria
     */
    public static function unactiveCriteria(string $alias = 'b.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'active', false))
        ;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
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

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countBackerWithAidInCounty(?array $params = null): int
    {
        $queryBuilder = $this->createQueryBuilder('b');

        /** @var PerimeterRepository $perimeterRepository */
        $perimeterRepository = $this->getEntityManager()->getRepository(Perimeter::class);
        $perimeterFrom = $perimeterRepository->find($params['id']);
        $ids = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $perimeterFrom]);
        $ids[] = $perimeterFrom->getId();

        $queryBuilder
            ->select('COUNT(DISTINCT(b.id)) as nb')
            ->addCriteria(self::activeCriteria())
            ->innerJoin('b.aidFinancers', 'aidFinancers')
            ->innerJoin('aidFinancers.aid', 'aid')
            ->addCriteria(AidRepository::showInSearchCriteria('aid.'))
            ->innerJoin('b.perimeter', 'perimeter')
            ->andWhere('perimeter.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        return $queryBuilder->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Backer>
     */
    public function findBackerWithAidInCounty(?array $params = null): array
    {
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $organizationType = $params['organizationType'] ?? null;
        $aidTypeGroup = $params['aidTypeGroup'] ?? null;
        $categoryIds = $params['categoryIds'] ?? null;
        $perimeterScales = $params['perimeterScales'] ?? null;
        $backerCategory = $params['backerCategory'] ?? null;
        $active = isset($params['active']) ? $params['active'] : null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('b');

        if (true === $active) {
            $qb
                ->addCriteria(self::activeCriteria());
        } elseif (false === $active) {
            $qb
                ->addCriteria(self::unactiveCriteria());
        }

        $qb
            ->leftJoin('b.backerGroup', 'backerGroup')
            ->addSelect('backerGroup')
            ->innerJoin('b.aidFinancers', 'aidFinancers')
            ->innerJoin('aidFinancers.aid', 'aid')
            // aid live
            ->addCriteria(AidRepository::showInSearchCriteria('aid.'))
        ;
        if ($perimeterFrom instanceof Perimeter && $perimeterFrom->getId()) {
            /** @var PerimeterRepository $perimeterRepository */
            $perimeterRepository = $this->getEntityManager()->getRepository(Perimeter::class);
            $ids = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $perimeterFrom]);
            $ids[] = $perimeterFrom->getId();

            $qb
                ->innerJoin('b.perimeter', 'perimeter')
                ->addSelect('perimeter')
                ->andWhere('perimeter.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;

            $backerGroupAdded = true;
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

        if ($backerCategory instanceof BackerCategory && $backerCategory->getId()) {
            if (!isset($backerGroupAdded)) {
                $qb
                    ->innerJoin('b.backerGroup', 'backerGroup')
                ;
            }
            $qb
                ->innerJoin('backerGroup.backerSubCategory', 'backerSubCategory')
                ->innerJoin('backerSubCategory.backerCategory', 'backerCategory')
                ->andWhere('backerCategory = :backerCategory')
                ->setParameter('backerCategory', $backerCategory)
            ;
        }

        if (null !== $orderBy) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countWithAids(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('IFNULL(COUNT(DISTINCT(b.id)), 0) AS nb')
            ->addCriteria(self::activeCriteria())
            ->innerJoin('b.aidFinancers', 'aidFinancers')
        ;

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(b.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Backer>
     */
    public function findAidLive(?array $params = null): array
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
            ->setParameter('backer', $params['backer']);

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

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Backer>
     */
    public function findAidFinancersFinancial(?array $params = null): array
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

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Backer>
     */
    public function findSelectedForHome(?array $params = null): array
    {
        $params['hasLogo'] = true;
        $params['isSpotlighted'] = true;
        $params['orderRand'] = true;
        $params['limit'] = 5;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Backer>
     */
    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $ids = $params['ids'] ?? null;
        $hasLogo = $params['hasLogo'] ?? null;
        $isSpotlighted = $params['isSpotlighted'] ?? null;
        $orderRand = $params['orderRand'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $hasFinancedAids = $params['hasFinancedAids'] ?? null;
        $hasPublishedFinancedAids = $params['hasPublishedFinancedAids'] ?? null;
        $active = isset($params['active']) ? $params['active'] : null;
        $nbAidsLiveMin = $params['nbAidsLiveMin'] ?? null;
        $backerGroup = $params['backerGroup'] ?? null;
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $limit = $params['limit'] ?? null;

        $qb = $this->createQueryBuilder('b');

        if (is_array($ids) && !empty($ids)) {
            $qb
                ->andWhere('b.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if (true === $active) {
            $qb
                ->addCriteria(BackerRepository::activeCriteria());
        } elseif (false === $active) {
            $qb
                ->addCriteria(BackerRepository::unactiveCriteria());
        }

        if (null !== $nbAidsLiveMin) {
            $qb
                ->andWhere('b.nbAidsLive >= :nbAidsLiveMin')
                ->setParameter('nbAidsLiveMin', $nbAidsLiveMin)
            ;
        }

        if (true === $hasLogo) {
            $qb
                ->andWhere('b.logo IS NOT NULL');
        }

        if (true === $isSpotlighted) {
            $qb
                ->andWhere('b.isSpotlighted = :isSpotlighted')
                ->setParameter('isSpotlighted', true)
            ;
        }

        if (null !== $nameLike) {
            $qb
                ->andWhere('b.name LIKE :nameLike')
                ->setParameter('nameLike', '%'.$nameLike.'%')
            ;
        }

        if (null !== $hasFinancedAids) {
            if ($hasFinancedAids) {
                $qb
                    ->innerJoin('b.aidFinancers', 'aidFinancersForHasFinancedAids');
            } else {
                $qb
                    ->leftJoin('b.aidFinancers', 'aidFinancersForHasFinancedAids')
                    ->andWhere('aidFinancersForHasFinancedAids.id IS NULL');
            }
        }

        if (null !== $hasPublishedFinancedAids) {
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

        if ($backerGroup instanceof BackerGroup && $backerGroup->getId()) {
            $qb
                ->andWhere('b.backerGroup = :backerGroup')
                ->setParameter('backerGroup', $backerGroup)
            ;
        }

        if ($perimeterFrom instanceof Perimeter && $perimeterFrom->getId()) {
            /** @var PerimeterRepository $perimeterRepository */
            $perimeterRepository = $this->getEntityManager()->getRepository(Perimeter::class);
            $ids = $perimeterRepository->getIdPerimetersContainedIn(['perimeter' => $perimeterFrom]);
            $ids[] = $perimeterFrom->getId();

            $qb
                ->innerJoin('b.perimeter', 'perimeter')
                ->andWhere('perimeter.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;
        }

        if (true === $orderRand) {
            $qb->orderBy('RAND()');
        }

        if (null !== $orderBy) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
        }

        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
}
