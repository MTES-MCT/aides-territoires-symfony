<?php

namespace App\Repository\Keyword;

use App\Entity\Keyword\KeywordSynonymlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeywordSynonymlist>
 *
 * @method KeywordSynonymlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeywordSynonymlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeywordSynonymlist[]    findAll()
 * @method KeywordSynonymlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeywordSynonymlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeywordSynonymlist::class);
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(ks.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $nameLike = $params['nameLike'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $limit = $params['limit'] ?? null;

        $qb = $this->createQueryBuilder('ks');

        if ($nameLike !== null) {
            $qb
                ->andWhere('(ks.name LIKE :nameLike OR ks.keywordsList LIKE :nameLike)')
                ->setParameter('nameLike', '%' . $nameLike . '%')
            ;
        }

        if ($orderBy !== null) {
            $qb
                ->orderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($limit !== null) {
            $qb
                ->setMaxResults($limit);
        }

        return $qb;
    }


    public function importOldId(array $params = null): bool
    {
        // on recupere l'id max
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.oldId), 0) + 1 as maxOldId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxOldId = $resultMax[0]['maxOldId'] ?? 1;

        // on update les ids actuels pour avoir les futurs id de libre
        $qb = $this->createQueryBuilder('u');
        $qb->update(KeywordSynonymlist::class, 'uu');
        $qb->set('uu.id', 'uu.id + ' . $maxOldId)
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // on update les ids avec le oldId
        $qb = $this->createQueryBuilder('u');
        $qb->update(KeywordSynonymlist::class, 'uu');
        $qb->set('uu.id', 'uu.oldId')
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // met à jour l'auto increment
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.id), 0) + 1 as maxId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxId = $resultMax[0]['maxId'] ?? 1;

        $table = $this->getEntityManager()->getClassMetadata(KeywordSynonymlist::class)->getTableName();
        $sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT = ' . $maxId;

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();

        return true;
    }
}
