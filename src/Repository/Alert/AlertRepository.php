<?php

namespace App\Repository\Alert;

use App\Entity\Alert\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 *
 * @method Alert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Alert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Alert[]    findAll()
 * @method Alert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function findToSend(array $params = null) : array {
        $today = new \DateTime(date('Y-m-d'));
        $yesterday = new \DateTime(date('Y-m-d', strtotime('-1 day')));
        $lastWeek = new \DateTime(date('Y-m-d', strtotime('-7 day')));

        $params['dailyMinDate'] = $yesterday;
        $params['weeklyMinDate'] = $lastWeek;
        
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function  getQueryBuilder(array $params = null) : QueryBuilder
    {
        $dailyMinDate = $params['dailyMinDate'] ?? null;
        $weeklyMinDate = $params['weeklyMinDate'] ?? null;

        $qb = $this->createQueryBuilder('a');
        
        if ($dailyMinDate instanceof \DateTime && $weeklyMinDate instanceof \DateTime) {
            $qb
                ->andWhere('
                (a.alertFrequency = :daily AND (a.dateLatestAlert <= :dailyMinDate OR a.dateLatestAlert IS NULL))
                OR
                (a.alertFrequency = :weekly AND (a.dateLatestAlert <= :weeklyMinDate OR a.dateLatestAlert IS NULL))
                ')
                ->setParameter('daily', Alert::FREQUENCY_DAILY_SLUG)
                ->setParameter('dailyMinDate', $dailyMinDate)
                ->setParameter('weekly', Alert::FREQUENCY_WEEKLY_SLUG)
                ->setParameter('weeklyMinDate', $weeklyMinDate)
            ;
        }
        return $qb;
    }
}
