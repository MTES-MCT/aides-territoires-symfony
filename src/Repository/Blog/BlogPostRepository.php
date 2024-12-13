<?php

namespace App\Repository\Blog;

use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogPost>
 *
 * @method BlogPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogPost[]    findAll()
 * @method BlogPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPost::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, BlogPost>
     */
    public function getRecents(array $params = null): array
    {
        $params['status'] = BlogPost::STATUS_PUBLISHED;
        $params['limit'] = 2;
        $params['orderBy'] = [
            'sort' => 'bp.datePublished',
            'order' => 'DESC'
        ];
        return $this->findCustom($params);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, BlogPost>
     */
    public function findCustom(array $params = null)
    {
        $qb = $this->getQueryBuilder($params);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(bp.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $blogPostCategory = $params['blogPostCategory'] ?? null;
        $status = $params['status'] ?? null;
        $limit = $params['limit'] ?? null;
        $offset = $params['offset'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('bp');

        if ($blogPostCategory instanceof BlogPostCategory && $blogPostCategory->getId()) {
            $qb
                ->andWhere('bp.blogPostCategory = :blogPostCategory')
                ->setParameter('blogPostCategory', $blogPostCategory)
            ;
        }

        if ($status !== null) {
            $qb
                ->andWhere('bp.status = :status')
                ->setParameter('status', $status)
            ;
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
        }

        return $qb;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return bool
     */
    public function importOldId(array $params = null): bool
    {
        // on recupere l'id max
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.oldId), 0) + 1 as maxOldId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxOldId = $resultMax[0]['maxOldId'] ?? 1;

        // on update les ids actuels pour avoir les futurs id de libre
        $qb = $this->createQueryBuilder('u');
        $qb->update(BlogPost::class, 'uu');
        $qb->set('uu.id', 'uu.id + ' . $maxOldId)
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // on update les ids avec le oldId
        $qb = $this->createQueryBuilder('u');
        $qb->update(BlogPost::class, 'uu');
        $qb->set('uu.id', 'uu.oldId')
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // met à jour l'auto increment
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.id), 0) + 1 as maxId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxId = $resultMax[0]['maxId'] ?? 1;

        $table = $this->getEntityManager()->getClassMetadata(BlogPost::class)->getTableName();
        $sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT = ' . $maxId;

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();

        return true;
    }
}
