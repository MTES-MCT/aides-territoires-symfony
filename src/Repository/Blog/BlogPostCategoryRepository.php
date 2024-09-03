<?php

namespace App\Repository\Blog;

use App\Entity\Blog\BlogPostCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlogPostCategory>
 *
 * @method BlogPostCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlogPostCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlogPostCategory[]    findAll()
 * @method BlogPostCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlogPostCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPostCategory::class);
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
        $qb->update(BlogPostCategory::class, 'uu');
        $qb->set('uu.id', 'uu.id + ' . $maxOldId)
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // on update les ids avec le oldId
        $qb = $this->createQueryBuilder('u');
        $qb->update(BlogPostCategory::class, 'uu');
        $qb->set('uu.id', 'uu.oldId')
            ->andWhere('uu.oldId IS NOT NULL');
        $qb->getQuery()->execute();

        // met à jour l'auto increment
        $qbMax = $this->createQueryBuilder('u');
        $qbMax->select('IFNULL(MAX(u.id), 0) + 1 as maxId');
        $resultMax = $qbMax->getQuery()->getResult();
        $maxId = $resultMax[0]['maxId'] ?? 1;

        $table = $this->getEntityManager()->getClassMetadata(BlogPostCategory::class)->getTableName();
        $sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT = ' . $maxId;

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();

        return true;
    }
}
