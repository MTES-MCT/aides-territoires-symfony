<?php

namespace App\Repository\Site;

use App\Entity\Site\AbTest;
use App\Entity\Site\AbTestUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbTestUser>
 */
class AbTestUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbTestUser::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return integer
     */
    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('COUNT(au.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $abTest = $params['abTest'] ?? null;
        $variation = $params['variation'] ?? null;
        $refused = $params['refused'] ?? null;

        $qb = $this->createQueryBuilder('au');

        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('au.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('au.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax)
            ;
        }

        if ($abTest instanceof AbTest && $abTest->getId()) {
            $qb
                ->andWhere('au.abTest = :abTest')
                ->setParameter('abTest', $abTest)
            ;
        }

        if ($variation !== null) {
            $qb
                ->andWhere('au.variation = :variation')
                ->setParameter('variation', $variation)
            ;
        }

        if ($refused !== null) {
            $qb
                ->andWhere('au.refused = :refused')
                ->setParameter('refused', $refused)
            ;
        }

        return $qb;
    }
}
