<?php

namespace App\Repository\Log;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidApplicationUrlClick;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidApplicationUrlClick>
 *
 * @method LogAidApplicationUrlClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidApplicationUrlClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidApplicationUrlClick[]    findAll()
 * @method LogAidApplicationUrlClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidApplicationUrlClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidApplicationUrlClick::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, aidId: integer}>
     */
    public function countFormGroup(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('COUNT(laau.id) as nb');
        $qb->innerJoin('laau.aid', 'aid');
        $qb->addSelect('aid.id as aidId');
        $qb->groupBy('aid.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, id: integer, name: string}>
     */
    public function countTopAidOnPeriod(?array $params = null): array
    {
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(laau.id), 0) AS nb, aid.id AS id, aid.name AS name')
            ->innerJoin('laau.aid', 'aid')
            ->groupBy('aid.id')
            ->orderBy('nb', 'DESC')
        ;

        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, dateDay: string}>
     */
    public function countByDay(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(laau.id), 0) AS nb')
            ->addSelect('DATE_FORMAT(laau.dateCreate, \'%Y-%m-%d\') as dateDay')
            ->groupBy('laau.dateCreate')
            ->orderBy('laau.dateCreate', 'ASC')
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre de vues par source
     *
     * @param array<string, mixed>|null $params
     * @return array<int, array{nb: integer, source: string}>
     */
    public function countBySource(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('COUNT(laau.id) as nb, laau.source');
        $qb->groupBy('laau.source');
        $qb->orderBy('nb', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return integer
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(laau.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $dateCreate = $params['dateCreate'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;
        $aidIds = $params['aidIds'] ?? null;
        $sources = $params['sources'] ?? null;

        $qb = $this->createQueryBuilder('laau');

        if (is_array($sources) && !empty($sources)) {
            $qb
                ->andWhere('laau.source IN (:sources)')
                ->setParameter('sources', $sources)
            ;
        }
        
        if ($author instanceof User && $author->getId()) {
            $qb
                ->innerJoin('laau.aid', 'aid')
                ->andWhere('laau.author = :author')
                ->setParameter('author', $author)
            ;
        }

        if ($aid instanceof Aid && $aid->getId()) {
            $qb
                ->andWhere('laau.aid = :aid')
                ->setParameter('aid', $aid)
            ;
        }

        if (is_array($aidIds) && !empty($aidIds)) {
            $qb
                ->andWhere('laau.aid IN (:aidIds)')
                ->setParameter('aidIds', $aidIds)
            ;
        }

        if ($dateCreate instanceof \DateTime) {
            $qb
                ->andWhere('laau.dateCreate = :dateCreate')
                ->setParameter('dateCreate', $dateCreate)
            ;
        }

        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('laau.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('laau.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb;
    }
}
