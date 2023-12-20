<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerGroup>
 *
 * @method BackerGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerGroup[]    findAll()
 * @method BackerGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerGroup::class);
    }
}
