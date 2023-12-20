<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidSearch>
 *
 * @method LogAidSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidSearch[]    findAll()
 * @method LogAidSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidSearch::class);
    }

//    /**
//     * @return LogAidSearch[] Returns an array of LogAidSearch objects
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

//    public function findOneBySomeField($value): ?LogAidSearch
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
