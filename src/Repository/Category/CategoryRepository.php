<?php

namespace App\Repository\Category;

use App\Entity\Category\Category;
use App\Entity\Category\CategoryTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(c.id), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return string[]
     */
    public function getNames(?array $params = null): array
    {
        $params['orderBy'] = ['sort' => 'c.name', 'order' => 'ASC'];
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('c.name');

        $results = $qb->getQuery()->getResult();

        // on met directement le champ name dans le tableau
        return array_column($results, 'name');
    }

    /**
     * @param array<string, mixed> $synonyms
     * @return array<int, Category>
     */
    public function findFromSynonyms(array $synonyms): array
    {
        $originalName =
            (isset($synonyms['original_name'])
            && trim($synonyms['original_name']) !== '')
                ? $synonyms['original_name']
                : null
        ;
        $intentionsString =
            (isset($synonyms['intentions_string'])
            && trim($synonyms['intentions_string']) !== '')
                ? $synonyms['intentions_string']
                : null
        ;
        $objectsString =
            (isset($synonyms['objects_string'])
            && trim($synonyms['objects_string']) !== '')
                ? $synonyms['objects_string']
                : null
        ;
        $simpleWordsString =
            (isset($synonyms['simple_words_string'])
            && trim($synonyms['simple_words_string']) !== '')
                ? $synonyms['simple_words_string']
                : null
        ;

        // on va faire un tableau de mots à rechercher à partir des synonymes
        $words = [$originalName];
        // on prends en priorité l'objectString
        if ($objectsString) {
            $words = str_getcsv($objectsString, ' ', '"');
            // si on a également des intentions, on les ajoute
            if ($intentionsString) {
                $words = array_merge($words, str_getcsv($intentionsString, ' ', '"'));
            }
        } elseif ($simpleWordsString) {
            $words = array_merge($words, str_getcsv($simpleWordsString, ' ', '"'));
        }

        $qb = $this->getQueryBuilder(['words' => $words]);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Category>
     */
    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $groupBy = $params['groupBy'] ?? null;
        $ids = $params['ids'] ?? null;
        $words = $params['words'] ?? null;
        $slugs = $params['slugs'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $categoryTheme = $params['categoryTheme'] ?? null;

        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;

        $qb = $this->createQueryBuilder('c');

        if (is_array($ids) && !empty($ids)) {
            $qb->andWhere('c.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if ($nameLike !== null) {
            $qb->andWhere('c.name LIKE :nameLike')
                ->setParameter('nameLike', $nameLike . '%')
            ;
        }

        if (is_array($words) && !empty($words)) {
            $qb->andWhere('c.name IN (:words)')
                ->setParameter('words', $words)
            ;
        }

        if (is_array($slugs) && !empty($slugs)) {
            $qb->andWhere('c.slug IN (:slugs)')
                ->setParameter('slugs', $slugs)
            ;
        }

        if ($categoryTheme instanceof CategoryTheme && $categoryTheme->getId()) {
            $qb->andWhere('c.categoryTheme = :categoryTheme')
                ->setParameter('categoryTheme', $categoryTheme);
        }

        if ($orderBy !== null) {
            $qb
                ->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($groupBy !== null) {
            $qb->addGroupBy($groupBy);
        }

        return $qb;
    }
}
