<?php

namespace App\Repository;

use App\Entity\LexikMaintenance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LexikMaintenance>
 *
 * @method LexikMaintenance|null find($id, $lockMode = null, $lockVersion = null)
 * @method LexikMaintenance|null findOneBy(array $criteria, array $orderBy = null)
 * @method LexikMaintenance[]    findAll()
 * @method LexikMaintenance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LexikMaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LexikMaintenance::class);
    }

//    /**
//     * @return LexikMaintenance[] Returns an array of LexikMaintenance objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LexikMaintenance
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
