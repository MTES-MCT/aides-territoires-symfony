<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ProjectReferenceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectReferenceCategory>
 *
 * @method ProjectReferenceCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReferenceCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReferenceCategory[]    findAll()
 * @method ProjectReferenceCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectReferenceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReferenceCategory::class);
    }

//    /**
//     * @return ProjectReferenceCategory[] Returns an array of ProjectReferenceCategory objects
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

//    public function findOneBySomeField($value): ?ProjectReferenceCategory
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
