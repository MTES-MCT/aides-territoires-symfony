<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerLock>
 *
 * @method BackerLock|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerLock|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerLock[]    findAll()
 * @method BackerLock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerLock::class);
    }

//    /**
//     * @return BackerLock[] Returns an array of BackerLock objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BackerLock
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
