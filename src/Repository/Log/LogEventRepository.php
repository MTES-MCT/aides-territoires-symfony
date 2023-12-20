<?php

namespace App\Repository\Log;

use App\Entity\Log\LogEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method LogEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogEvent[]    findAll()
 * @method LogEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEvent::class);
    }

    public function countAlertSent(array $params = null)
    {
        $params['category'] = 'alert';
        $params['event'] = 'sent';

        $qb = $this->getQueryBuilder($params);

        $qb->select('SUM(le.value) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(le.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $category = $params['category'] ?? null;
        $event = $params['event'] ?? null;

        $qb = $this->createQueryBuilder('le');

        if ($category !== null) {
            $qb ->andWhere('le.category = :category')
                ->setParameter('category', $category)
            ;
        }

        if ($event !== null) {
            $qb ->andWhere('le.event = :event')
                ->setParameter('event', $event)
            ;
        }

        return $qb;
    }    
}
