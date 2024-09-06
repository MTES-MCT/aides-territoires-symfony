<?php

namespace App\Repository\Log;

use App\Entity\Log\LogProjectValidatedSearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogProjectValidatedSearch>
 *
 * @method LogProjectValidatedSearch|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogProjectValidatedSearch|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogProjectValidatedSearch[]    findAll()
 * @method LogProjectValidatedSearch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogProjectValidatedSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogProjectValidatedSearch::class);
    }
}
