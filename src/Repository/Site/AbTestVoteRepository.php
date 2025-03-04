<?php

namespace App\Repository\Site;

use App\Entity\Site\AbTest;
use App\Entity\Site\AbTestVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbTestVote>
 */
class AbTestVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbTestVote::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, AbTestVote>
     */
    public function findCustom(?array $params = null)
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
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $abTest = $params['abTest'] ?? null;

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

        return $qb;
    }
}
