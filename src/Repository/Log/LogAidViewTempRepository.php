<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidViewTemp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidViewTemp>
 */
class LogAidViewTempRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidViewTemp::class);
    }
}
