<?php

namespace App\Repository\Organization;

use App\Entity\Organization\OrganizationInvitation;
use App\Entity\User\User;
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

    public function userHasPendingInvitation(User $user): bool
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)')
            ->where('oi.email = :email')
            ->andWhere('oi.dateRefuse IS NULL AND oi.dateAccept IS NULL')
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
