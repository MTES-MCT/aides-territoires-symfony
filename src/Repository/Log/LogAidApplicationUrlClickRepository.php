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

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(laau.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;

        $qb = $this->createQueryBuilder('laau');

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
