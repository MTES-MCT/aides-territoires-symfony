<?php

namespace App\Repository\Aid;

use App\Entity\Aid\AidTypeSupport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AidTypeSupport>
 *
 * @method AidTypeSupport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AidTypeSupport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AidTypeSupport[]    findAll()
 * @method AidTypeSupport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidTypeSupportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AidTypeSupport::class);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ats.id)), 0) AS nb');

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
        
        $qb = $this->createQueryBuilder('ats');

        if (is_array($slugs)) {
            $qb->andWhere('ats.slug IN (:slugs)')
                ->setParameter('slugs', $slugs);
        }
        
        return $qb;
    }
}
