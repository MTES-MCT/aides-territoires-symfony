<?php

namespace App\Repository\User;

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

    public function findAdmins(array $params = null)
    {
        $params['onlyAdmin'] = true;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $onlyAdmin = $params['onlyAdmin'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;

        $qb = $this->createQueryBuilder('u');

        if ($onlyAdmin === true) {
            $qb
            ->andWhere('u.roles LIKE :roleAdmin')
            ->setParameter('roleAdmin', '%'.User::ROLE_ADMIN.'%')
            ;
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb
            ->andWhere('u.dateCreate >= :dateCreateMin')
            ->setParameter('dateCreateMin', $dateCreateMin)
            ;
        }

        return $qb;
    }

    public function countRegisterByDay(?array $params): array
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
