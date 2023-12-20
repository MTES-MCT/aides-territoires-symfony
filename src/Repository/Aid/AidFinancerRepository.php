<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidFinancer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidFinancer>
 *
 * @method AidFinancer|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidFinancer|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidFinancer[]    findAll()
 * @method AidFinancer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidFinancerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidFinancer::class);
    }
}
