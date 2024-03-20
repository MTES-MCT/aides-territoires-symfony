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

    public function findFromString(string $string): array
    {
        $qb = $this->getQueryBuilder(['string' => $string]);
        return $qb->getQuery()->getResult();
    }

    public function findCustom(?array $params = null) : array
    {
        $qb = $this->getQueryBuilder($params);
        return $qb->getQuery()->getResult();
        
    }

    public function getQueryBuilder(?array $params = null) : QueryBuilder
    {
        $string = $params['string'] ?? null;
        $names = $params['names'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $onlyParent = $params['onlyParent'] ?? false;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        
        $qb = $this->createQueryBuilder('kr');

        if ($nameLike !== null) {
            $qb->andWhere('kr.name LIKE :nameLike')
                ->setParameter('nameLike', '%'.$nameLike.'%')
                ;
        }

        if ($onlyParent) {
            $qb->andWhere('kr.parent = kr');
        }

        if (is_array($names) && count($names) > 0) {
            $qb->andWhere('kr.name IN (:names)')
                ->setParameter('names', $names)
                ;
        }
        if ($string) {
            $words = str_getcsv($string, ' ', '"');
            if (is_array($words) && count($words) > 1) {
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
        $sql="SELECT distinct(k.name),k.intention
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
