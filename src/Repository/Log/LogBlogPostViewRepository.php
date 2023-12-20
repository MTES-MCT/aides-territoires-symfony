<?php

namespace App\Repository\Log;

use App\Entity\Log\LogBlogPostView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBlogPostView>
 *
 * @method LogBlogPostView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBlogPostView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBlogPostView[]    findAll()
 * @method LogBlogPostView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBlogPostViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBlogPostView::class);
    }

//    /**
//     * @return LogBlogPostView[] Returns an array of LogBlogPostView objects
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

//    public function findOneBySomeField($value): ?LogBlogPostView
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
