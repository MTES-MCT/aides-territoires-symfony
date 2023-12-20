<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerCategory>
 *
 * @method BackerCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerCategory[]    findAll()
 * @method BackerCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerCategory::class);
    }

//    /**
//     * @return BackerCategory[] Returns an array of BackerCategory objects
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

//    public function findOneBySomeField($value): ?BackerCategory
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
