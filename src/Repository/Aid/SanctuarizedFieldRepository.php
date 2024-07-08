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

    //    /**
    //     * @return SanctuarizedField[] Returns an array of SanctuarizedField objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SanctuarizedField
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
