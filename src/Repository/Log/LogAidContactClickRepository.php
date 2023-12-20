<?php

namespace App\Repository\Log;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidContactClick;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidContactClick>
 *
 * @method LogAidContactClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidContactClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidContactClick[]    findAll()
 * @method LogAidContactClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidContactClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidContactClick::class);
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(lacc.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;

        $qb = $this->createQueryBuilder('lacc');

        if ($author instanceof User && $author->getId()) {
            $qb
                ->innerJoin('lacc.aid', 'aid')
                ->andWhere('lacc.author = :author')
                ->setParameter('author', $author)
                ;
        }
        
        if ($aid instanceof Aid && $aid->getId()) {
            $qb
                ->andWhere('lacc.aid = :aid')
                ->setParameter('aid', $aid)
            ;
        }
        
        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('lacc.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('lacc.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb;
    }
}
