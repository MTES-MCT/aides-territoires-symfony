<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidSuggestedAidProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidSuggestedAidProject>
 *
 * @method AidSuggestedAidProject|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidSuggestedAidProject|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidSuggestedAidProject[]    findAll()
 * @method AidSuggestedAidProject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidSuggestedAidProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidSuggestedAidProject::class);
    }
}
