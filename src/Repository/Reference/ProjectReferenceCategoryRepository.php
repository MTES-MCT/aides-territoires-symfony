<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ProjectReferenceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectReferenceCategory>
 *
 * @method ProjectReferenceCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectReferenceCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectReferenceCategory[]    findAll()
 * @method ProjectReferenceCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectReferenceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReferenceCategory::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(?array $params = null): int
    {
        return $this->getQueryBuilder($params)
            ->select('COUNT(prc.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, ProjectReferenceCategory>
     */
    public function findCustom(?array $params = null): array
    {
        return $this->getQueryBuilder($params)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $nameLike = $params['nameLike'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('prc');

        if ($nameLike !== null) {
            $qb->andWhere('prc.name LIKE :nameLike')
                ->setParameter('nameLike', '%' . $nameLike . '%');
        }

        if ($firstResult !== null) {
            $qb->setFirstResult($firstResult);
        }

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
