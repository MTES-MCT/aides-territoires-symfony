<?php

namespace App\Repository\Project;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Service\Reference\ReferenceService;
use App\Service\Various\StringService;
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
    public function __construct(
        ManagerRegistry $registry,
        private ReferenceService $referenceService,
        private StringService $stringService
    ) {
        parent::__construct($registry, Project::class);
    }

    public static function publicCriteria($alias = 'p.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'isPublic', true))
        ;
    }

    public static function privateCriteria($alias = 'p.'): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'isPublic', false))
        ;
    }

    public function countByOrganization(Organization $organization): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) AS nb')
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult();
        return $result[0]['nb'] ?? 0;
    }

    public function countByUser(User $user): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) AS nb')
            ->andWhere('p.author = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        return $result[0]['nb'] ?? 0;
    }

    public function findImageToFix(array $params = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('(p.image LIKE :jpg OR p.image LIKE :png)')
            ->setParameter('jpg', '%-jpg')
            ->setParameter('png', '%-png');
        return $qb->getQuery()->getResult();
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

    public function countCustom(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('IFNULL(COUNT(DISTINCT(p.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findPublicProjects(array $params = null): array
    {
        $params['isPublic'] = true;
        $params['status'] = Project::STATUS_PUBLISHED;
        $params['limit'] = $params['limit'] ?? null;
        $params['orderBy'] = [
            'sort' => 'p.dateCreate',
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

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            if ($result instanceof Project) {
                $return[] = $result;
            } elseif (is_array($result) && isset($result[0]) && $result[0] instanceof Project) {
                if (isset($result['score_total'])) {
                    $result[0]->setScoreTotal($result['score_total']);
                }
                $return[] = $result[0];
            }
        }
        return $return;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder // NOSONAR too complex
    {
        $isPublic = $params['isPublic'] ?? null;
        $isTypesSuggested = $params['project_types_suggestion'] ?? null;
        $status = $params['status'] ?? null;
        $step = $params['step'] ?? null;
        $contractLink = $params['contractLink'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $perimeterRadius = $params['perimeterRadius'] ?? null;
        $keywordSynonymlistSearch = $params['keywordSynonymlistSearch'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $limit = $params['limit'] ?? null;
        $search = $params['search'] ?? null;
        $scoreTotalMin = $params['scoreTotalMin'] ?? 30;
        $projectReference = $params['projectReference'] ?? null;
        $radius = $params['radius'] ?? null;
        $exclude = $params['exclude'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $organizations = $params['organizations'] ?? null;
        $organizationType = $params['organizationType'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if ($search !== null) {
            $synonyms = $this->referenceService->getSynonymes($search);
            $originalName = !empty($synonyms['original_name'])
                ? $this->stringService->sanitizeBooleanSearch($synonyms['original_name'])
                : null;
            $intentionsString = !empty($synonyms['intentions_string'])
                ? $this->stringService->sanitizeBooleanSearch($synonyms['intentions_string'])
                : null;

            $objectsString = !empty($synonyms['objects_string'])
                ? $this->stringService->sanitizeBooleanSearch($synonyms['objects_string'])
                : null;

            $simpleWordsString = !empty($synonyms['simple_words_string'])
                ? $this->stringService->sanitizeBooleanSearch($synonyms['simple_words_string'])
                : null;

            if ($originalName) {
                $sqlOriginalName = '
                CASE WHEN (p.name = :originalName) THEN 500 ELSE 0 END
                ';
                $qb->setParameter('originalName', $originalName);
            }

            if ($objectsString) {
                $sqlObjects = '
                CASE WHEN (MATCH_AGAINST(p.name) AGAINST(:objects_string IN BOOLEAN MODE) > 1)
                THEN 90 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:objects_string IN BOOLEAN MODE) > 1)
                THEN 10 ELSE 0 END
                ';

                $objects = str_getcsv($objectsString, ' ', '"');
                if (!empty($objects)) {
                    $sqlObjects .= ' + ';
                }
                for ($i = 0; $i < count($objects); $i++) {
                    $sqlObjects .= '
                        CASE WHEN (p.name LIKE :objects' . $i . ') THEN 30 ELSE 0 END
                    ';
                    if ($i < count($objects) - 1) {
                        $sqlObjects .= ' + ';
                    }
                    $qb->setParameter('objects' . $i, '%' . $objects[$i] . '%');
                }

                $qb->setParameter('objects_string', $objectsString);
            }
            if ($intentionsString && $objectsString) {
                $sqlIntentions = '
                CASE WHEN (MATCH_AGAINST(p.name) AGAINST(:intentions_string IN BOOLEAN MODE) > 1)
                THEN 5 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:intentions_string IN BOOLEAN MODE) > 1)
                THEN 1 ELSE 0 END
                ';
                $qb->setParameter('intentions_string', $intentionsString);
            }

            if ($simpleWordsString) {
                $sqlSimpleWords = '
                CASE WHEN (MATCH_AGAINST(p.name) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1)
                THEN 30 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1)
                THEN 5 ELSE 0 END
                ';
                $qb->setParameter('simple_words_string', $simpleWordsString);
            }

            if ($projectReference instanceof ProjectReference) {
                $sqlProjectReference = '
                CASE
                    WHEN :projectReference = p.projectReference THEN 90
                    ELSE 0
                END
                ';

                $qb
                    ->setParameter('projectReference', $projectReference);
            }

            $sqlTotal = '';
            if ($originalName) {
                $sqlTotal .= $sqlOriginalName;
            }
            if ($objectsString) {
                if ($originalName) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlObjects;
            }
            if ($intentionsString && $objectsString) {
                if ($originalName || $objectsString) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlIntentions;
            }
            if (isset($sqlSimpleWords)) {
                if ($originalName || $objectsString || $intentionsString) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlSimpleWords;
            }

            if (isset($sqlProjectReference)) {
                if (
                    $originalName
                    || $objectsString
                    || $intentionsString
                    || isset($sqlSimpleWords)
                    || isset($sqlCategories)
                    || isset($sqlKeywordReferences)
                ) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlProjectReference;
            }

            if ($sqlTotal !== '') {
                $scoreTotalAvailable = true;
                $qb->addSelect('(' . $sqlTotal . ') as score_total');
                $qb->andHaving('score_total >= ' . $scoreTotalMin);
            }
        }

        if ($organizationType instanceof OrganizationType && $organizationType->getId()) {
            $qb
                ->innerJoin('p.organization', 'organizationForType')
                ->andWhere('organizationForType.organizationType = :organizationType')
                ->setParameter('organizationType', $organizationType)
            ;
        }

        if ($organizations !== null) {
            $qb
                ->andWhere('p.organization IN (:organizations)')
                ->setParameter('organizations', $organizations)
            ;
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb->andWhere('p.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin);
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb->andWhere('p.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax);
        }

        if ($exclude instanceof Project && $exclude->getId()) {
            $qb
                ->andWhere('p != :perimeterExclude')
                ->setParameter('perimeterExclude', $exclude)
            ;
        }
        if (
            $perimeterRadius instanceof Perimeter
            && $perimeterRadius->getLatitude()
            && $perimeterRadius->getLongitude() && $radius !== null
        ) {
            $qb
                ->addSelect('(((ACOS(SIN(:lat * PI() / 180)
                * SIN(perimeterForDistance.latitude * PI() / 180) + COS(:lat * PI() / 180)
                * COS(perimeterForDistance.latitude * PI() / 180)
                * COS((:lng - perimeterForDistance.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1.6093)
                AS dist')
                ->innerJoin('p.organization', 'organizationForDistance')
                ->innerJoin('organizationForDistance.perimeter', 'perimeterForDistance')
                ->setParameter('lat', $perimeterRadius->getLatitude())
                ->setParameter('lng', $perimeterRadius->getLongitude())
                ->having('dist <= :distanceKm')
                ->setParameter('distanceKm', $radius)
                ->orderBy('dist', 'ASC')
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

        if ($perimeter instanceof Perimeter && $perimeter->getId()) {
            $qb
                ->innerJoin('p.organization', 'organization')
                ->innerJoin('organization.perimeter', 'perimeter')
                ->innerJoin('perimeter.perimetersTo', 'perimetersTo')
                ->andWhere('(perimetersTo = :perimeter OR perimeter = :perimeter)')
                ->setParameter('perimeter', $perimeter)
            ;
        }
        if (is_array($keywordSynonymlistSearch) &&  count($keywordSynonymlistSearch) > 0) {
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

        if (isset($scoreTotalAvailable)) {
            $qb->orderBy('score_total', 'DESC');
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
