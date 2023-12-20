<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAccountRegisterFromNextPageWarningClickEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAccountRegisterFromNextPageWarningClickEvent>
 *
 * @method LogAccountRegisterFromNextPageWarningClickEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAccountRegisterFromNextPageWarningClickEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAccountRegisterFromNextPageWarningClickEvent[]    findAll()
 * @method LogAccountRegisterFromNextPageWarningClickEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAccountRegisterFromNextPageWarningClickEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAccountRegisterFromNextPageWarningClickEvent::class);
    }

//    /**
//     * @return LogAccountRegisterFromNextPageWarningClickEvent[] Returns an array of LogAccountRegisterFromNextPageWarningClickEvent objects
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

//    public function findOneBySomeField($value): ?LogAccountRegisterFromNextPageWarningClickEvent
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
