<?php

namespace App\Repository\User;

use App\Entity\User\UserGroupPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGroupPermission>
 *
 * @method UserGroupPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGroupPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGroupPermission[]    findAll()
 * @method UserGroupPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGroupPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroupPermission::class);
    }

//    /**
//     * @return UserGroupPermission[] Returns an array of UserGroupPermission objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserGroupPermission
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
