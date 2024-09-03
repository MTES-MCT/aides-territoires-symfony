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
}
