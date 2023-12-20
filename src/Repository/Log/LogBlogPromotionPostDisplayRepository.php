<?php

namespace App\Repository\Log;

use App\Entity\Log\LogBlogPromotionPostDisplay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBlogPromotionPostDisplay>
 *
 * @method LogBlogPromotionPostDisplay|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBlogPromotionPostDisplay|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBlogPromotionPostDisplay[]    findAll()
 * @method LogBlogPromotionPostDisplay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBlogPromotionPostDisplayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBlogPromotionPostDisplay::class);
    }

//    /**
//     * @return LogBlogPromotionPostDisplay[] Returns an array of LogBlogPromotionPostDisplay objects
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

//    public function findOneBySomeField($value): ?LogBlogPromotionPostDisplay
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
