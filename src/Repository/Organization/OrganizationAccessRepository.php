<?php

namespace App\Repository\Organization;

use App\Entity\Organization\OrganizationAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationAccess>
 *
 * @method OrganizationAccess|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationAccess|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationAccess[]    findAll()
 * @method OrganizationAccess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationAccess::class);
    }

    //    /**
    //     * @return OrganizationAccess[] Returns an array of OrganizationAccess objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?OrganizationAccess
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
