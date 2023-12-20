<?php

namespace App\Repository\Eligibility;

use App\Entity\Eligibility\EligibilityTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EligibilityTest>
 *
 * @method EligibilityTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method EligibilityTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method EligibilityTest[]    findAll()
 * @method EligibilityTest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EligibilityTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EligibilityTest::class);
    }
}
