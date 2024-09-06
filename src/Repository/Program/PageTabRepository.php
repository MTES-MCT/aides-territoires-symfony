<?php

namespace App\Repository\Program;

use App\Entity\Program\PageTab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageTab>
 *
 * @method PageTab|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageTab|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageTab[]    findAll()
 * @method PageTab[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageTabRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageTab::class);
    }
}
