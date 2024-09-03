<?php

namespace App\Repository\Bundle;

use App\Entity\Bundle\Bundle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bundle>
 *
 * @method Bundle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bundle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bundle[]    findAll()
 * @method Bundle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BundleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bundle::class);
    }
}
