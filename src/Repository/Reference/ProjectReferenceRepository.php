<?php

namespace App\Repository\Reference;

use App\Entity\Reference\ProjectReference;
use App\Entity\Reference\ProjectReferenceCategory;
use App\Service\Various\StringService;
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
    public function __construct(
        ManagerRegistry $registry,
        private StringService $stringService
    ) {
        parent::__construct($registry, ProjectReference::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, string>
     */
    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'pr.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('pr.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        return (int) $qb->select('COUNT(pr.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, ProjectReference>
     */
    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $projectReferenceCategory = $params['projectReferenceCategory'] ?? null;
        $nameMatchAgainst = $params['nameMatchAgainst'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $excludes = $params['excludes'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $addOrderBy = $params['addOrderBy'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('pr');

        if ($projectReferenceCategory instanceof ProjectReferenceCategory && $projectReferenceCategory->getId()) {
            $qb
                ->andWhere('pr.projectReferenceCategory = :projectReferenceCategory')
                ->setParameter('projectReferenceCategory', $projectReferenceCategory);
        }

        if ($nameMatchAgainst !== null) {
            $nameMatchAgainst = $this->stringService->sanitizeBooleanSearch($nameMatchAgainst);
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
        }

        if ($addOrderBy !== null) {
            foreach ($addOrderBy as $order) {
                $qb->addOrderBy($order['sort'], $order['order']);
            }
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
