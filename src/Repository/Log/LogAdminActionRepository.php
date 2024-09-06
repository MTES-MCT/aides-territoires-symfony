<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAdminAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAdminAction>
 *
 * @method LogAdminAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAdminAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAdminAction[]    findAll()
 * @method LogAdminAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAdminActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAdminAction::class);
    }
}
