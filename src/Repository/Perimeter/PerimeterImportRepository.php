<?php

namespace App\Repository\Perimeter;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Perimeter\PerimeterImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerimeterImport>
 *
 * @method PerimeterImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method PerimeterImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method PerimeterImport[]    findAll()
 * @method PerimeterImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PerimeterImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerimeterImport::class);
    }

    public function findNextToImport(array $params = null): ?PerimeterImport
    {
        $params = array_merge($params, [
            'askProcessing' => true,
            'orderBy' => [
                'sort' => 'pi.id',
                'order' => 'DESC'
            ]
        ]);

        return $this->findOneCustom($params);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(pi.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findOneCustom(array $params = null): ?PerimeterImport
    {
        $params = array_merge($params, [
            'maxResults' => 1
        ]);
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $exclude = $params['exclude'] ?? null;
        $askProcessing = $params['askProcessing'] ?? null;
        $importProcessing = $params['importProcessing'] ?? null;
        $isImported = $params['isImported'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('pi');

        if ($exclude instanceof PerimeterImport && $exclude->getId()) {
            $qb
                ->andWhere('pi != :exclude')
                ->setParameter('exclude', $exclude)
            ;
        }

        if ($askProcessing !== null) {
            $qb
                ->andWhere('pi.askProcessing = :askProcessing')
                ->setParameter('askProcessing', $askProcessing)
            ;
        }

        if ($importProcessing !== null) {
            $qb
                ->andWhere('pi.importProcessing = :importProcessing')
                ->setParameter('importProcessing', $importProcessing)
            ;
        }

        if ($isImported !== null) {
            $qb
                ->andWhere('pi.isImported = :isImported')
                ->setParameter('isImported', $isImported)
            ;
        }

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
