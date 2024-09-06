<?php

namespace App\Repository\Aid;

use App\Entity\Aid\SanctuarizedField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SanctuarizedField>
 *
 * @method SanctuarizedField|null find($id, $lockMode = null, $lockVersion = null)
 * @method SanctuarizedField|null findOneBy(array $criteria, array $orderBy = null)
 * @method SanctuarizedField[]    findAll()
 * @method SanctuarizedField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SanctuarizedFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SanctuarizedField::class);
    }
}
