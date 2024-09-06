<?php

namespace App\Repository\Log;

use App\Entity\Blog\BlogPost;
use App\Entity\Blog\BlogPostCategory;
use App\Entity\Log\LogBlogPostView;
use App\Entity\Organization\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBlogPostView>
 *
 * @method LogBlogPostView|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBlogPostView|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBlogPostView[]    findAll()
 * @method LogBlogPostView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBlogPostViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBlogPostView::class);
    }

    public function countByDate(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb
            ->select('lbpv.dateCreate, COUNT(lbpv.id) AS nb')
            ->groupBy('lbpv.dateCreate')
        ;
        return $qb->getQuery()->getResult();
    }

    public function findTopCategoriesOfDateRange(?array $params = null): array
    {
        $params['maxResults'] = $params['maxResults'] ?? 10;
        $qb = $this->getQueryBuilder($params);

        $qb
            ->innerJoin('lbpv.blogPost', 'blogPostCount')
            ->innerJoin('blogPostCount.blogPostCategory', 'blogPostCategoryCount')
            ->select('blogPostCategoryCount.id, COUNT(lbpv.id) AS nb')
            ->groupBy('blogPostCategoryCount.id')
            ->orderBy('nb', 'DESC')
        ;
        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            $return[] = [
                'blogPostCategory' => $this->_em->getReference(BlogPostCategory::class, $result['id']),
                'nb' => $result['nb']
            ];
        }
        return $return;
    }

    public function findTopOfDateRange(?array $params = null): array
    {
        $params['maxResults'] = $params['maxResults'] ?? 10;
        $qb = $this->getQueryBuilder($params);

        $qb
            ->innerJoin('lbpv.blogPost', 'blogPostCount')
            ->select('blogPostCount.id, COUNT(lbpv.id) AS nb')
            ->groupBy('blogPostCount.id')
            ->orderBy('nb', 'DESC')
        ;
        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            $return[] = [
                'blogPost' => $this->_em->getReference(BlogPost::class, $result['id']),
                'nb' => $result['nb']
            ];
        }
        return $return;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $dateMin = $params['dateMin'] ?? null;
        $dateMax = $params['dateMax'] ?? null;
        $blogPost = $params['blogPost'] ?? null;
        $organization = $params['organization'] ?? null;
        $maxResults = $params['maxResults'] ?? null;

        $qb = $this->createQueryBuilder('lbpv');

        if ($blogPost instanceof BlogPost && $blogPost->getId()) {
            $qb
                ->innerJoin('lbpv.blogPost', 'blogPost')
                ->andWhere('lbpv.blogPost = :blogPost')
                ->setParameter('blogPost', $blogPost)
            ;
        }

        if ($organization instanceof Organization && $organization->getId()) {
            $qb
                ->innerJoin('lbpv.organization', 'organization')
                ->andWhere('lbpv.organization = :organization')
                ->setParameter('organization', $organization)
            ;
        }

        if ($dateMin instanceof \DateTime) {
            $qb
                ->andWhere('lbpv.dateCreate >= :dateMin')
                ->setParameter('dateMin', $dateMin)
            ;
        }

        if ($dateMax instanceof \DateTime) {
            $qb
                ->andWhere('lbpv.dateCreate <= :dateMax')
                ->setParameter('dateMax', $dateMax)
            ;
        }

        if ($maxResults !== null) {
            $qb
                ->setMaxResults($maxResults);
        }

        return $qb;
    }
}
