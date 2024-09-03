<?php

namespace App\Repository\Log;

use App\Entity\Log\LogAidCreatedsFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidCreatedsFolder>
 *
 * @method LogAidCreatedsFolder|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidCreatedsFolder|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidCreatedsFolder[]    findAll()
 * @method LogAidCreatedsFolder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidCreatedsFolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidCreatedsFolder::class);
    }
}
