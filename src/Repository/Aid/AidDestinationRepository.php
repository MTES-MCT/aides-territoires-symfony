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

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ad.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ad');

        return $qb;
    }
}
