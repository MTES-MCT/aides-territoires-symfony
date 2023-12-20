<?php

namespace App\Repository\Organization;

use App\Entity\Organization\OrganizationTypeGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationTypeGroup>
 *
 * @method OrganizationTypeGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationTypeGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationTypeGroup[]    findAll()
 * @method OrganizationTypeGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationTypeGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationTypeGroup::class);
    }

//    /**
//     * @return OrganizationTypeGroup[] Returns an array of OrganizationTypeGroup objects
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

//    public function findOneBySomeField($value): ?OrganizationTypeGroup
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
