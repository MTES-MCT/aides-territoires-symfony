<?php

namespace App\Repository\Aid;

use App\Entity\Aid\Aid;
use App\Entity\Aid\AidDestination;
use App\Entity\Aid\AidRecurrence;
use App\Entity\Aid\AidStep;
use App\Entity\Aid\AidType;
use App\Entity\Aid\AidTypeGroup;
use App\Entity\Backer\Backer;
use App\Entity\Backer\BackerCategory;
use App\Entity\Backer\BackerGroup;
use App\Entity\Category\Category;
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Service\Reference\KeywordReferenceService;
use App\Service\Reference\ReferenceService;
use App\Service\Various\StringService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Aid>
 *
 * @method Aid|null find($id, $lockMode = null, $lockVersion = null)
 * @method Aid|null findOneBy(array $criteria, array $orderBy = null)
 * @method Aid[]    findAll()
 * @method Aid[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AidRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private ReferenceService $referenceService,
        private KeywordReferenceService $keywordReferenceService,
        private StringService $stringService
    ) {
        parent::__construct($registry, Aid::class);
    }

    public function countAidsFromIds(array $ids, ?array $params = null): int
    {
        $aidTypeGroup = $params['aidTypeGroup'] ?? null;

        $qb = $this->createQueryBuilder('a')
            ->select('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $ids);

        if ($aidTypeGroup instanceof AidTypeGroup && $aidTypeGroup->getId()) {
            $qb
                ->innerJoin('a.aidTypes', 'aidType')
                ->andWhere('aidType.aidTypeGroup = :aidTypeGroup')
                ->setParameter('aidTypeGroup', $aidTypeGroup);
        }

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getCategoryThemesIdsFromIds(array $ids): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('DISTINCT(categoryTheme.id) as categoryTheme_id')
            ->innerJoin('a.categories', 'category')
            ->innerJoin('category.categoryTheme', 'categoryTheme')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        $results = $qb->getQuery()->getResult();

        return array_column($results, 'categoryTheme_id');
    }

    public function getScaleCovered($scale, ?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params)
            ->innerJoin('a.perimeter', 'p')
            ->select('DISTINCT(p.id) as id, p.name, p.latitude, p.longitude, p.population')
            ->andWhere('p.scale = :scale')
            ->andWhere('p.isObsolete = false')
            ->setParameter('scale', $scale)
            ->orderBy('p.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public static function liveCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->lte($alias . 'dateStart', $today),
                Criteria::expr()->isNull($alias . 'dateStart')
            ))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->gte($alias . 'dateSubmissionDeadline', $today),
                Criteria::expr()->isNull($alias . 'dateSubmissionDeadline')
            ))
            // ->andWhere(Criteria::expr()->isNull($alias.'genericAid'))
        ;
    }

    public static function showInSearchCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->gte($alias . 'dateSubmissionDeadline', $today),
                Criteria::expr()->isNull($alias . 'dateSubmissionDeadline')
            ))
        ;
    }

    public static function hiddenCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->neq($alias . 'status', Aid::STATUS_PUBLISHED),
                Criteria::expr()->gte($alias . 'dateStart', $today),
                Criteria::expr()->lte($alias . 'dateSubmissionDeadline', $today),
            ))
        ;
    }

    public static function deadlineCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));
        $dateLimit = new \DateTime(date('Y-m-d'));
        $dateLimit->add(new \DateInterval('P' . Aid::APPROACHING_DEADLINE_DELTA . 'D'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->gte($alias . 'dateSubmissionDeadline', $today))
            ->andWhere(Criteria::expr()->lte($alias . 'dateSubmissionDeadline', $dateLimit))
        ;
    }

    public static function expiredCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->lt($alias . 'dateSubmissionDeadline', $today))
        ;
    }

    public static function grantCriteria($alias = ''): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'aidTypes.slug', AidType::SLUG_GRANT))
        ;
    }

    public static function localCriteria($alias = ''): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->neq($alias . 'genericAid', null))
        ;
    }

    public static function genericCriteria($alias = ''): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'isGeneric', true))
        ;
    }

    public static function decliStandardCriteria($alias = ''): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias . 'isGeneric', false))
            ->andWhere(Criteria::expr()->eq($alias . 'genericAid', null))
        ;
    }

    public function countByUser(User $user, array $params = null): int
    {
        $showInSearch = $params['showInSearch'] ?? null;

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) AS nb')
            ->andWhere('a.author = :user')
            ->andWhere('a.status != :delete')
            ->setParameter('user', $user)
            ->setParameter('delete', Aid::STATUS_DELETED);

        if ($showInSearch !== null) {
            $qb->addCriteria(AidRepository::showInSearchCriteria('a.'));
        }
        $result = $qb
            ->getQuery()
            ->getResult();
        return $result[0]['nb'] ?? 0;
    }

    public function findPublishedWithNoBrokenLink(?array $params = null): array
    {
        $params['showInSearch'] = true;
        $params['hasBrokenLink'] = false;
        $params['orderBy'] = [
            'sort' => 'a.timeCreate',
            'order' => 'DESC'
        ];
        $params['maxResults'] = 200;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findRecent(array $params = null): array
    {
        $params['limit'] = $params['limit'] ?? 3;
        $params['orderBy'] = [
            'sort' => 'a.datePublished',
            'order' => 'DESC'
        ];
        $params['showInSearch'] = true;

        $qb = $this->getQueryBuilder($params);

        $results = $qb->getQuery()->getResult();

        $return = [];
        foreach ($results as $result) {
            if ($result instanceof Aid) {
                $return[] = $result;
            } elseif (is_array($result) && isset($result[0]) && $result[0] instanceof Aid) {
                if (isset($result['score_total'])) {
                    $result[0]->setScoreTotal($result['score_total']);
                }
                if (isset($result['score_objects'])) {
                    $result[0]->setScoreObjects($result['score_objects']);
                }
                $return[] = $result[0];
            }
        }
        return $return;
    }

    public function findOneCustom(array $params = null): ?Aid
    {
        $params = array_merge($params, ['limit' => 1]);

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForSitemap(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->select('a.slug');
        return $qb->getQuery()->getResult();
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->addSelect('projectReferences');
        $qb->leftJoin('a.projectReferences', 'projectReferences');

        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            if ($result instanceof Aid) {
                $return[] = $result;
            } elseif (is_array($result) && isset($result[0]) && $result[0] instanceof Aid) {
                if (isset($result['score_total'])) {
                    $result[0]->setScoreTotal($result['score_total']);
                }
                if (isset($result['score_objects'])) {
                    $result[0]->setScoreObjects($result['score_objects']);
                }
                $return[] = $result[0];
            }
        }
        return $return;
    }

    public function findWithKeywords(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb
            ->innerJoin('a.keywords', 'keywords');

        return $qb->getQuery()->getResult();
    }

    public function countLives(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb
            ->select('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb')
            ->innerJoin('a.perimeter', 'p')
            ->addCriteria(self::showInSearchCriteria())
        ;

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countAfterSelect(?array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);
        return count($qb->getQuery()->getResult());
    }

    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        if (isset($params['addSelect'])) {
            $qb->addSelect('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb');
            $qb->addGroupBy('a.id');
        } else {
            $qb->select('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb');
        }

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $id = $params['id'] ?? null;
        $author = $params['author'] ?? null;
        $isLive = $params['isLive'] ?? null;
        $showInSearch = $params['showInSearch'] ?? null;
        $organizationType = $params['organizationType'] ?? null;
        $organizationTypes = $params['organizationTypes'] ?? null;
        $organizationTypeSlugs = $params['organizationTypeSlugs'] ?? null;
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $perimeterFromId = $params['perimeterFromId'] ?? null;
        $perimeterFromIds = $params['perimeterFromIds'] ?? null;
        $perimeterTo = $params['perimeterTo'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $perimeterScales = $params['perimeterScales'] ?? null;
        $limit = $params['limit'] ?? null;
        $backer = $params['backer'] ?? null;
        $backers = $params['backers'] ?? null;
        $backerCategory = $params['backerCategory'] ?? null;
        $backerGroup = $params['backerGroup'] ?? null;
        $isFinancial = $params['isFinancial'] ?? null;
        $isTechnical = $params['isTechnical'] ?? null;
        $aidTypeGroup = $params['aidTypeGroup'] ?? null;
        $aidTypeIds = $params['aidTypeIds'] ?? null;
        $aidType = $params['aidType'] ?? null;
        $aidTypes = $params['aidTypes'] ?? null;
        $categoryIds = $params['categoryIds'] ?? null;
        $categories = $params['categories'] ?? null;
        $categorySlugs = $params['categorySlugs'] ?? null;
        $keywords = $params['keywords'] ?? null;

        $keyword = $params['keyword'] ?? null;
        if ($keyword !== null) {
            $keyword = strip_tags((string) $keyword);
            $synonyms = $this->referenceService->getSynonymes($keyword);
        }

        $applyBefore = $params['applyBefore'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $orderByDateSubmissionDeadline = $params['orderByDateSubmissionDeadline'] ?? null;
        $aidStepIds = $params['aidStepIds'] ?? null;
        $aidSteps = $params['aidSteps'] ?? null;
        $aidStep = $params['aidStep'] ?? null;
        $programIds = $params['programIds'] ?? null;
        $programSlugs = $params['programSlugs'] ?? null;
        $programs = $params['programs'] ?? null;
        $program = $params['program'] ?? null;
        $aidDestinations = $params['aidDestinations'] ?? null;
        $aidDestination = $params['aidDestination'] ?? null;
        $isCharged = $params['isCharged'] ?? null;
        $europeanAid = $params['europeanAid'] ?? null;
        $isCallForProject = $params['isCallForProject'] ?? null;
        $originUrl = $params['originUrl'] ?? null;
        $exclude = $params['exclude'] ?? null;
        $state = $params['state'] ?? null;
        $statusDisplay = $params['statusDisplay'] ?? null;
        $status = $params['status'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;
        $slug = $params['slug'] ?? null;
        $textSearch = $params['textSearch'] ?? null;
        $publishedAfter = $params['publishedAfter'] ?? null;
        $publishedBefore = $params['publishedBefore'] ?? null;
        $aidRecurrence = $params['aidRecurrence'] ?? null;
        $hasBrokenLink = $params['hasBrokenLink'] ?? null;
        $scoreTotalMin = $params['scoreTotalMin'] ?? 60;
        $scoreObjectsMin = $params['scoreObjectsMin'] ?? 30;
        $scoreIntentionMin = $params['scoreIntentionMin'] ?? 1;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $projectReference = $params['projectReference'] ?? null;
        $dateCheckBrokenLinkMax = $params['dateCheckBrokenLinkMax'] ?? null;
        $nameLike = $params['nameLike'] ?? null;
        $hasNoKeywordReference = $params['hasNoKeywordReference'] ?? null;
        $withOldKeywords = $params['withOldKeywords'] ?? null;

        $qb = $this->createQueryBuilder('a');

        if ($id !== null) {
            $qb
                ->andWhere('a.id = :id')
                ->setParameter('id', $id);
        }

        if ($nameLike !== null) {
            $qb
                ->andWhere('a.name LIKE :nameLike')
                ->setParameter('nameLike', '%' . $nameLike . '%')
            ;
        }

        if ($withOldKeywords) {
            $qb
                ->innerJoin('a.keywords', 'oldKeywords');
        }

        if ($dateCheckBrokenLinkMax instanceof \DateTime) {
            $qb
                ->andWhere('a.dateCheckBrokenLink < :dateCheckBrokenLinkMax OR a.dateCheckBrokenLink IS NULL')
                ->setParameter('dateCheckBrokenLinkMax', $dateCheckBrokenLinkMax)
            ;
        }
        if ($dateCreateMin instanceof \DateTime) {
            $qb->andWhere('a.dateCreate >= :dateCreateMin')
                ->setParameter('dateCreateMin', $dateCreateMin);
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb->andWhere('a.dateCreate <= :dateCreateMax')
                ->setParameter('dateCreateMax', $dateCreateMax);
        }

        if ($hasBrokenLink !== null) {
            $qb
                ->andWhere('a.hasBrokenLink = :hasBrokenLink')
                ->setParameter('hasBrokenLink', $hasBrokenLink)
            ;
        }


        if (isset($synonyms)) {
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
                CASE WHEN (a.name = :originalName) THEN 4000 ELSE 0 END +
                CASE WHEN (a.nameInitial = :originalName) THEN 4000 ELSE 0 END
                ';
                $qb->setParameter('originalName', $originalName);
            }

            // Projets référents
            if ($projectReference instanceof ProjectReference) {
                $sqlProjectReference = '
                CASE
                    WHEN :projectReference MEMBER OF a.projectReferences THEN 2000
                    ELSE 0
                END
                ';

                $qb
                    ->setParameter('projectReference', $projectReference);

                if (!$projectReference->getRequiredKeywordReferences()->isEmpty()) {
                    // fait un tableau unique des mots clés requis et de ses synonymes
                    $requiredKeywordReferencesName = [];
                    foreach ($projectReference->getRequiredKeywordReferences() as $keywordReference) {
                        $requiredKeywordReferencesName[] = $keywordReference->getName();
                        foreach ($keywordReference->getKeywordReferences() as $subKeyword) {
                            $requiredKeywordReferencesName[] = $subKeyword->getName();
                        }
                        if (
                            $keywordReference->getParent()
                            && $keywordReference->getParent()->getId() !== $keywordReference->getId()
                        ) {
                            $requiredKeywordReferencesName[] = $keywordReference->getParent()->getName();
                            foreach ($keywordReference->getParent()->getKeywordReferences() as $subKeyword) {
                                $requiredKeywordReferencesName[] = $subKeyword->getName();
                            }
                        }
                    }
                    $requiredKeywordReferencesName = array_unique($requiredKeywordReferencesName);
                    
                    // on ajoute des guillemets si le mot clé contient un espace, ex: "batiment scolaire"
                    $transformedTerms = array_map(function($term) {
                        return strpos($term, ' ') !== false ? '"' . $term . '"' : $term;
                    }, $requiredKeywordReferencesName);
                    

                    // on transforme le tableau en string pour la recherche fulltext
                    $requiredKeywordReferencesNameString = implode(' ', $transformedTerms);

                    $qb->andWhere('
                        MATCH_AGAINST(a.name, a.nameInitial, a.description, a.eligibility, a.projectExamples) AGAINST (:requireKeywordReferencesString IN BOOLEAN MODE) > 0
                        OR :projectReference MEMBER OF a.projectReferences
                    ');
                    $qb->setParameter('requireKeywordReferencesString', $requiredKeywordReferencesNameString);
                }
            }

            // les keywordReferences
            $keywordReferencesSynonyms =
                $this->getEntityManager()->getRepository(KeywordReference::class)->findFromSynonyms($synonyms);
            if (!empty($keywordReferencesSynonyms)) {
                $sqlKeywordReferences = '
                CASE
                    WHEN :keywordReferences MEMBER OF a.keywordReferences THEN 60
                    ELSE 0
                END
                ';
                $qb->setParameter('keywordReferences', $keywordReferencesSynonyms);
            }

            $sqlObjects = '';
            if ($objectsString) {
                if (isset($sqlProjectReference)) {
                    $sqlObjects .= $sqlProjectReference;
                }
                if (isset($sqlKeywordReferences)) {
                    $sqlObjects .= ' + ' . $sqlKeywordReferences;
                }

                if (trim($sqlObjects) !== '') {
                    $sqlObjects .= ' + ';
                }
                $sqlObjects .= '
                CASE WHEN (MATCH_AGAINST(a.name) AGAINST(:objects_string IN BOOLEAN MODE) > 1)
                THEN 60 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.nameInitial) AGAINST(:objects_string IN BOOLEAN MODE) > 1)
                THEN 60 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.description, a.eligibility, a.projectExamples)
                AGAINST(:objects_string IN BOOLEAN MODE) > 1)
                THEN 10 ELSE 0 END
                ';

                $objects = str_getcsv($objectsString, ' ', '"');

                if (!empty($objects)) {
                    $sqlObjects .= ' + ';
                    $sqlRegexpName = '';
                    $sqlRegexpNameInitial = '';
                    $sqlRegexpDescription = '';

                    // on limite le nombre d'objets car le serveur ne tiens pas le coup
                    if (count($objects) > 40) {
                        $objects = array_slice($objects, 0, 40);
                    }
                    for ($i = 0; $i < count($objects); $i++) {
                        $sqlRegexpName .= 'REGEXP(a.name, :objects' . $i . ') = 1';
                        $sqlRegexpNameInitial .= 'REGEXP(a.nameInitial, :objects' . $i . ') = 1';
                        $sqlRegexpDescription .= 'REGEXP(a.description, :objects' . $i . ') = 1';

                        if ($i < count($objects) - 1) {
                            $sqlRegexpName .= ' OR ';
                            $sqlRegexpNameInitial .= ' OR ';
                            $sqlRegexpDescription .= ' OR ';
                        }

                        $qb->setParameter('objects' . $i, '\\b' . $objects[$i] . '\\b');
                    }

                    $sqlObjects .=
                        'CASE WHEN ( ' . $sqlRegexpName . ' ) THEN 60 ELSE 0 END +'
                        . 'CASE WHEN ( ' . $sqlRegexpNameInitial . ' ) THEN 60 ELSE 0 END +'
                        . 'CASE WHEN ( ' . $sqlRegexpDescription . ' ) THEN 60 ELSE 0 END ';
                }

                $qb->setParameter('objects_string', $objectsString);
            }

            if (trim($sqlObjects) !== '') {
                $qb->addSelect('(' . $sqlObjects . ') as score_objects');
                $qb->andHaving('score_objects >= ' . $scoreObjectsMin);
            }

            if ($intentionsString && $objectsString) {
                $sqlIntentions = '
                CASE WHEN (MATCH_AGAINST(a.name, a.nameInitial, a.description, a.eligibility, a.projectExamples)
                AGAINST(:intentions_string IN BOOLEAN MODE) > 0.8)
                THEN 1 ELSE 0 END
                ';
                if (isset($sqlProjectReference) && $sqlProjectReference !== '') {
                    $sqlIntentions .= ' + ' . $sqlProjectReference;
                }

                $qb->addSelect('(' . $sqlIntentions . ') as score_intentions');
                $qb->setParameter('intentions_string', $intentionsString);
                $qb->andHaving('score_intentions >= ' . $scoreIntentionMin);
            }

            if ($simpleWordsString && !$objectsString) {
                $sqlSimpleWords = '
                CASE WHEN (MATCH_AGAINST(a.name) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1)
                THEN 120 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.nameInitial) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1)
                THEN 120 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.description, a.eligibility, a.projectExamples)
                AGAINST(:simple_words_string IN BOOLEAN MODE) > 1)
                THEN 60 ELSE 0 END
                ';
                $qb->setParameter('simple_words_string', $simpleWordsString);
            }

            $sqlTotal = '';
            if ($originalName) {
                $sqlTotal .= $sqlOriginalName;
            }
            if ($intentionsString && $objectsString) {
                if (
                    $originalName
                    || $objectsString
                ) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlIntentions;
            }
            if (isset($sqlSimpleWords)) {
                if (
                    $originalName
                    || $objectsString
                    || $intentionsString
                ) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlSimpleWords;
            }

            if (isset($sqlCategories)) {
                if (
                    $originalName
                    || $objectsString
                    || $intentionsString
                    || isset($sqlSimpleWords)
                ) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlCategories;
            }

            if (isset($sqlKeywordReferences)) {
                if (
                    $originalName
                    || $objectsString
                    || $intentionsString
                    || isset($sqlSimpleWords)
                    || isset($sqlCategories)
                ) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlKeywordReferences;
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

                if ($sqlObjects !== '') {
                    $qb->addSelect('(' . $sqlTotal . ') as score_total');
                    $qb->andHaving('(score_total + score_objects) >= ' . $scoreTotalMin);
                } else {
                    $qb->addSelect('(' . $sqlTotal . ') as score_total');
                    $qb->andHaving('score_total >= ' . $scoreTotalMin);
                }
            }
        }

        // recherche sur projet référent strict
        if (
            $projectReference instanceof ProjectReference
            && $projectReference->getId()
            && !isset($synonyms)
        ) {
            $qb
                ->innerJoin('a.projectReferences', 'projectReferences')
                ->andWhere('projectReferences = :projectReference')
                ->setParameter('projectReference', $projectReference)
            ;
        }

        if ($aidRecurrence instanceof AidRecurrence && $aidRecurrence->getId()) {
            $qb
                ->innerJoin('a.aidRecurrence', 'aidRecurrence')
                ->andWhere('aidRecurrence = :aidRecurrence')
                ->setParameter('aidRecurrence', $aidRecurrence)
            ;
        }

        if ($publishedAfter instanceof \DateTime) {
            $qb
                ->andWhere('a.datePublished >= :publishedAfter')
                ->setParameter('publishedAfter', $publishedAfter)
            ;
        }

        if ($publishedBefore instanceof \DateTime) {
            $qb
                ->andWhere('a.datePublished <= :publishedBefore')
                ->setParameter('publishedBefore', $publishedBefore)
            ;
        }

        if ($textSearch !== null) {
            $texts = explode(',', $textSearch);
            $i = 1;
            foreach ($texts as $text) {
                $qb->leftJoin('a.keywords', 'keywordsForTextSearch');
                $qb->leftJoin('a.categories', 'categoriesForTextSearch');
                $qb->andWhere("
                    a.name LIKE :text$i
                    OR a.nameInitial LIKE :text$i
                    OR a.description LIKE :text$i
                    OR keywordsForTextSearch.name LIKE :text$i
                    OR categoriesForTextSearch.name LIKE :text$i
                ")
                    ->setParameter("text$i", '%' . $text . '%')
                ;
                $i++;
            }
        }
        if ($author instanceof User && $author->getId()) {
            $qb
                ->andWhere('a.author = :author')
                ->setParameter('author', $author)
            ;
        }

        if ($isLive === true) {
            $qb
                ->addCriteria(self::liveCriteria());
        }

        if ($showInSearch === true) {
            $qb
                ->addCriteria(self::showInSearchCriteria());
        }

        if ($slug !== null) {
            $qb
                ->andWhere('a.slug = :slug')
                ->setParameter('slug', $slug)
            ;
        }

        if ($organizationType instanceof OrganizationType && $organizationType->getId()) {
            $qb
                ->innerJoin('a.aidAudiences', 'aidAudiences')
                ->andWhere('aidAudiences IN (:organizationType)')
                ->setParameter('organizationType', $organizationType);
        }

        if ($organizationTypes) {
            $qb
                ->andWhere(':organizationTypes MEMBER OF a.aidAudiences')
                ->setParameter('organizationTypes', $organizationTypes);
        }


        if (is_array($organizationTypeSlugs) && count($organizationTypeSlugs) > 0) {
            $qb
                ->innerJoin('a.aidAudiences', 'aidAudiencesSlug')
                ->andWhere('aidAudiencesSlug.slug IN (:organizationTypeSlugs)')
                ->setParameter('organizationTypeSlugs', $organizationTypeSlugs);
        }


        if ($perimeter instanceof Perimeter && $perimeter->getId()) {
            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->andWhere('perimeter = :perimeter')
                ->setParameter('perimeter', $perimeter)
            ;
        }

        if ($perimeterFrom instanceof Perimeter && $perimeterFrom->getId()) {
            $ids = $this->getEntityManager()->getRepository(Perimeter::class)->getIdPerimetersContainedIn(
                ['perimeter' => $perimeterFrom]
            );
            $ids[] = $perimeterFrom->getId();

            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->andWhere('perimeter.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;
        }

        if ($perimeterFromId !== null) {
            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->innerJoin('perimeter.perimetersFrom', 'perimetersFrom')
                ->andWhere('(perimetersFrom.id = :perimeterFromId OR perimeter.id = :perimeterFromId)')
                ->setParameter('perimeterFromId', (int) $perimeterFromId)
            ;
        }

        if (is_array($perimeterFromIds) && !empty($perimeterFromIds)) {
            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->andWhere('perimeter.id IN (:ids)')
                ->setParameter('ids', $perimeterFromIds)
            ;
        }

        if ($perimeterTo instanceof Perimeter && $perimeterTo->getId()) {
            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->innerJoin('perimeter.perimetersTo', 'perimetersTo')
                ->andWhere('(perimetersTo = :perimeter OR perimeter = :perimeter)')
                ->setParameter('perimeter', $perimeterTo)
            ;
        }

        // echelles de périmetres
        if (is_array($perimeterScales) && isset($perimeterFrom)) {
            $qb
                ->andWhere('perimeter.scale IN (:perimeterScales)')
                ->setParameter('perimeterScales', $perimeterScales)
            ;
        }

        if ($backer instanceof Backer && $backer->getId()) {
            $qb
                ->innerJoin('a.aidFinancers', 'aidFinancers')
                ->innerJoin('aidFinancers.backer', 'backer')
                ->andWhere('backer = :backer')
                ->setParameter('backer', $backer)
            ;

            if ($backerCategory instanceof BackerCategory && $backerCategory->getId()) {
                $qb
                    ->innerJoin('backer.backerGroup', 'backerGroup')
                    ->innerJoin('backerGroup.backerSubCategory', 'backerSubCategory')
                    ->innerJoin('backerSubCategory.backerCategory', 'backerCategory')
                    ->andWhere('backerCategory = :backerCategory')
                    ->setParameter('backerCategory', $backerCategory)
                ;
            }
        }

        if (($backers instanceof ArrayCollection || is_array($backers)) && count($backers) > 0) {
            $qb
                ->innerJoin('a.aidFinancers', 'aidFinancersB')
                ->innerJoin('aidFinancersB.backer', 'backerB')
                ->andWhere('backerB IN (:backers)')
                ->setParameter('backers', $backers)
            ;
        }

        if ($backerGroup instanceof BackerGroup && $backerGroup->getId()) {
            $qb
                ->innerJoin('a.aidFinancers', 'aidFinancersG')
                ->innerJoin('aidFinancersG.backer', 'backerG')
                ->innerJoin('backerG.backerGroup', 'backerGroupG')
                ->andWhere('backerGroupG = :backerGroup')
                ->setParameter('backerGroup', $backerGroup)
            ;
        }

        if ($isFinancial === true) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypes')
                ->innerJoin('aidTypes.aidTypeGroup', 'aidTypeGroup')
                ->andWhere('aidTypeGroup.slug = :slugFinancial')
                ->setParameter('slugFinancial', AidTypeGroup::SLUG_FINANCIAL)
            ;
        }

        if ($isTechnical === true) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypesT')
                ->innerJoin('aidTypesT.aidTypeGroup', 'aidTypeGroupT')
                ->andWhere('aidTypeGroupT.slug = :slugTechnical')
                ->setParameter('slugTechnical', AidTypeGroup::SLUG_TECHNICAL)
            ;
        }

        if (is_array($categoryIds) && count($categoryIds) > 0) {
            $qb
                ->innerJoin('a.categories', 'categories')
                ->andWhere('categories.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds)
            ;
        }

        if ($categories !== null && count($categories) > 0) {
            $qb
                ->innerJoin('a.categories', 'categories')
                ->andWhere('categories IN (:categories)')
                ->setParameter('categories', $categories)
            ;
        }

        if (is_array($categorySlugs) && count($categorySlugs) > 0) {
            $qb
                ->innerJoin('a.categories', 'categories')
                ->andWhere('categories.slug IN (:categorySlugs)')
                ->setParameter('categorySlugs', $categorySlugs)
            ;
        }

        if (is_array($keywords) && !empty($keywords)) {
            $queryKeywords = '(';
            for ($i = 0; $i < count($keywords); $i++) {
                if ($i > 0) {
                    $queryKeywords .= ' OR ';
                }
                $queryKeywords .= '
                    keywords.name LIKE :keyword' . $i . '
                    OR a.name LIKE :keyword' . $i . '
                    OR categoriesKeyword.name LIKE :keyword' . $i . '
                    OR a.description LIKE :keyword' . $i . ' '
                ;
                $qb->setParameter("keyword$i", '%' . $keywords[$i] . '%');
            }
            $queryKeywords .= ')';

            $qb
                ->innerJoin('a.keywords', 'keywords')
                ->innerJoin('a.categories', 'categoriesKeyword')
                ->andWhere($queryKeywords)

            ;
        }

        if ($aidTypeGroup instanceof AidTypeGroup && $aidTypeGroup->getId()) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypesForGroup')
                ->innerJoin('aidTypesForGroup.aidTypeGroup', 'aidTypeGroupForGroup')
                ->andWhere('aidTypeGroupForGroup = :aidTypeGroup')
                ->setParameter('aidTypeGroup', $aidTypeGroup)
            ;
        }

        if ($aidType instanceof AidType && $aidType->getId()) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypesSearch')
                ->andWhere('aidTypesSearch = :aidType')
                ->setParameter('aidType', $aidType)
            ;
        }
        if (is_array($aidTypeIds) && count($aidTypeIds) > 0) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypes')
                ->andWhere('aidTypes.id IN (:aidTypeIds)')
                ->setParameter('aidTypeIds', $aidTypeIds)
            ;
        }

        if ($aidTypes !== null && count($aidTypes) > 0) {
            $qb
                ->innerJoin('a.aidTypes', 'aidTypes')
                ->andWhere('aidTypes IN (:aidTypes)')
                ->setParameter('aidTypes', $aidTypes)
            ;
        }

        if ($applyBefore instanceof \DateTime) {
            $today = new \DateTime(date('Y-m-d'));

            $qb
                ->andWhere('(a.dateSubmissionDeadline <= :applyBefore AND a.dateSubmissionDeadline >= :todayDeadline)')
                ->setParameter('applyBefore', $applyBefore)
                ->setParameter('todayDeadline', $today)
            ;
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($orderBy !== null) {
            if ($orderBy['sort'] == 'score_total') {
                if (isset($scoreTotalAvailable) && $scoreTotalAvailable === true) {
                    $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
                    if ($sqlObjects !== '') {
                        $qb->addOrderBy('score_objects', 'DESC');
                    }
                }
            } else {
                $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
            };
        }

        if ($orderByDateSubmissionDeadline === true) {
            $qb
                ->addSelect('CASE WHEN a.dateSubmissionDeadline IS NULL THEN 1 ELSE 0 END as HIDDEN priority_is_null')
                ->addOrderBy('priority_is_null', 'ASC')
                ->addOrderBy('a.dateSubmissionDeadline', 'ASC')
            ;
        }

        // si aucun tri mais qu'on a le score total
        if (
            $orderBy == null
            && $orderByDateSubmissionDeadline == null
            && isset($scoreTotalAvailable)
            && $scoreTotalAvailable === true
        ) {
            $qb
                ->addOrderBy('score_total', 'DESC');
            if ($sqlObjects !== '') {
                $qb->addOrderBy('score_objects', 'DESC');
            }
        }

        if (is_array($aidStepIds) && count($aidStepIds) > 0) {
            $qb
                ->innerJoin('a.aidSteps', 'aidSteps')
                ->andWhere('aidSteps.id IN (:aidStepIds)')
                ->setParameter('aidStepIds', $aidStepIds)
            ;
        }

        if (($aidSteps instanceof ArrayCollection || is_array($aidSteps)) && count($aidSteps) > 0) {
            $qb
                ->innerJoin('a.aidSteps', 'aidSteps')
                ->andWhere('aidSteps IN (:aidSteps)')
                ->setParameter('aidSteps', $aidSteps)
            ;
        }

        if ($aidStep instanceof AidStep && $aidStep->getId()) {
            $qb
                ->innerJoin('a.aidSteps', 'aidSteps')
                ->andWhere('aidSteps = :aidStep')
                ->setParameter('aidStep', $aidStep)
            ;
        }

        if (is_array($programIds) && count($programIds) > 0) {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs.id IN (:programIds)')
                ->setParameter('programIds', $programIds)
            ;
        }

        if (is_array($programSlugs) && count($programSlugs) > 0) {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs.slug IN (:programSlugs)')
                ->setParameter('programSlugs', $programSlugs)
            ;
        }

        if ($programs !== null && count($programs) > 0) {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs IN (:programs)')
                ->setParameter('programs', $programs)
            ;
        }

        if ($program instanceof Program && $program->getId()) {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs = :program')
                ->setParameter('program', $program)
            ;
        }

        if (
            ($aidDestinations instanceof ArrayCollection || is_array($aidDestinations))
            && count($aidDestinations) > 0
        ) {
            $qb
                ->innerJoin('a.aidDestinations', 'aidDestinations')
                ->andWhere('aidDestinations IN (:aidDestinations)')
                ->setParameter('aidDestinations', $aidDestinations)
            ;
        }

        if ($aidDestination instanceof AidDestination && $aidDestination->getId()) {
            $qb
                ->innerJoin('a.aidDestinations', 'aidDestinations')
                ->andWhere('aidDestinations = :aidDestination')
                ->setParameter('aidDestination', $aidDestination)
            ;
        }

        if ($isCharged !== null) {
            $qb
                ->andWhere('a.isCharged = :isCharged')
                ->setParameter('isCharged', $isCharged)
            ;
        }

        if ($europeanAid !== null) {
            // hbéritage django, on ne filtre pas si on veu tout
            if (strtolower($europeanAid) !== 'all') {
                if ($europeanAid == Aid::SLUG_EUROPEAN) {
                    $qb
                        ->andWhere('a.europeanAid IS NOT NULL');
                } else {
                    $qb
                        ->andWhere('a.europeanAid = :europeanAid')
                        ->setParameter('europeanAid', $europeanAid)
                    ;
                }
            }
        }

        if ($isCallForProject !== null) {
            $qb
                ->andWhere('a.isCallForProject = :isCallForProject')
                ->setParameter('isCallForProject', $isCallForProject)
            ;
        }

        if ($originUrl !== null) {
            $qb
                ->andWhere('a.originUrl = :originUrl')
                ->setParameter('originUrl', $originUrl)
            ;
        }

        if ($exclude instanceof Aid && $exclude->getId()) {
            $qb
                ->andWhere('a != :exclude')
                ->setParameter('exclude', $exclude)
            ;
        }

        if ($state !== null) {
            switch ($state) {
                case 'open':
                    $dateLimit = new \DateTime(date('Y-m-d'));

                    $qb
                        ->andWhere('(a.dateStart <= :dateLimit OR a.dateStart IS NULL)')
                        ->andWhere('(a.dateSubmissionDeadline > :dateLimit OR a.dateSubmissionDeadline IS NULL)')
                        ->setParameter('dateLimit', $dateLimit)
                    ;
                    break;

                case 'deadline':
                    $dateLimit = new \DateTime(date('Y-m-d'));
                    $dateLimit->add(new \DateInterval('P' . Aid::APPROACHING_DEADLINE_DELTA . 'D'));
                    $qb
                        ->andWhere('a.dateSubmissionDeadline <= :dateLimit')
                        ->setParameter('dateLimit', $dateLimit)
                    ;
                    break;

                case 'expired':
                    $dateLimit = new \DateTime(date('Y-m-d'));
                    $qb
                        ->andWhere('a.dateSubmissionDeadline < :dateLimit')
                        ->setParameter('dateLimit', $dateLimit)
                    ;
                    break;
            }
        }

        if ($statusDisplay !== null) {
            switch ($statusDisplay) {
                case 'hidden':
                    $qb
                        ->addCriteria(self::hiddenCriteria('a.'));
                    break;

                case 'live':
                    $qb
                        ->addCriteria(self::liveCriteria('a.'));
                    break;
            }
        }

        if ($hasNoKeywordReference) {
            $qb
                ->leftJoin('a.keywordReferences', 'keywordReferencesNotSet')
                ->andWhere('keywordReferencesNotSet.id IS NULL')
            ;
        }

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($firstResult !== null) {
            $qb->setFirstResult($firstResult);
        }
        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }

        return $qb;
    }
}
