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

    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(a.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findToSend(array $params = null) : array {
        $today = new \DateTime(date('Y-m-d'));
        $yesterday = new \DateTime(date('Y-m-d', strtotime('-1 day')));
        $lastWeek = new \DateTime(date('Y-m-d', strtotime('-7 day')));

        $params['dailyMinDate'] = $yesterday;
        $params['weeklyMinDate'] = $lastWeek;
        $params['hasQueryString'] = true;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findToSendDaily(array $params = null) : array {
        $yesterday = new \DateTime(date('Y-m-d', strtotime('-1 day')));

        $params['dateLatestAlertMin'] = $yesterday;
        $params['hasQueryString'] = true;
        $params['alertFrequency'] = Alert::FREQUENCY_DAILY_SLUG;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findToSendWeekly(array $params = null) : array {
        $yesterday = new \DateTime(date('Y-m-d', strtotime('-7 day')));

        $params['dateLatestAlertMin'] = $yesterday;
        $params['hasQueryString'] = true;
        $params['alertFrequency'] = Alert::FREQUENCY_WEEKLY_SLUG;
        
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function  getQueryBuilder(array $params = null) : QueryBuilder
    {
        $dailyMinDate = $params['dailyMinDate'] ?? null;
        $weeklyMinDate = $params['weeklyMinDate'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $hasQueryString = $params['hasQueryString'] ?? null;
        $dateLatestAlertMin = $params['dateLatestAlertMin'] ?? null;
        $alertFrequency = $params['alertFrequency'] ?? null;

        $email = $params['email'] ?? null;

        $qb = $this->createQueryBuilder('a');
        
        if ($alertFrequency !== null) {
            $qb
                ->andWhere('a.alertFrequency = :alertFrequency')
                ->setParameter('alertFrequency', $alertFrequency)
                ;
        }

        if ($hasQueryString) {
            $qb
                ->andWhere('a.querystring IS NOT NULL AND a.querystring <> \'\'')
                ;
        }
        if ($email !== null)
        {
            $qb
                ->andWhere('a.email = :email')
                ->setParameter('email', $email)
                ;
        }
        if ($dateCreateMin instanceof \DateTime) {
            $qb
            ->andWhere('a.timeCreate >= :dateCreateMin')
            ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }


        if ($dateCreateMax instanceof \DateTime) {
            $qb
            ->andWhere('a.timeCreate <= :dateCreateMax')
            ->setParameter('dateCreateMax', $dateCreateMax)
            ;
        }

        if ($dateLatestAlertMin instanceof \DateTime) {
            $qb
            ->andWhere('a.dateLatestAlert >= :dateLatestAlertMin OR a.dateLatestAlert IS NULL')
            ->setParameter('dateLatestAlertMin', $dateLatestAlertMin)
            ;
        }

        if ($dailyMinDate instanceof \DateTime && $weeklyMinDate instanceof \DateTime) {
            $qb
                ->andWhere('
                (
                (a.alertFrequency = :daily AND (a.dateLatestAlert <= :dailyMinDate OR a.dateLatestAlert IS NULL))
                OR
                (a.alertFrequency = :weekly AND (a.dateLatestAlert <= :weeklyMinDate OR a.dateLatestAlert IS NULL))
                )
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
