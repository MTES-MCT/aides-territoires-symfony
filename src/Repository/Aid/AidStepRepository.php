<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidStep>
 *
 * @method AidStep|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidStep|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidStep[]    findAll()
 * @method AidStep[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidStep::class);
    }

    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'ast.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('ast.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ast.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $slugs = $params['slugs'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('ast');

        if (is_array($slugs) && count($slugs) > 0) {
            $qb->andWhere('ast.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
