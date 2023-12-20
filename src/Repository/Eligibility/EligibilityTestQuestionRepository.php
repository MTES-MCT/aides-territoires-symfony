<?php

namespace App\Repository\Eligibility;

use App\Entity\Eligibility\EligibilityTestQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EligibilityTestQuestion>
 *
 * @method EligibilityTestQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method EligibilityTestQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method EligibilityTestQuestion[]    findAll()
 * @method EligibilityTestQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EligibilityTestQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EligibilityTestQuestion::class);
    }
}
