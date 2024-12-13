<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidRecurrence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidRecurrence>
 *
 * @method AidRecurrence|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidRecurrence|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidRecurrence[]    findAll()
 * @method AidRecurrence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidRecurrenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidRecurrence::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ar.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return string[]
     */
    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'ar.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('ar.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, AidRecurrence>
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
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('ar');

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
