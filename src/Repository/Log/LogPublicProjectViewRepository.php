<?php

namespace App\Repository\Log;

use App\Entity\Log\LogPublicProjectView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogPublicProjectView>
 *
 * @method LogPublicProjectView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogPublicProjectView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogPublicProjectView[]    findAll()
 * @method LogPublicProjectView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogPublicProjectViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogPublicProjectView::class);
    }
}
