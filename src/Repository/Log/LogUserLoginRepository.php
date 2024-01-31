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
