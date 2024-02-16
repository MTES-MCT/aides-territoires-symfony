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
use App\Entity\Keyword\Keyword;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Program\Program;
use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Entity\User\User;
use App\Service\Reference\ReferenceService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
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
        private ReferenceService $referenceService
    )
    {
        parent::__construct($registry, Aid::class);
    }

    public function findAidsBySynonyms(array $params): array
    {
        $submission_deadline=date("Y-m-d");
        $objects_string = $params["objects_string"];
        $intentions_string = $params["intentions_string"];
        $simple_words_string = $params["simple_words_string"];
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $organizationType = $params['organizationType'] ?? null;

        $sql="SELECT sq.id,
            sq.name,
            sq.slug,
            sq.score,
            sq.is_object,
            sq.is_intention
        FROM (
            SELECT aid.id,
            aid.name,
            aid.slug,
                    IF(MATCH(aid.name) AGAINST('$objects_string' IN BOOLEAN MODE), 3, 0) +
                    IF(MATCH(aid.description) AGAINST('$objects_string' IN BOOLEAN MODE), 1, 0) +
                    IF(MATCH(aid.eligibility) AGAINST('$objects_string' IN BOOLEAN MODE), 2, 0) AS score,
                    IF(MATCH(aid.name, aid.description, aid.eligibility) AGAINST('$objects_string' IN BOOLEAN MODE), 1, 0) AS is_object,
                    IF(MATCH(aid.name, aid.description, aid.eligibility) AGAINST('$intentions_string' IN BOOLEAN MODE), 1, 0) AS is_intention
            FROM aid
        ";

        if ($organizationType instanceof OrganizationType) {
            $sql .= "
                INNER JOIN aid_organization_type aot ON aot.aid_id = aid.id AND aot.organization_type_id = :idOrganizationType
            ";
        }

        $sql .= "
            WHERE aid.is_amendment = 0
            AND aid.status = 'published'
            AND (aid.date_submission_deadline >= '$submission_deadline' OR aid.date_submission_deadline IS NULL OR aid.aid_recurrence_id = :id_ongoing )
        ";

        if ($perimeterFrom instanceof Perimeter) {
            $sql .= "
            AND aid.perimeter_id IN (
                SELECT DISTINCT V0.id
                FROM perimeter V0
                WHERE (V0.id = :idPerimeter OR V0.id IN (
                    SELECT DISTINCT U0.perimeter_target
                    FROM perimeter_perimeter U0
                    WHERE U0.perimeter_source = :idPerimeter
                    )
                    OR V0.id IN (
                        SELECT DISTINCT U0.perimeter_source
                        FROM perimeter_perimeter U0
                        WHERE U0.perimeter_target = :idPerimeter
                    )
                )
            )
            ";
        }

        $sql .= "
        ) AS sq
        WHERE sq.score > 0
        ORDER BY sq.score DESC, sq.is_intention DESC
        ";
        

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $sqlParams = [
            'id_ongoing' => 2,
        ];
        if ($organizationType instanceof OrganizationType) {
            $sqlParams['idOrganizationType'] = $organizationType->getId();
        }
        if ($perimeterFrom instanceof Perimeter) {
            $sqlParams['idPerimeter'] = $perimeterFrom->getId();
        }
        // dd($sql, $sqlParams);
        $result = $stmt->executeQuery($sqlParams);

        return $result->fetchAllAssociative();
    }

    public static function liveCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->lte($alias.'dateStart', $today),
                Criteria::expr()->isNull($alias.'dateStart')
            ))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->gte($alias.'dateSubmissionDeadline', $today),
                Criteria::expr()->isNull($alias.'dateSubmissionDeadline')
            ))
            // ->andWhere(Criteria::expr()->isNull($alias.'genericAid'))
        ;
    }

    public static function showInSearchCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->gte($alias.'dateSubmissionDeadline', $today),
                Criteria::expr()->isNull($alias.'dateSubmissionDeadline')
            ))
        ;
    }

    public static function hiddenCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->neq($alias.'status', Aid::STATUS_PUBLISHED),
                Criteria::expr()->gte($alias.'dateStart', $today),
                Criteria::expr()->lte($alias.'dateSubmissionDeadline', $today),
            ))
        ;
    }

    public static function deadlineCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));
        $dateLimit = new \DateTime(date('Y-m-d'));
        $dateLimit->add(new \DateInterval('P'.Aid::APPROACHING_DEADLINE_DELTA.'D'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'status', Aid::STATUS_PUBLISHED))
            ->andWhere(Criteria::expr()->gte($alias.'dateSubmissionDeadline', $today))
            ->andWhere(Criteria::expr()->lte($alias.'dateSubmissionDeadline', $dateLimit))
        ;
    }
    
    public static function expiredCriteria($alias = 'a.'): Criteria
    {
        $today = new \DateTime(date('Y-m-d'));

        return Criteria::create()
            ->andWhere(Criteria::expr()->lt($alias.'dateSubmissionDeadline', $today))
        ;
    }

    public static function grantCriteria($alias = '') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'aidTypes.slug', AidType::SLUG_GRANT))
        ;
    }

    public static function localCriteria($alias = '') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->neq($alias.'genericAid', null))
        ;
    }

    public static function genericCriteria($alias = '') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'isGeneric', true))
        ;
    }
    
    public static function decliStandardCriteria($alias = '') : Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq($alias.'isGeneric', false))
            ->andWhere(Criteria::expr()->eq($alias.'genericAid', null))
        ;
    }

    public function countByUser(User $user, array $params = null): int
    {
        $isLive = $params['isLive'] ?? null;

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id) AS nb')
            ->andWhere('a.author = :user')
            ->andWhere('a.status != :delete')
            ->setParameter('user', $user)
            ->setParameter('delete', Aid::STATUS_DELETED);

        if ($isLive !== null) {
            $qb->addCriteria(AidRepository::liveCriteria('a.'));
        }
        $result = $qb
            ->getQuery()
            ->getResult()
        ;
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
        $params['maxResults'] = 1;
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findRecent(array $params = null): array
    {
        $params['limit'] = $params['limit'] ?? 3;
        $params['orderBy'] = [
            'sort' => 'a.timeCreate',
            'order' => 'DESC'
        ];
        $params['isLive'] = true;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    public function findOneCustom(array $params = null) : ?Aid
    {
        $params = array_merge($params, array('limit' => 1));

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            if ($result instanceof Aid) {
                $return[] = $result;
            } else if (is_array($result) && isset($result[0]) && $result[0] instanceof Aid) {
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
            ->innerJoin('a.keywords', 'keywords')
        ;

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

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);
        
        if (isset($params['addSelect'])) {
            $qb->addSelect('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb');
            $qb->addGroupBy('a.id');
        } else {
            $qb->select('IFNULL(COUNT(DISTINCT(a.id)), 0) AS nb');
        }

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function getQueryBuilder(array $params = null) : QueryBuilder
    {
        $author = $params['author'] ?? null;
        $isLive = $params['isLive'] ?? null;
        $showInSearch = $params['showInSearch'] ?? null;
        $organizationType = $params['organizationType'] ?? null;
        $organizationTypeSlugs = $params['organizationTypeSlugs'] ?? null;
        $perimeterFrom = $params['perimeterFrom'] ?? null;
        $perimeterFromId = $params['perimeterFromId'] ?? null;
        $perimeterTo = $params['perimeterTo'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $perimeterScales = $params['perimeterScales'] ?? null;
        $limit = $params['limit'] ?? null;
        $backer = $params['backer'] ?? null;
        $backers = $params['backers'] ?? null;
        $isFinancial = $params['isFinancial'] ?? null;
        $isTechnical = $params['isTechnical'] ?? null;
        $aidTypeGroup = $params['aidTypeGroup'] ?? null;
        $aidTypeIds = $params['aidTypeIds'] ?? null;
        $aidType = $params['aidType'] ?? null;
        $aidTypes = $params['aidTypes'] ?? null;
        $categoryIds = $params['categoryIds'] ?? null;
        $categories = $params['categories'] ?? null;
        $categorySlugs = $params['categorySlugs'] ?? null;
        $backerCategory = $params['backerCategory'] ?? null;
        $keywords = $params['keywords'] ?? null;

        $keyword = $params['keyword'] ?? null;
        if ($keyword !== null) {
            $synonyms = $this->referenceService->getSynonymes($keyword);
        }

        $applyBefore = $params['applyBefore'] ?? null;
        $orderBy = (isset($params['orderBy']) && isset($params['orderBy']['sort']) && isset($params['orderBy']['order'])) ? $params['orderBy'] : null;
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
        $aidRecurrence = $params['aidRecurrence'] ?? null;
        $inAdmin = $params['inAdmin'] ?? null;
        $hasBrokenLink= $params['hasBrokenLink'] ?? null;
        $scoreTotalMin = $params['scoreTotalMin'] ?? 60;
        $scoreObjectsMin = $params['scoreObjectsMin'] ?? 30;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $projectReference = $params['projectReference'] ?? null;

        $qb = $this->createQueryBuilder('a');

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
            $originalName = (isset($synonyms['original_name']) && $synonyms['original_name'] !== '')  ? $synonyms['original_name'] : null;
            $intentionsString = (isset($synonyms['intentions_string']) && $synonyms['intentions_string'] !== '')  ? $synonyms['intentions_string'] : null;
            $objectsString = (isset($synonyms['objects_string']) && $synonyms['objects_string'] !== '')  ? $synonyms['objects_string'] : null;
            $simpleWordsString = (isset($synonyms['simple_words_string']) && $synonyms['simple_words_string'] !== '')  ? $synonyms['simple_words_string'] : null;
            $oldKeywordsString = '';

            if ($originalName) {
                $sqlOriginalName = '
                CASE WHEN (a.name = :originalName) THEN 500 ELSE 0 END
                ';
                $qb->setParameter('originalName', $originalName);
            }

            if ($objectsString) {
                $oldKeywordsString .= $objectsString;
                $sqlObjects = '
                CASE WHEN (MATCH_AGAINST(a.name) AGAINST(:objects_string IN BOOLEAN MODE) > 1) THEN 90 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.description, a.eligibility, a.projectExamples) AGAINST(:objects_string IN BOOLEAN MODE) > 1) THEN 10 ELSE 0 END 
                ';

                $objects = str_getcsv($objectsString, ' ', '"');
                if (count($objects) > 0) {
                    $sqlObjects .= ' + ';
                }
                for ($i = 0; $i<count($objects); $i++) {

                    $sqlObjects .= '
                        CASE WHEN (a.name LIKE :objects'.$i.') THEN 30 ELSE 0 END
                    ';
                    // $sqlObjects .= '
                    //     CASE WHEN (a.name LIKE :objects'.$i.') THEN 10 ELSE 0 END +
                    //     CASE WHEN (a.description LIKE :objects'.$i.') THEN 2 ELSE 0 END +
                    //     CASE WHEN (a.eligibility LIKE :objects'.$i.') THEN 2 ELSE 0 END
                    // ';
                    if ($i < count($objects) - 1) {
                        $sqlObjects .= ' + ';
                    }
                    $qb->setParameter('objects'.$i, '%'.$objects[$i].'%');
                }

                $qb->addSelect('('.$sqlObjects.') as score_objects');
                $qb->setParameter('objects_string', $objectsString);
                $qb->andHaving('score_objects >= '.$scoreObjectsMin);
            }

            if ($intentionsString && $objectsString) {
                $oldKeywordsString .= ' '.$intentionsString;
                $sqlIntentions = '
                CASE WHEN (MATCH_AGAINST(a.name) AGAINST(:intentions_string IN BOOLEAN MODE) > 1) THEN 5 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.description, a.eligibility, a.projectExamples) AGAINST(:intentions_string IN BOOLEAN MODE) > 1) THEN 1 ELSE 0 END 
                ';
                $qb->setParameter('intentions_string', $intentionsString);
            }

            if ($simpleWordsString) {
                $sqlSimpleWords = '
                CASE WHEN (MATCH_AGAINST(a.name) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1) THEN 30 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(a.description, a.eligibility, a.projectExamples) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1) THEN 5 ELSE 0 END 
                ';

                $simpleWords = str_getcsv($simpleWordsString, ' ', '"');
                // if (count($simpleWords) > 0) {
                //     $sqlSimpleWords .= ' + ';
                // }
                // for ($i = 0; $i<count($simpleWords); $i++) {
                //     $sqlSimpleWords .= '
                //         CASE WHEN (a.name LIKE :simple_word'.$i.') THEN 30 ELSE 0 END +
                //         CASE WHEN (a.description LIKE :simple_word'.$i.') THEN 4 ELSE 0 END +
                //         CASE WHEN (a.eligibility LIKE :simple_word'.$i.') THEN 4 ELSE 0 END +
                //         CASE WHEN (a.projectExamples LIKE :simple_word'.$i.') THEN 4 ELSE 0 END
                //     ';
                //     if ($i < count($simpleWords) - 1) {
                //         $sqlSimpleWords .= ' + ';
                //     }
                //     $qb->setParameter('simple_word'.$i, '%'.$simpleWords[$i].'%');
                // }

                $qb->setParameter('simple_words_string', $simpleWordsString);
            }

            // Les catégories
            if ($objectsString) {
                $qb->leftJoin('a.categories', 'categoriesKeyword');
                $sqlCategories = '
                    CASE WHEN (MATCH_AGAINST(categoriesKeyword.name) AGAINST(:objects_string IN BOOLEAN MODE) > 1) THEN 30 ELSE 0 END 
                ';
                if ($intentionsString) {
                    $sqlCategories .= '
                    +
                    CASE WHEN (MATCH_AGAINST(categoriesKeyword.name) AGAINST(:intentions_string IN BOOLEAN MODE) > 1) THEN 10 ELSE 0 END
                    ';
                }
            } else {
                if ($simpleWordsString) {
                    $qb->leftJoin('a.categories', 'categoriesKeyword');
                    $sqlCategories = '
                        CASE WHEN (MATCH_AGAINST(categoriesKeyword.name) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1) THEN 20 ELSE 0 END 
                    ';
                }
            }

            // les keywordReferences
            if ($objectsString) {
                $keywordReferences = $this->getEntityManager()->getRepository(KeywordReference::class)->findFromString($objectsString);
                if (count($keywordReferences) > 0) {
                    // $qb
                    // ->leftJoin('a.keywordReferences', 'keywordReferencesOs')
                    // ->setParameter('keywordReferences', $keywordReferences)
                    // ;
                    // $sqlKeywordReferences = '
                    //     CASE WHEN (:keywordReferences IN (keywordReferencesOs)) THEN 60 ELSE 0 END 
                    // ';
                    $sqlKeywordReferences = '
                    CASE 
                        WHEN :keywordReferences MEMBER OF a.keywordReferences THEN 60 
                        ELSE 0 
                    END
                    ';
                $qb->setParameter('keywordReferences', $keywordReferences)
                ;
                    if ($intentionsString) {
                        $keywordReferences = $this->getEntityManager()->getRepository(KeywordReference::class)->findFromString($intentionsString);
                        if (count($keywordReferences) > 0) {
                            $sqlKeywordReferences .= '
                            +
                            CASE 
                                WHEN :keywordReferences MEMBER OF a.keywordReferences THEN 20 
                                ELSE 0 
                            END
                            ';
                        }
                    }
                }
            } else {
                if ($simpleWordsString) {
                    $keywordReferences = $this->getEntityManager()->getRepository(KeywordReference::class)->findFromString($simpleWordsString);
                    if (count($keywordReferences) > 0) {
                        $sqlKeywordReferences = '
                        CASE 
                            WHEN :keywordReferences MEMBER OF a.keywordReferences THEN 20 
                            ELSE 0 
                        END
                        ';
                    }
                    $qb
                    ->leftJoin('a.keywordReferences', 'keywordReferences')
                    ->setParameter('keywordReferences', $keywordReferences)
                    ;
                }
            }

            if ($projectReference instanceof ProjectReference) {
                $sqlProjectReference = '
                CASE 
                    WHEN :projectReference MEMBER OF a.projectReferences THEN 90 
                    ELSE 0 
                END
                ';

                $qb
                ->setParameter('projectReference', $projectReference)
                ;
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

            if (isset($sqlCategories)) {
                if ($originalName || $objectsString || $intentionsString || isset($sqlSimpleWords)) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlCategories;
            }

            if (isset($sqlKeywordReferences)) {
                if ($originalName || $objectsString || $intentionsString || isset($sqlSimpleWords) || isset($sqlCategories)) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlKeywordReferences;
            }

            if (isset($sqlProjectReference)) {
                if ($originalName || $objectsString || $intentionsString || isset($sqlSimpleWords) || isset($sqlCategories) || isset($sqlKeywordReferences)) {
                    $sqlTotal .= ' + ';
                }
                $sqlTotal .= $sqlProjectReference;
            }

            if ($sqlTotal !== '') {
                $qb->addSelect('('.$sqlTotal.') as score_total');
                $qb->andHaving('score_total >= '.$scoreTotalMin);
                $qb->orderBy('score_total', 'DESC');
            }
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

        if ($textSearch !== null) {
            $texts = explode(',', $textSearch); 
            $i=1;
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
                ->setParameter("text$i", '%'.$text.'%');
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
            // $qb
            //     ->innerJoin('a.perimeter', 'perimeter')
            //     ->innerJoin('perimeter.perimetersFrom', 'perimetersFrom')
            //     ->andWhere('(perimetersFrom = :perimeter OR perimeter = :perimeter)')
            //     ->setParameter('perimeter', $perimeterFrom)
            //     ;
            $ids = $this->getEntityManager()->getRepository(Perimeter::class)->getIdPerimetersContainedIn(array('perimeter' => $perimeterFrom));
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

        if ($perimeterTo instanceof Perimeter && $perimeterTo->getId()) {
            $qb
                ->innerJoin('a.perimeter', 'perimeter')
                ->innerJoin('perimeter.perimetersTo', 'perimetersTo')
                ->andWhere('(perimetersTo = :perimeter OR perimeter = :perimeter)')
                ->setParameter('perimeter', $perimeterTo)
                ;
        }

        // GESTION DES AID GENERICS / DECLINAISONS
        // We should never have both the generic aid and its local version
        // together on search results.
        // Which one should be removed from the result ? It depends...
        // We consider the scale perimeter associated to the local aid.
        // - When searching on a wider area than the local aid's perimeter,
        //     then we display the generic version.
        // - When searching on a smaller area than the local aid's perimeter,
        //     then we display the local version.
        // Si on a un périmètre


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

        if (($backers instanceof ArrayCollection || is_array($backers)) && count($backers) > 0)
        {
            $qb
                ->innerJoin('a.aidFinancers', 'aidFinancersB')
                ->innerJoin('aidFinancersB.backer', 'backerB')
                ->andWhere('backerB IN (:backers)')
                ->setParameter('backers', $backers)
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

        if (is_array($keywords) && count($keywords) > 0) {
            $queryKeywords = '(';
            for ($i = 0; $i < count($keywords); $i++) {
                if ($i > 0) {
                    $queryKeywords .= ' OR ';
                }
                $queryKeywords .= 'keywords.name LIKE :keyword'.$i.' OR a.name LIKE :keyword'.$i.' OR categoriesKeyword.name LIKE :keyword'.$i.' OR a.description LIKE :keyword'.$i.' ';
                $qb->setParameter("keyword$i", '%'.$keywords[$i].'%');
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
                }
            } else {
                $qb->addOrderBy($orderBy['sort'], $orderBy['order']);
            }
            ;
        }

        if ($orderByDateSubmissionDeadline === true) {
            $qb
                ->addSelect('CASE WHEN a.dateSubmissionDeadline IS NULL THEN 1 ELSE 0 END as HIDDEN priority_is_null')
                ->addOrderBy('priority_is_null', 'ASC')
                ->addOrderBy('a.dateSubmissionDeadline', 'ASC')
            ;
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

        if (is_array($programIds) && count($programIds) > 0)
        {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs.id IN (:programIds)')
                ->setParameter('programIds', $programIds)
            ;
        }

        if (is_array($programSlugs) && count($programSlugs) > 0)
        {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs.slug IN (:programSlugs)')
                ->setParameter('programSlugs', $programSlugs)
            ;
        }

        if ($programs !== null && count($programs) > 0)
        {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs IN (:programs)')
                ->setParameter('programs', $programs)
            ;
        }

        if ($program instanceof Program && $program->getId())
        {
            $qb
                ->innerJoin('a.programs', 'programs')
                ->andWhere('programs = :program')
                ->setParameter('program', $program)
            ;
        }

        if (($aidDestinations instanceof ArrayCollection || is_array($aidDestinations)) && count($aidDestinations) > 0) {
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

        if ($europeanAid !== null)
        {
            // hbéritage django, on ne filtre pas si on veu tout
            if (strtolower($europeanAid) !== 'all') {
                if ($europeanAid == Aid::SLUG_EUROPEAN) {
                    $qb
                        ->andWhere('a.europeanAid IS NOT NULL')
                    ;
                } else {
                    $qb
                        ->andWhere('a.europeanAid = :europeanAid')
                        ->setParameter('europeanAid', $europeanAid)
                    ;
                }
            }
        }

        if ($isCallForProject !== null)
        {
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

        if ($state !== null)
        {
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
                    $dateLimit->add(new \DateInterval('P'.Aid::APPROACHING_DEADLINE_DELTA.'D'));
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

        if ($statusDisplay !== null)
        {
            switch ($statusDisplay) {
                case 'hidden':
                    $qb
                        ->addCriteria(self::hiddenCriteria('a.'))
                    ;
                    break;

                case 'live': 
                    $qb
                        ->addCriteria(self::liveCriteria('a.'))
                    ;
                    break;
            }
        }

        if ($status !== null)
        {
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
