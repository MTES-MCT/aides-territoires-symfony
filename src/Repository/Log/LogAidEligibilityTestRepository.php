<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidEligibilityTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidEligibilityTest>
 *
 * @method LogAidEligibilityTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidEligibilityTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidEligibilityTest[]    findAll()
 * @method LogAidEligibilityTest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidEligibilityTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidEligibilityTest::class);
    }
}
