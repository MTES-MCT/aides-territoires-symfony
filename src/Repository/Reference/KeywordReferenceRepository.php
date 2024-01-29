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

    public function getQueryBuilder(?array $params = null) : QueryBuilder
    {
        $string = $params['string'] ?? null;

        $qb = $this->createQueryBuilder('kr');

        if ($string) {
            $words = str_getcsv($string, ' ', '"');
            if (is_array($words) && count($words) > 1) {
                $qb->andWhere('kr.name IN (:words)')
                    ->setParameter('words', $words)
                    ;
            }
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
       
        $sqlParams = array(
            'searchText' => $searchText
        );
        $result = $stmt->executeQuery($sqlParams);

        return $result->fetchAllAssociative();
	}

}
