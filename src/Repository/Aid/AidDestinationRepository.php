<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidDestination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidDestination>
 *
 * @method AidDestination|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidDestination|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidDestination[]    findAll()
 * @method AidDestination[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidDestinationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidDestination::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return string[]
     */
    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'ad.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('ad.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ad.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, AidDestination>
     */
    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $ids = $params['ids'] ?? null;
        $slugs = $params['slugs'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('ad');

        if (is_array($ids) && !empty($ids)) {
            $qb
                ->andWhere('ad.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if (is_array($slugs) && !empty($slugs)) {
            $qb
                ->andWhere('ad.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
