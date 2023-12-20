<?php

namespace App\Repository\Upload;

use App\Entity\Upload\UploadImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UploadImage>
 *
 * @method UploadImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UploadImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UploadImage[]    findAll()
 * @method UploadImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UploadImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadImage::class);
    }

//    /**
//     * @return UploadImage[] Returns an array of UploadImage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UploadImage
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
