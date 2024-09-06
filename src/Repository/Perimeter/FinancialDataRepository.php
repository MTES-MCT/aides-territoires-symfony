<?php

namespace App\Repository\Perimeter;

use App\Entity\Perimeter\FinancialData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinancialData>
 *
 * @method FinancialData|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancialData|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancialData[]    findAll()
 * @method FinancialData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancialDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancialData::class);
    }
}
