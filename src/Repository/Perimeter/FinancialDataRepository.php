<?php

namespace App\Repository\Perimeter;

use App\Entity\Perimeter\FinancialData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinancialData>
 *
 * @method FinancialData|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancialData|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancialData[]    findAll()
 * @method FinancialData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancialDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancialData::class);
    }

//    /**
//     * @return FinancialData[] Returns an array of FinancialData objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FinancialData
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
