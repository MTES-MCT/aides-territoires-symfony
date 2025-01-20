<?php

namespace App\Repository\Reference;

use App\Entity\Reference\KeywordReference;
use App\Service\Various\StringService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeywordReference>
 *
 * @method KeywordReference|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeywordReference|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeywordReference[]    findAll()
 * @method KeywordReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeywordReferenceRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private StringService $stringService
    ) {
        parent::__construct($registry, KeywordReference::class);
    }

    /**
     * @param string[] $keywords
     * @return array<int, KeywordReference>
     */
    public function findFromKewyordsOrOriginalName(array $keywords, string $originalName): array
    {
        $originalName = $this->stringService->sanitizeBooleanSearch($originalName);
        foreach ($keywords as $key => $keyword) {
            $keywords[$key] = $this->stringService->sanitizeBooleanSearch($keyword);
        }

        $qb = $this->createQueryBuilder('kr');
        $qb->orderBy('kr.name', 'ASC');
        $qb->andWhere('kr.name IN (:keywords) OR MATCH_AGAINST(kr.name) AGAINST(:originalName IN BOOLEAN MODE) > 10')
            ->setParameter('keywords', $keywords)
            ->setParameter('originalName', $originalName)
            ->andWhere('kr.active = 1')
        ;

        $sqlOr = '';
        $i = 0;
        foreach ($keywords as $keyword) {
            if ($i > 0) {
                $sqlOr .= ' OR ';
            }
            $sqlOr .= 'kr.name LIKE :keyword' . $i;
            $qb->setParameter('keyword' . $i, '%' . $keyword . '%');
            $i++;
        }
        $qb->andWhere($sqlOr);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, KeywordReference>
     */
    public function findIntentionNames(): array
    {
        $qb = $this->createQueryBuilder('kr');
        $qb->select('kr.name');
        $qb->where('kr.intention = 1');
        $qb->andWhere('kr.active = 1');
        $qb->orderBy('kr.name', 'ASC');

        $result = $qb->getQuery()->getResult();
        // on le tableau à plat pour ne retourner qu'un tableau de string
        return array_map(fn($item) => $item['name'], $result);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, KeywordReference>
     */
    public function findArrayOfAllSynonyms(?array $params): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('DISTINCT (kr2.id), kr2.name,  kr2.intention');
        $qb->andWhere('kr2.active = 1');
        $qb->innerJoin(KeywordReference::class, 'kr2', 'WITH', 'kr2 = kr.parent OR kr.parent = kr2.parent');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed> $synonyms
     * @return array<int, KeywordReference>
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

        $qb = $this->getQueryBuilder(['words' => $words, 'active' => true]);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $string
     * @return array<int, KeywordReference>
     */
    public function findFromString(string $string): array
    {
        $qb = $this->getQueryBuilder(['string' => $string]);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, KeywordReference>
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
    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $string = $params['string'] ?? null;
        $name = $params['name'] ?? null;
        $names = $params['names'] ?? null;
        $excludeds = $params['excludeds'] ?? null;
        $words = $params['words'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $onlyParent = $params['onlyParent'] ?? false;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $noIntention = $params['noIntention'] ?? null;
        $active = $params['active'] ?? null;
        $qb = $this->createQueryBuilder('kr');

        if ($active === true) {
            $qb->andWhere('kr.active = 1');
        }
        if ($noIntention === true) {
            $qb
                ->andWhere('kr.intention = 0');
        }

        if ($name !== null) {
            $qb->andWhere('kr.name = :name')
                ->setParameter('name', $name)
            ;
        }

        if ($nameLike !== null) {
            $qb->andWhere('kr.name LIKE :nameLike')
                ->setParameter('nameLike', '%' . $nameLike . '%')
            ;
        }

        if ($onlyParent) {
            $qb->andWhere('kr.parent = kr');
        }

        if (is_array($names) && !empty($names)) {
            $qb->andWhere('kr.name IN (:names)')
                ->setParameter('names', $names)
            ;
        }

        if (is_array($excludeds) && !empty($excludeds)) {
            $qb->andWhere('kr.name NOT IN (:excludeds)')
                ->setParameter('excludeds', $excludeds)
            ;
        }

        if (is_array($words) && !empty($words)) {
            $qb->andWhere('kr.name IN (:words)')
                ->setParameter('words', $words)
            ;
        }

        if ($string) {
            $words = str_getcsv($string, ' ', '"');
            if (is_array($words)) {
                $qb->andWhere('kr.name IN (:words)')
                    ->setParameter('words', $words)
                ;
            }
        }

        if ($orderBy !== null) {
            if ($orderBy['sort'] == 'projectReferenceCategory.name') {
                $qb->leftJoin('pr.projectReferenceCategory', 'projectReferenceCategory');
            }
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
            ;
        }

        return $qb;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllSynonyms(string $searchText)
    {
        $sql = "SELECT distinct(k.name),k.intention
        from keyword_reference k
        WHERE k.parent_id IN (
            SELECT k2.parent_id
            from keyword_reference k2
            WHERE k2.name = :searchText
        )
        ";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);

        $stmt->bindValue('searchText', $searchText);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }
}
