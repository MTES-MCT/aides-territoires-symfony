<?php

namespace App\Repository\Category;

use App\Entity\Category\CategoryTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryTheme>
 *
 * @method CategoryTheme|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategoryTheme|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategoryTheme[]    findAll()
 * @method CategoryTheme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryTheme::class);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(ct.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ct');

        return $qb;
    }
}
