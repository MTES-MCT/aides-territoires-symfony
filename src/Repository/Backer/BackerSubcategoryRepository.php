<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerSubcategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerSubcategory>
 *
 * @method BackerSubcategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerSubcategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerSubcategory[]    findAll()
 * @method BackerSubcategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerSubcategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerSubcategory::class);
    }
}
