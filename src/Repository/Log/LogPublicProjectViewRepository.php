<?php

namespace App\Repository\Log;

use App\Entity\Log\LogPublicProjectView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogPublicProjectView>
 *
 * @method LogPublicProjectView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogPublicProjectView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogPublicProjectView[]    findAll()
 * @method LogPublicProjectView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogPublicProjectViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogPublicProjectView::class);
    }

//    /**
//     * @return LogPublicProjectView[] Returns an array of LogPublicProjectView objects
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

//    public function findOneBySomeField($value): ?LogPublicProjectView
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
