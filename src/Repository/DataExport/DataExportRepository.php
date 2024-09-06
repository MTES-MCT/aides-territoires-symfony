<?php

namespace App\Repository\DataExport;

use App\Entity\DataExport\DataExport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DataExport>
 *
 * @method DataExport|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataExport|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataExport[]    findAll()
 * @method DataExport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataExport::class);
    }
}
