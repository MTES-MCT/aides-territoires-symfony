<?php

namespace App\Repository\Log;

use App\Entity\Log\LogBlogPromotionPostClick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBlogPromotionPostClick>
 *
 * @method LogBlogPromotionPostClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBlogPromotionPostClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBlogPromotionPostClick[]    findAll()
 * @method LogBlogPromotionPostClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBlogPromotionPostClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBlogPromotionPostClick::class);
    }
}
