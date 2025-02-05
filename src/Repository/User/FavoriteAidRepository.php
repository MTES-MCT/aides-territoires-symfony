<?php

namespace App\Repository\User;

use App\Entity\User\FavoriteAid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FavoriteAid>
 */
class FavoriteAidRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteAid::class);
    }

    /**
     * Compte les aides les plus favorisées.
     *
     * @param array<string, mixed>|null $params
     *
     * @return array<int, mixed>
     */
    public function countTopAids(?array $params = null): array
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->createQueryBuilder('fa')
            ->select('a.id', 'a.name', 'COUNT(fa.id) as nb', 'a.slug')
            ->innerJoin('fa.aid', 'a')
            ->groupBy('a.id')
            ->orderBy('nb', 'DESC')
            ->setMaxResults(10)
        ;

        if ($dateMin instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre d'aides par jour.
     *
     * @param array<string, mixed>|null $params
     *
     * @return array<int, mixed>
     */
    public function countByDay(?array $params = null): array
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->createQueryBuilder('fa')
            ->select('DATE_FORMAT(fa.dateCreate, \'%Y-%m-%d\') as date', 'COUNT(fa.id) as nb')
            ->groupBy('date')
            ->orderBy('date', 'DESC')
        ;

        if ($dateMin instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total d'aides ajoutées en favoris
     *
     * @param array<string, mixed>|null $params
     */
    public function countTotal(?array $params = null): int
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->createQueryBuilder('fa')
            ->select('COUNT(fa.id) as nb')
        ;

        if ($dateMin instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countTopSources(?array $params = null): array
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;

        $qb = $this->createQueryBuilder('fa')
            ->select('las.source', 'COUNT(fa.id) as nb')
            ->innerJoin('fa.logAidSearch', 'las')
            ->groupBy('las.source')
            ->orderBy('nb', 'DESC')
            ->setMaxResults(10)
        ;

        if ($dateMin instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb->andWhere('fa.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
