<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerGroup>
 *
 * @method BackerGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerGroup[]    findAll()
 * @method BackerGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerGroup::class);
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('bg');

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
        }

        
        return $qb;
    }
}
