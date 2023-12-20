<?php

namespace App\Repository\Blog;

use App\Entity\Blog\BlogPromotionPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogPromotionPost>
 *
 * @method BlogPromotionPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogPromotionPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogPromotionPost[]    findAll()
 * @method BlogPromotionPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogPromotionPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPromotionPost::class);
    }

    public function findPublished(array $params = null) : array {
        $params['status'] = BlogPromotionPost::STATUS_PUBLISHED;
        $params['orderBy'] = ['sort' => 'bpp.timeCreate', 'order' => 'Desc'];

        return $this->getQueryBuilder($params)->getQuery()->getResult();
    }

    public function  getQueryBuilder(array $params = null) : QueryBuilder
    {
        $status = $params['status'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;

        $qb = $this->createQueryBuilder('bpp');
        
        if ($status !== null) {
            $qb
            ->andWhere('bpp.status = :status')
            ->setParameter('status', $status)
            ;
        }

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
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
        $qb->update(BlogPromotionPost::class, 'uu');
        $qb->set('uu.id', 'uu.id + '.$maxOldId)
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // on update les ids avec le oldId
        $qb = $this->createQueryBuilder('u');
        $qb->update(BlogPromotionPost::class, 'uu');
        $qb->set('uu.id', 'uu.oldId')
        ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // met à jour l'auto increment
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.id), 0) + 1 as maxId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxId = $resultMax[0]['maxId'] ?? 1;

        $table = $this->getEntityManager()->getClassMetadata(BlogPromotionPost::class)->getTableName();
        $sql = 'ALTER TABLE '.$table.' AUTO_INCREMENT = '.$maxId;

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();

        return true;
    }
}
