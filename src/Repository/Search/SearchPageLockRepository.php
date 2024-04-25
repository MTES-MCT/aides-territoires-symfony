<?php

namespace App\Repository\Search;

use App\Entity\Search\SearchPageLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SearchPageLock>
 *
 * @method SearchPageLock|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchPageLock|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchPageLock[]    findAll()
 * @method SearchPageLock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchPageLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchPageLock::class);
    }

    //    /**
    //     * @return SearchPageLock[] Returns an array of SearchPageLock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SearchPageLock
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
