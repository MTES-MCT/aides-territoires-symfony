<?php

namespace App\Repository\Directory;

use App\Entity\Directory\Directory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Directory>
 *
 * @method Directory|null find($id, $lockMode = null, $lockVersion = null)
 * @method Directory|null findOneBy(array $criteria, array $orderBy = null)
 * @method Directory[]    findAll()
 * @method Directory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DirectoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Directory::class);
    }
}
