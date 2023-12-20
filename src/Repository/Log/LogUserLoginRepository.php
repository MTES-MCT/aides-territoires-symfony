<?php

namespace App\Repository\Log;

use App\Entity\Log\LogUserLogin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUserLogin>
 *
 * @method LogUserLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogUserLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogUserLogin[]    findAll()
 * @method LogUserLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogUserLoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUserLogin::class);
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $user = $params['user'] ?? null;
        $limit = $params['limit'] ?? null;
        $action = $params['action'] ?? null;

        $qb = $this->createQueryBuilder('l');

        if ($user !== null) {
            $qb
                ->andWhere('l.user = :user')
                ->setParameter('user', $user)
                ;
        }
    
        if ($action !== null) {
            $qb
                ->andWhere('l.action = :action')
                ->setParameter('action', $action)
                ;
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

//    /**
//     * @return LogUserLogin[] Returns an array of LogUserLogin objects
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

//    public function findOneBySomeField($value): ?LogUserLogin
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
