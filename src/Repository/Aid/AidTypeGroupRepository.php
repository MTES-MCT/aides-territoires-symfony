<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidTypeGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidTypeGroup>
 *
 * @method AidTypeGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidTypeGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidTypeGroup[]    findAll()
 * @method AidTypeGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidTypeGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidTypeGroup::class);
    }

//    /**
//     * @return AidTypeGroup[] Returns an array of AidTypeGroup objects
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

//    public function findOneBySomeField($value): ?AidTypeGroup
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
