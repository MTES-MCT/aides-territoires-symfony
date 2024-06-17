<?php

namespace App\Repository\Log;

use App\Entity\Log\LogUserLogin;
use App\Entity\Organization\OrganizationType;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUserLogin>
 *
 * @method LogUserLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogUserLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogUserLogin[]    findAll()
 * @method LogUserLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogUserLoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUserLogin::class);
    }

    public function getUniqueLoginsByYear(): array
    {
        return
            $this->createQueryBuilder('l')
            ->select('
            EXTRACT(YEAR FROM l.dateCreate) AS year,
            COUNT(DISTINCT l.user) AS unique_users
            ')
            ->groupBy('year')
            ->orderBy('year', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUniqueLoginsByQuarters(): array
    {
        return
            $this->createQueryBuilder('l')
            ->select('
            EXTRACT(YEAR FROM l.dateCreate) AS year,
            EXTRACT(QUARTER FROM l.dateCreate) AS quarter,
            COUNT(DISTINCT l.user) AS unique_users
            ')
            ->groupBy('year')
            ->addGroupBy('quarter')
            ->orderBy('year', 'ASC')
            ->addOrderBy('quarter', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUniqueLoginsByMonth(): array
    {
        return
            $this->createQueryBuilder('l')
            ->select('
            EXTRACT(YEAR FROM l.dateCreate) AS year,
            EXTRACT(MONTH FROM l.dateCreate) AS month,
            COUNT(DISTINCT l.user) AS unique_users
            ')
            ->groupBy('year')
            ->addGroupBy('month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('month', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUniqueLoginsByWeek(): array
    {
        return
            $this->createQueryBuilder('l')
            ->select('
            EXTRACT(YEAR FROM l.dateCreate) AS year,
            EXTRACT(WEEK FROM l.dateCreate) AS week,
            EXTRACT(MONTH FROM l.dateCreate) AS month,
            COUNT(DISTINCT l.user) AS unique_users
            ')
            ->groupBy('year')
            ->addGroupBy('week')
            ->addGroupBy('month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('week', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countUsersLoggedAtLeastOnce(): int
    {
        return
        $this->createQueryBuilder('l')
        ->select('COUNT(DISTINCT l.user)')
        ->getQuery()
        ->getSingleScalarResult()
        ;
    }
    public function countUsersLoggedOnce(): int
    {
        $sql = '
        SELECT 
            COUNT(*) AS nb
        FROM (
            SELECT 
                user_id
            FROM 
                log_user_login
            GROUP BY 
                user_id
            HAVING 
                COUNT(*) = 1
        ) AS users_once;
        ';
        // lance la requete sql
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $results = $result->fetchAllAssociative();
        return (isset($results[0]) && isset($results[0]['nb'])) ? $results[0]['nb'] : 0;
    }
    public function countCustom(?array $params = null): int
    {
        $distinctUser = $params['distinctUser'] ?? null;
        $qb = $this->getQueryBuilder($params);
        if ($distinctUser) {
            $qb->select('COUNT(DISTINCT(l.user))');
        } else {
            $qb->select('COUNT(l.id)');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $user = $params['user'] ?? null;
        $limit = $params['limit'] ?? null;
        $action = $params['action'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $month = $params['month'] ?? null;
        $isCommune = $params['isCommune'] ?? null;
        $isEpci = $params['isEpci'] ?? null;
        $excludeAdmins = $params['excludeAdmins'] ?? null;

        $qb = $this->createQueryBuilder('l');

        if ($excludeAdmins === true) {
            $qb
            ->innerJoin('l.user', 'userForRole')
            ->andWhere('userForRole.roles NOT LIKE :roleAdmin')
            ->setParameter('roleAdmin', '%'.User::ROLE_ADMIN.'%')
            ;
        }

        if ($isCommune) {
            $qb
                ->innerJoin('l.user', 'u')
                ->innerJoin('u.organizations', 'organizations')
                ->innerJoin('organizations.organizationType', 'organizationType')
                ->andWhere('organizationType.slug = :slugCommune')
                ->setParameter('slugCommune', OrganizationType::SLUG_COMMUNE)
                ;
        }

        if ($isEpci) {
            $qb
                ->innerJoin('l.user', 'u')
                ->innerJoin('u.organizations', 'organizations')
                ->innerJoin('organizations.organizationType', 'organizationType')
                ->andWhere('organizationType.slug = :slugCommune')
                ->setParameter('slugCommune', OrganizationType::SLUG_EPCI)
                ;
        }

        if ($month instanceof \DateTime) {
            $qb->andWhere('DATE_FORMAT(l.dateCreate, \'%Y-%m\') = :month')
                ->setParameter('month', $month->format('Y-m'))
                ;
        }
        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin)
                ;
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('l.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax)
                ;
        }

        if ($user !== null) {
            $qb
                ->andWhere('l.user = :user')
                ->setParameter('user', $user)
                ;
        }
    
        if ($action !== null) {
            $qb
                ->andWhere('l.action = :action')
                ->setParameter('action', $action)
                ;
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
}
