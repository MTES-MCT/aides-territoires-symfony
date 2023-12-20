<?php

namespace App\Repository\Project;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public static function publicCriteria($alias = 'p.') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'isPublic', true))
        ;
    }

    public static function privateCriteria($alias = 'p.') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'isPublic', false))
        ;
    }

    public function countByUser(User $user): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) AS nb')
            ->andWhere('p.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
        return $result[0]['nb'] ?? 0;
    }

    
    public function findPublicProjects(array $params = null): array
    {
        $params['isPublic'] = true;
        $params['status'] = Project::STATUS_PUBLISHED;
        $params['limit'] = $params['limit'] ?? null;
        $params['orderBy'] = [
            'sort' => 'p.timeCreate',
            'order' => 'DESC'
        ];

        $qb = $this->getQueryBuilder($params);
        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $isPublic = $params['isPublic'] ?? null;
        $isTypesSuggested = $params['project_types_suggestion'] ?? null;
        $status = $params['status'] ?? null;
        $step = $params['step'] ?? null;
        $contractLink = $params['contractLink'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $keywordSynonymlistSearch = $params['keywordSynonymlistSearch'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        $limit = $params['limit'] ?? null;
        $intentions_string = $params['intentions_string'] ?? null;
        $objects_string = $params['objects_string'] ?? null;
        $simple_words_string = $params['simple_words_string'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if ($objects_string !== null) {
            $qb
                ->andWhere('
                MATCH_AGAINST(p.name) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                OR MATCH_AGAINST(p.description) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                ')
                ->setParameter('objects_string', $objects_string)
            ;
        }

        if ($isPublic !== null) {
            $qb
                ->andWhere('p.isPublic = :isPublic') 
                ->setParameter('isPublic', $isPublic)
                ;
        }

        if ($isTypesSuggested !== null) {
            $qb
                ->andWhere('p.projectTypesSuggestion = :isTypesSuggested')
                ->setParameter('isTypesSuggested', $isTypesSuggested)
                ;
        }

        if($perimeter instanceof Perimeter && $perimeter->getId()){
            $qb
                ->innerJoin('p.organization', 'organization')
                ->innerJoin('organization.perimeter','perimeter')
                ->innerJoin('perimeter.perimetersTo','perimetersTo')
                ->andWhere('(perimetersTo = :perimeter OR perimeter = :perimeter)')
                ->setParameter('perimeter', $perimeter)
                ;
        }
        if (is_array($keywordSynonymlistSearch) &&  count($keywordSynonymlistSearch)>0 ) {
            $qb
                ->innerJoin('p.keywordSynonymlists', 'keywordSynonymlists')
                ->andWhere('keywordSynonymlists.id IN (:keywordSynonymlistSearch)')
                ->setParameter('keywordSynonymlistSearch', $keywordSynonymlistSearch)
                ;
        }

        if ($status !== null) {
            $qb
                ->andWhere('p.status = :status')
                ->setParameter('status', $status)
                ;
        }
        if ($contractLink !== null) {
            $qb
                ->andWhere('p.contractLink = :contractLink')
                ->setParameter('contractLink', $contractLink)
                ;
        }
        if ($step !== null) {
            $qb
                ->andWhere('p.step = :step')
                ->setParameter('step', $step)
                ;
        }

        if ($orderBy !== null) {
            $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
}
