<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidTypeGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidTypeGroup>
 *
 * @method AidTypeGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidTypeGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidTypeGroup[]    findAll()
 * @method AidTypeGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidTypeGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidTypeGroup::class);
    }
}
