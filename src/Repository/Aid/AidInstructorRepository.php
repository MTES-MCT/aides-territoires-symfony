<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidInstructor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidInstructor>
 *
 * @method AidInstructor|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidInstructor|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidInstructor[]    findAll()
 * @method AidInstructor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidInstructorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidInstructor::class);
    }
}
