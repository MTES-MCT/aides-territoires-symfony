<?php

namespace App\Repository\Backer;

use App\Entity\Backer\BackerAskAssociate;
use App\Entity\Organization\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BackerAskAssociate>
 *
 * @method BackerAskAssociate|null find($id, $lockMode = null, $lockVersion = null)
 * @method BackerAskAssociate|null findOneBy(array $criteria, array $orderBy = null)
 * @method BackerAskAssociate[]    findAll()
 * @method BackerAskAssociate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BackerAskAssociateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackerAskAssociate::class);
    }

    public function findOrganizationRefused(Organization $organization): array
    {
        $params = [
            'organization' => $organization,
            'refused' => true,
        ];
        $qb = $this->getQueryBuilder($params);
        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOrganizationPending(Organization $organization): ?BackerAskAssociate
    {
        $params = [
            'organization' => $organization,
            'accepted' => false,
            'refused' => false,
        ];
        $qb = $this->getQueryBuilder($params);
        return $qb
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $accepted = $params['accepted'] ?? null;
        $refused = $params['refused'] ?? null;
        $organization = $params['organization'] ?? null;

        $qb = $this->createQueryBuilder('baa');

        if ($accepted !== null) {
            $qb
                ->andWhere('baa.accepted = :accepted')
                ->setParameter('accepted', $accepted)
            ;
        }

        if ($refused !== null) {
            $qb
                ->andWhere('baa.refused = :refused')
                ->setParameter('refused', $refused)
            ;
        }

        if ($organization instanceof Organization && $organization->getId()) {
            $qb
                ->andWhere('baa.organization = :organization')
                ->setParameter('organization', $organization)
            ;
        }

        return $qb;
    }
}
