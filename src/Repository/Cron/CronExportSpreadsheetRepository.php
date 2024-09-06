<?php

namespace App\Repository\Cron;

use App\Entity\Cron\CronExportSpreadsheet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrontExportSpreadsheet>
 *
 * @method CrontExportSpreadsheet|null find($id, $lockMode = null, $lockVersion = null)
 * @method CrontExportSpreadsheet|null findOneBy(array $criteria, array $orderBy = null)
 * @method CrontExportSpreadsheet[]    findAll()
 * @method CrontExportSpreadsheet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CronExportSpreadsheetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronExportSpreadsheet::class);
    }

    public function findOneToExport(?array $params = null): ?CronExportSpreadsheet
    {
        try {
            $params['notEmailed'] = true;
            $params['error'] = false;
            $params['orderBy'] = ['sort' => 'ces.id', 'order' => 'DESC'];
            $qb = $this->getQueryBuilder($params);
            $qb
                ->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $processing = $params['processing'] ?? null;
        $notEmailed = $params['notEmailed'] ?? null;
        $error = $params['error'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('ces');

        if ($processing !== null) {
            $qb
                ->andWhere('ces.processing = :processing')
                ->setParameter('processing', $processing)
            ;
        }

        if ($error !== null) {
            $qb
                ->andWhere('ces.error = :error')
                ->setParameter('error', $error)
            ;
        }

        if ($notEmailed !== null) {
            $qb
                ->andWhere('ces.timeEmail IS NULL');
        }

        if ($orderBy !== null) {
            $qb
                ->orderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }
}
