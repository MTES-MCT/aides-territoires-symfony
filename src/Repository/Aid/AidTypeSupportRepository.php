<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidTypeSupport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidTypeSupport>
 *
 * @method AidTypeSupport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidTypeSupport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidTypeSupport[]    findAll()
 * @method AidTypeSupport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidTypeSupportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidTypeSupport::class);
    }

//    /**
//     * @return AidTypeSupport[] Returns an array of AidTypeSupport objects
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

//    public function findOneBySomeField($value): ?AidTypeSupport
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
