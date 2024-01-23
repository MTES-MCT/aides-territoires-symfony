<?php

namespace App\Repository\Project;

use App\Entity\Organization\Organization;
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

    public function countByOrganization(Organization $organization): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) AS nb')
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult()
        ;
        return $result[0]['nb'] ?? 0;
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

    public function countReviewable(?array $params = null): int
    {
        $params['status'] = Project::STATUS_REVIEWABLE;
        try {
            $qb = $this->getQueryBuilder($params);
            $qb->select('IFNULL(COUNT(p.id), 0) AS nb');

            return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
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
        $projects = [];
        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            if ($result instanceof Project) {
                $projects[] = $result;
            } else {
                if (isset($result['dist'])) {
                    $result[0]->setDistance($result['dist']);
                }
                $projects[] = $result[0];
            }
        }
        return $projects;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $isPublic = $params['isPublic'] ?? null;
        $isTypesSuggested = $params['project_types_suggestion'] ?? null;
        $status = $params['status'] ?? null;
        $step = $params['step'] ?? null;
        $contractLink = $params['contractLink'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $perimeterRadius = $params['perimeterRadius'] ?? null;
        $keywordSynonymlistSearch = $params['keywordSynonymlistSearch'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
        $limit = $params['limit'] ?? null;
        $intentions_string = $params['intentions_string'] ?? null;
        $objects_string = $params['objects_string'] ?? null;
        $simple_words_string = $params['simple_words_string'] ?? null;
        $radius = $params['radius'] ?? null;
        $exclude = $params['exclude'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if ($exclude instanceof Project && $exclude->getId()) {
            $qb
                ->andWhere('p != :perimeterExclude')
                ->setParameter('perimeterExclude', $exclude)
                ;
        }
        if ($perimeterRadius instanceof Perimeter && $perimeterRadius->getLatitude() && $perimeterRadius->getLongitude() && $radius !== null) {
            $qb
                ->addSelect('(((ACOS(SIN(:lat * PI() / 180) * SIN(perimeterForDistance.latitude * PI() / 180) + COS(:lat * PI() / 180) *
                    COS(perimeterForDistance.latitude * PI() / 180) * COS((:lng - perimeterForDistance.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1.6093) AS dist')
                ->innerJoin('p.organization', 'organizationForDistance')
                ->innerJoin('organizationForDistance.perimeter', 'perimeterForDistance')
                ->setParameter('lat', $perimeterRadius->getLatitude())
                ->setParameter('lng', $perimeterRadius->getLongitude())
                ->having('dist <= :distanceKm')
                ->setParameter('distanceKm', $radius)
                ->orderBy('dist', 'ASC');
            ;
        }

        if ($objects_string !== null && $objects_string !== '') {
            $qb
                ->andWhere('
                MATCH_AGAINST(p.name) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                OR MATCH_AGAINST(p.description) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                ')
                ->setParameter('objects_string', $objects_string)
            ;
        }

        if ($simple_words_string !== null && $objects_string == '') {
            $qb
                ->andWhere('
                MATCH_AGAINST(p.name) AGAINST (:simple_words_string IN BOOLEAN MODE) > 2
                OR MATCH_AGAINST(p.description) AGAINST (:simple_words_string IN BOOLEAN MODE) > 2
                ')
                ->setParameter('simple_words_string', $simple_words_string)
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
