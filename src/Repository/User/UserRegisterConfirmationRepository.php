<?php

namespace App\Repository\User;

use App\Entity\User\UserRegisterConfirmation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRegisterConfirmation>
 *
 * @method UserRegisterConfirmation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRegisterConfirmation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRegisterConfirmation[]    findAll()
 * @method UserRegisterConfirmation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRegisterConfirmationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRegisterConfirmation::class);
    }
}
