<?php

namespace App\Repository\Category;

use App\Entity\Category\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }
    
    public function getQueryBuilder(array $params = null)
    {
        $groupBy = $params['groupBy'] ?? null;
        $ids = $params['ids'] ?? null;

        $qb = $this->createQueryBuilder('c');

        if (is_array($ids)) {
            $qb->andWhere('c.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if ($groupBy !== null) {
            $qb->addGroupBy($groupBy);
        }

        return $qb;
    }
}
