<?php

namespace App\Repository\Log;

use App\Entity\Aid\Aid;
use App\Entity\Log\LogAidOriginUrlClick;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAidOriginUrlClick>
 *
 * @method LogAidOriginUrlClick|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAidOriginUrlClick|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAidOriginUrlClick[]    findAll()
 * @method LogAidOriginUrlClick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogAidOriginUrlClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogAidOriginUrlClick::class);
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(laouc.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $author = $params['author'] ?? null;
        $aid = $params['aid'] ?? null;

        $qb = $this->createQueryBuilder('laouc');

        if ($author instanceof User && $author->getId()) {
            $qb
                ->innerJoin('laouc.aid', 'aid')
                ->andWhere('laouc.author = :author')
                ->setParameter('author', $author)
                ;
        }
        
        if ($aid instanceof Aid && $aid->getId()) {
            $qb
                ->andWhere('laouc.aid = :aid')
                ->setParameter('aid', $aid)
            ;
        }
        
        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('laouc.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('laouc.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        return $qb;
    }
}
