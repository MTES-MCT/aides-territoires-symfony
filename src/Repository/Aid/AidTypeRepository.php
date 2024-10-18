<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidType>
 *
 * @method AidType|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidType|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidType[]    findAll()
 * @method AidType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidType::class);
    }

    public function getIdsFromAidTypeGroup(AidTypeGroup $aidTypeGroup): array
    {
        $qb = $this->createQueryBuilder('at');

        $qb
            ->select('at.id')
            ->where('at.aidTypeGroup = :aidTypeGroup')
            ->setParameter('aidTypeGroup', $aidTypeGroup);

        $results = $qb->getQuery()->getResult();

        return array_column($results, 'id');
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(at.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'at.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('at.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $slugs = $params['slugs'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('at');

        if (is_array($slugs)) {
            $qb->andWhere('at.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
