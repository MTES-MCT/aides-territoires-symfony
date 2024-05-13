<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidLock>
 *
 * @method AidLock|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidLock|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidLock[]    findAll()
 * @method AidLock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidLock::class);
    }
}
