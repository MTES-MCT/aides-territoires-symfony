<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ProjectReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectReference>
 *
 * @method ProjectReference|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReference|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReference[]    findAll()
 * @method ProjectReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReference::class);
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $nameMatchAgainst = $params['nameMatchAgainst'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $excludes = $params['excludes'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        $addOrderBy = $params['addOrderBy'] ?? null;

        $qb = $this->createQueryBuilder('pr');

        if ($nameMatchAgainst !== null)
        {
            $qb
            ->andWhere('MATCH_AGAINST(pr.name) AGAINST (:nameMatchAgainst IN BOOLEAN MODE) > 5')
            ->setParameter('nameMatchAgainst', $nameMatchAgainst);
            
        }

        if ($nameLike !== null) {
            $qb
            ->andWhere('pr.name LIKE :nameLike')
            ->setParameter('nameLike', '%' . $nameLike . '%');
        }

        if ($excludes !== null) {
            $qb
            ->andWhere('pr NOT IN (:excludes)')
            ->setParameter('excludes', $excludes);
        }

        if ($orderBy !== null) {
            if ($orderBy['sort'] == 'projectReferenceCategory.name') {
                $qb->leftJoin('pr.projectReferenceCategory', 'projectReferenceCategory');
            }
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
            ;
        }

        if ($addOrderBy !== null) {
            foreach ($addOrderBy as $order) {
                $qb->addOrderBy($order['sort'], $order['order']);
            }
        }

        return $qb;
    }
}
