<?php

namespace App\Repository\Reference;

use App\Entity\Reference\KeywordReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
