<?php

namespace App\Repository\Program;

use App\Entity\Program\Program;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Program>
 *
 * @method Program|null find($id, $lockMode = null, $lockVersion = null)
 * @method Program|null findOneBy(array $criteria, array $orderBy = null)
 * @method Program[]    findAll()
 * @method Program[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Program::class);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(p.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'p.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('p.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function finddSelecteForHome(): array
    {
        $params['hasLogo'] = true;
        $params['isSpotlighted'] = true;
        $params['orderBy'] = [
            'sort' => 'p.timeCreate',
            'order' => 'DESC'
        ];
        $params['limit'] = 3;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $ids = $params['ids'] ?? null;
        $slugs = $params['slugs'] ?? null;
        $hasLogo = $params['hasLogo'] ?? null;
        $isSpotlighted = $params['isSpotlighted'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $limit = $params['limit'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if (is_array($ids) && !empty($ids)) {
            $qb
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if (is_array($slugs) && !empty($slugs)) {
            $qb
                ->andWhere('p.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }
        
        if ($hasLogo === true) {
            $qb
                ->andWhere('p.logo IS NOT NULL');
        }

        if ($isSpotlighted !== null) {
            $qb
                ->andWhere('p.isSpotlighted = :isSpotlighted')
                ->setParameter('isSpotlighted', $isSpotlighted)
            ;
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($limit !== null) {
            $qb
                ->setMaxResults($limit);
        }
        return $qb;
    }
}
