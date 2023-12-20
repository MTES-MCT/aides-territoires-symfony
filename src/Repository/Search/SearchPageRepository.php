<?php

namespace App\Repository\Search;

use App\Entity\Search\SearchPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SearchPage>
 *
 * @method SearchPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearchPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearchPage[]    findAll()
 * @method SearchPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchPage::class);
    }

//    /**
//     * @return SearchPage[] Returns an array of SearchPage objects
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

//    public function findOneBySomeField($value): ?SearchPage
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
