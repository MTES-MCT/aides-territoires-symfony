<?php

namespace App\Repository\Project;

use App\Entity\Project\ProjectLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectLock>
 *
 * @method ProjectLock|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectLock|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectLock[]    findAll()
 * @method ProjectLock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectLock::class);
    }

    //    /**
    //     * @return ProjectLock[] Returns an array of ProjectLock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProjectLock
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
