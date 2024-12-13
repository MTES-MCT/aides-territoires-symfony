<?php

namespace App\Repository\Log;

use App\Entity\Log\LogEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogEvent>
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

    /**
     *
     * @return integer
     */
    public function getLatestSiteCountAidLives(): int
    {
        $params['category'] = 'aid';
        $params['event'] = 'live_count';
        $params['source'] = 'aides-territoires';
        $params['orderBy'] = [
            'sort' => 'le.dateCreate',
            'order' => 'DESC'
        ];
        $params['maxResults'] = 1;

        $qb = $this->getQueryBuilder($params);

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result->getValue() : 4000;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return integer
     */
    public function countAlertSent(array $params = null): int
    {
        $params['category'] = 'alert';
        $params['event'] = 'sent';

        $qb = $this->getQueryBuilder($params);

        $qb->select('SUM(le.value) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return integer
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(le.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $category = $params['category'] ?? null;
        $event = $params['event'] ?? null;
        $source = $params['source'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('le');

        if ($category !== null) {
            $qb->andWhere('le.category = :category')
                ->setParameter('category', $category)
            ;
        }

        if ($event !== null) {
            $qb->andWhere('le.event = :event')
                ->setParameter('event', $event)
            ;
        }

        if ($source !== null) {
            $qb->andWhere('le.source = :source')
                ->setParameter('source', $source)
            ;
        }

        if ($orderBy !== null) {
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
