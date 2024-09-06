<?php

namespace App\Repository\Reference;

use App\Entity\Reference\KeywordReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeywordReference::class);
    }

    public function findArrayOfAllSynonyms(?array $params): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('DISTINCT (kr2.id), kr2.name,  kr2.intention');
        $qb->innerJoin(KeywordReference::class, 'kr2', 'WITH', 'kr2 = kr.parent OR kr.parent = kr2.parent');

        return $qb->getQuery()->getResult();
    }

    public function findFromSynonyms(array $synonyms): array
    {
        $originalName = (isset($synonyms['original_name']) && trim($synonyms['original_name']) !== '')  ? $synonyms['original_name'] : null;
        $intentionsString = (isset($synonyms['intentions_string']) && trim($synonyms['intentions_string']) !== '')  ? $synonyms['intentions_string'] : null;
        $objectsString = (isset($synonyms['objects_string']) && trim($synonyms['objects_string']) !== '')  ? $synonyms['objects_string'] : null;
        $simpleWordsString = (isset($synonyms['simple_words_string']) && trim($synonyms['simple_words_string']) !== '')  ? $synonyms['simple_words_string'] : null;

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

    public function findFromString(string $string): array
    {
        $qb = $this->getQueryBuilder(['string' => $string]);
        return $qb->getQuery()->getResult();
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $string = $params['string'] ?? null;
        $name = $params['name'] ?? null;
        $names = $params['names'] ?? null;
        $excludeds = $params['excludeds'] ?? null;
        $words = $params['words'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $onlyParent = $params['onlyParent'] ?? false;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        $noIntention = $params['noIntention'] ?? null;

        $qb = $this->createQueryBuilder('kr');

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
            if (is_array($words) && !empty($words)) {
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

    public function getAllSynonyms($searchText)
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
