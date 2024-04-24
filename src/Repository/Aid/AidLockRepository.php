<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidLock>
 *
 * @method AidLock|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidLock|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidLock[]    findAll()
 * @method AidLock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidLock::class);
    }

    //    /**
    //     * @return AidLock[] Returns an array of AidLock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AidLock
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
