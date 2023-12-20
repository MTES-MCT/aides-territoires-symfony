<?php

namespace App\Repository\Log;

use App\Entity\Log\LogBlogPromotionPostClick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBlogPromotionPostClick>
 *
 * @method LogBlogPromotionPostClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBlogPromotionPostClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBlogPromotionPostClick[]    findAll()
 * @method LogBlogPromotionPostClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBlogPromotionPostClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBlogPromotionPostClick::class);
    }

//    /**
//     * @return LogBlogPromotionPostClick[] Returns an array of LogBlogPromotionPostClick objects
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

//    public function findOneBySomeField($value): ?LogBlogPromotionPostClick
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
