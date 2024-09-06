<?php

namespace App\Repository\Log;

use App\Entity\Log\LogPublicProjectSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogPublicProjectSearch>
 *
 * @method LogPublicProjectSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogPublicProjectSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogPublicProjectSearch[]    findAll()
 * @method LogPublicProjectSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogPublicProjectSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogPublicProjectSearch::class);
    }
}
