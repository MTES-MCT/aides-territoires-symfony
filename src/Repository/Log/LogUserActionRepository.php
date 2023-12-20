<?php

namespace App\Repository\Log;

use App\Entity\Log\LogUserAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUserAction>
 *
 * @method LogUserAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogUserAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogUserAction[]    findAll()
 * @method LogUserAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogUserActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUserAction::class);
    }

//    /**
//     * @return LogUserAction[] Returns an array of LogUserAction objects
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

//    public function findOneBySomeField($value): ?LogUserAction
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
