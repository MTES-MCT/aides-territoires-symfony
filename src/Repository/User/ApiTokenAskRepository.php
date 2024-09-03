<?php

namespace App\Repository\User;

use App\Entity\User\ApiTokenAsk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiTokenAsk>
 *
 * @method ApiTokenAsk|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiTokenAsk|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiTokenAsk[]    findAll()
 * @method ApiTokenAsk[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiTokenAskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiTokenAsk::class);
    }

    public function countPendingAccept(array $params = null): int
    {
        return $this->countCustom(['pendingAccept' => true]);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(ata.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $pendingAccept = $params['pendingAccept'] ?? false;

        $qb = $this->createQueryBuilder('ata');

        if ($pendingAccept == true) {
            $qb->andWhere('ata.timeAccept IS NULL');
        }

        return $qb;
    }
}
