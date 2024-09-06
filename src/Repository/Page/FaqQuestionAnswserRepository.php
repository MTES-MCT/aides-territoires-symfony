<?php

namespace App\Repository\Page;

use App\Entity\Page\FaqQuestionAnswser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqQuestionAnswser>
 *
 * @method FaqQuestionAnswser|null find($id, $lockMode = null, $lockVersion = null)
 * @method FaqQuestionAnswser|null findOneBy(array $criteria, array $orderBy = null)
 * @method FaqQuestionAnswser[]    findAll()
 * @method FaqQuestionAnswser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FaqQuestionAnswserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqQuestionAnswser::class);
    }
}
