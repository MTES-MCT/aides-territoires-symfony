<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerUser>
 *
 * @method BackerUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerUser[]    findAll()
 * @method BackerUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerUser::class);
    }

//    /**
//     * @return BackerUser[] Returns an array of BackerUser objects
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

//    public function findOneBySomeField($value): ?BackerUser
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
