<?php

namespace App\Repository\Organization;

use App\Entity\Organization\OrganizationInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationInvitation>
 *
 * @method OrganizationInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationInvitation[]    findAll()
 * @method OrganizationInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationInvitation::class);
    }

//    /**
//     * @return OrganizationInvitation[] Returns an array of OrganizationInvitation objects
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

//    public function findOneBySomeField($value): ?OrganizationInvitation
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
