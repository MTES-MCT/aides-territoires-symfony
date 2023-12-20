<?php

namespace App\Repository\Log;

use App\Entity\Log\LogProgramView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogProgramView>
 *
 * @method LogProgramView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogProgramView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogProgramView[]    findAll()
 * @method LogProgramView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogProgramViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogProgramView::class);
    }

//    /**
//     * @return LogProgramView[] Returns an array of LogProgramView objects
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

//    public function findOneBySomeField($value): ?LogProgramView
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
