<?php

namespace App\Repository\Eligibility;

use App\Entity\Eligibility\EligibilityQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EligibilityQuestion>
 *
 * @method EligibilityQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method EligibilityQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method EligibilityQuestion[]    findAll()
 * @method EligibilityQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EligibilityQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EligibilityQuestion::class);
    }
}
