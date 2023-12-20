<?php

namespace App\Repository\Log;

use App\Entity\Log\LogBackerView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBackerView>
 *
 * @method LogBackerView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBackerView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBackerView[]    findAll()
 * @method LogBackerView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBackerViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBackerView::class);
    }

//    /**
//     * @return LogBackerView[] Returns an array of LogBackerView objects
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

//    public function findOneBySomeField($value): ?LogBackerView
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
