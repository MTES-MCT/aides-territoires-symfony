<?php

namespace App\Repository\Organization;

use App\Entity\Organization\Organization;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 *
 * @method Organization|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organization|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organization[]    findAll()
 * @method Organization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function countCollaborators(User $user): int
    {
        $result = $this->createQueryBuilder('o')
        ->select('COUNT(o.id) AS nb')
        ->innerJoin('o.beneficiairies','beneficiairies')
        ->andWhere('o = :userOrganization')
        ->setParameter('userOrganization', $user->getDefaultOrganization())
        ->andWhere('beneficiairies != :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult()
        ;
        return $result[0]['nb'] ?? 0;

    }

    public function findCounties(Organization $organization)
    {

    }
}
