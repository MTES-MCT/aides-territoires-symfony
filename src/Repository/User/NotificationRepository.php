<?php

namespace App\Repository\User;

use App\Entity\User\Notification;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findToSend(?array $params = null): array
    {
        $params['notRead'] = true;
        $params['notSend'] = true;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $user = $params['user'] ?? null;
        $notRead = $params['notRead'] ?? null;
        $notSend = $params['notSend'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('n');

        if ($user instanceof User && $user->getId()) {
            $qb
                ->andWhere('n.user = :user')
                ->setParameter('user', $user)
            ;
        }

        if ($notRead) {
            $qb
                ->andWhere('n.timeRead IS NULL')
            ;
        }

        if ($notSend) {
            $qb
                ->andWhere('n.timeEmail IS NULL')
            ;
        }

        if ($orderBy !== null) {
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
