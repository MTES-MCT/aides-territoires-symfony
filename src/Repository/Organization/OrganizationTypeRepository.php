<?php

namespace App\Repository\Organization;

use App\Entity\Organization\OrganizationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizationType>
 *
 * @method OrganizationType|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrganizationType|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrganizationType[]    findAll()
 * @method OrganizationType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationType::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ot.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return string[]
     */
    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'ot.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('ot.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, OrganizationType>
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

        $qb = $this->createQueryBuilder('ot');

        if (is_array($ids) && !empty($ids)) {
            $qb
                ->andWhere('ot.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if (is_array($slugs) && !empty($slugs)) {
            $qb
                ->andWhere('ot.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
