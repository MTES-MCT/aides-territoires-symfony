<?php

namespace App\Repository\Reference;

use App\Entity\Reference\KeywordReferenceSuggested;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeywordReferenceSuggested>
 *
 * @method KeywordReferenceSuggested|null find($id, $lockMode = null, $lockVersion = null)
 * @method KeywordReferenceSuggested|null findOneBy(array $criteria, array $orderBy = null)
 * @method KeywordReferenceSuggested[]    findAll()
 * @method KeywordReferenceSuggested[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KeywordReferenceSuggestedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeywordReferenceSuggested::class);
    }
}
