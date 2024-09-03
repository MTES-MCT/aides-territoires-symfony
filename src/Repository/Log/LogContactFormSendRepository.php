<?php

namespace App\Repository\Log;

use App\Entity\Log\LogContactFormSend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogContactFormSend>
 *
 * @method LogContactFormSend|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogContactFormSend|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogContactFormSend[]    findAll()
 * @method LogContactFormSend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogContactFormSendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogContactFormSend::class);
    }
}
