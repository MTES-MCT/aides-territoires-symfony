<?php

namespace App\Repository\User;

use App\Entity\Organization\OrganizationType;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getDefaultOrganizationId(?array $params = null)
    {
        $qb = $this->getQueryBuilder($params);
        $qb
            ->innerJoin('u.organizations', 'organizations')
            ->select('organizations.id')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function countProjectWithAids(?array $params = null)
    {
        $qb = $this->getQueryBuilder($params);
        $qb 
            ->select('COUNT(DISTINCT(projects))')
            ->innerJoin('u.projects', 'projects')
            ->innerJoin('projects.aidProjects', 'aidProjects');
            ;
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countCustom(?array $params = null)
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countBeneficiaries(?array $params = null)
    {
        $params['isBeneficiary'] = true;
        $params['isContributor'] = false;

        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countContributors(?array $params = null)
    {
        $params['isBeneficiary'] = false;
        $params['isContributor'] = true;

        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countRegistersWithAid(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))')
            ->innerJoin('u.aids', 'aids');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countRegistersWithProject(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))')
            ->innerJoin('u.projects', 'projects');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countRegisters(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(DISTINCT(u.id))');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countRegistersByWeek(?array $params = null): array
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;


        if (!$dateMin instanceof \DateTime || !$dateMax instanceof \DateTime) {
            return [];
        }

        $qb = $this->getQueryBuilder([
            'dateCreateMin' => $dateMin,
            'dateCreateMax' => $dateMax
        ])
            ->select('DATE_FORMAT(u.dateCreate, \'%Y-%u\') AS week, IFNULL(COUNT(DISTINCT(u.id)), 0) AS nb')
            ->groupBy('week');

        return $qb->getQuery()->getResult();
    }
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findWithUnsentNotification(?array $params = null): array
    {
        $params['hasUnsentNotification'] = true;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }
    public function findAdmins(array $params = null): array
    {
        $params['onlyAdmin'] = true;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findUsersConnectedSinceYesterday(?array $params = null): array
    {
        $params['connectedSinceYesterday'] = true;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $onlyAdmin = $params['onlyAdmin'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $connectedSinceYesterday = $params['connectedSinceYesterday'] ?? null;
        $hasUnsentNotification = $params['hasUnsentNotification'] ?? null;
        $notificationEmailFrequency = $params['notificationEmailFrequency'] ?? null;
        $isBeneficiary = $params['isBeneficiary'] ?? null;
        $isContributor = $params['isContributor'] ?? null;
        $organizationIsCommune = $params['organizationIsCommune'] ?? null;
        $organizationIsEpci = $params['organizationIsEpci'] ?? null;
        $organizationHasAid = $params['organizationHasAid'] ?? null;
        $organizationHasProject = $params['organizationHasProject'] ?? null;
        $email = $params['email'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('u');

        if ($email !== null) {
            $qb
                ->andWhere('u.email = :email')
                ->setParameter('email', $email)
            ;
        }

        if ($organizationHasProject) {
            $qb
                ->innerJoin('u.organizations', 'organizationForProject')
                ->innerJoin('organizationForProject.projects', 'project')
            ;
        }
        if ($organizationHasAid) {
            $qb
                ->innerJoin('u.organizations', 'organizationForAid')
                ->innerJoin('organizationForAid.aids', 'aid')
            ;
        }
        if ($organizationIsCommune) {
            $qb
                ->innerJoin('u.organizations', 'organizationForCommune')
                ->innerJoin('organizationForCommune.organizationType', 'organizationTypeForCommune')
                ->andWhere('organizationTypeForCommune.slug = :slugCommune')
                ->setParameter('slugCommune', OrganizationType::SLUG_COMMUNE)
            ;
        }

        if ($organizationIsEpci) {
            $qb
                ->innerJoin('u.organizations', 'organizationForCommune')
                ->innerJoin('organizationForCommune.organizationType', 'organizationTypeForCommune')
                ->andWhere('organizationTypeForCommune.slug = :slugCommune')
                ->setParameter('slugCommune', OrganizationType::SLUG_EPCI)
            ;
        }

        if ($isBeneficiary !== null) {
            $qb
                ->andWhere('u.isBeneficiary = :isBeneficiary')
                ->setParameter('isBeneficiary', $isBeneficiary)
            ;
        }

        if ($isContributor !== null) {
            $qb
                ->andWhere('u.isContributor = :isContributor')
                ->setParameter('isContributor', $isContributor)
            ;
        }

        if ($onlyAdmin === true) {
            $qb
                ->andWhere('u.roles LIKE :roleAdmin')
                ->setParameter('roleAdmin', '%' . User::ROLE_ADMIN . '%')
            ;
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb
                ->andWhere('u.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }


        if ($dateCreateMax instanceof \DateTime) {
            $qb
                ->andWhere('u.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax)
            ;
        }

        if ($connectedSinceYesterday == true) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('P1D')); // Subtract 1 day from the current date

            $qb
                ->andWhere('u.dateLastLogin IS NOT NULL')
                ->andWhere('u.dateLastLogin >= :yesterday')
                ->setParameter('yesterday', $date)
            ;
        }

        if ($hasUnsentNotification == true) {
            $qb
                ->innerJoin('u.notifications', 'n')
                ->andWhere('n.timeRead IS NULL')
                ->andWhere('n.timeEmail IS NULL')
            ;
        }

        if ($notificationEmailFrequency !== null) {
            $qb
                ->andWhere('u.notificationEmailFrequency = :notificationEmailFrequency')
                ->setParameter('notificationEmailFrequency', $notificationEmailFrequency)
            ;
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }

    public function countRegisterByDay(?array $params = null): array
    {
        $params['dateCreateMin'] = $params['dateCreateMin'] ?? null;
        $qb = $this->getQueryBuilder($params);
        $qb
            ->select('COUNT(u.id) as total, DATE_FORMAT(u.dateCreate, \'%Y-%m-%d\') as day')
            ->groupBy('day')
            ->orderBy('day', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }
}
