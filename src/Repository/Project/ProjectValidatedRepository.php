<?php

namespace App\Repository\Project;

use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectValidated;
use App\Service\Reference\ReferenceService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectValidated>
 *
 * @method ProjectValidated|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectValidated|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectValidated[]    findAll()
 * @method ProjectValidated[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectValidatedRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected ReferenceService $referenceService
    ) {
        parent::__construct($registry, ProjectValidated::class);
    }

    public function countProjectInCounties(?array $params = null): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('COUNT(DISTINCT(p.id)) as nb, perimeterDepartment.id as id')
            ->innerJoin('p.organization', 'organization')
            ->innerJoin('organization.perimeterDepartment', 'perimeterDepartment')
            ->groupBy('perimeterDepartment.id')
            ;

        return $qb->getQuery()->getResult();

    }
    public function countProjectInCounty(array $params = null): int
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->select('COUNT(DISTINCT(p.id)) as nb')
            ->innerJoin('p.organization', 'organization')
            ->innerJoin('organization.perimeterDepartment', 'perimeterDepartment')
            ->andWhere('perimeterDepartment.id = :id')
            ->setParameter('id', $params['id'])
        ;


        return $queryBuilder->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function findProjectInCounty(array $params = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->innerJoin('p.organization', 'organization')
            ->innerJoin('organization.perimeterDepartment', 'perimeterDepartment')
            ->andWhere('perimeterDepartment.id = :id')
            ->setParameter('id', $params['id'])
        ;


        return $queryBuilder->getQuery()->getResult();
    }

    public function findProjectInRadius(array $params = null): array
    {
        $keyword = $params['keyword'] ?? null;
        $intentions_string = $params['intentions_string'] ?? null;
        $objects_string = $params['objects_string'] ?? null;
        $simple_words_string = $params['simple_words_string'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $radius = $params['radius'] ?? null;
        $search = $params['search'] ?? null;
        $scoreTotalMin = $params['scoreTotalMin'] ?? 30;
        $maxResults = $params['maxResults'] ?? null;

        $queryBuilder = $this->createQueryBuilder('p');

        if ($search !== null) {
            $synonyms = $this->referenceService->getSynonymes($search);
            $originalName = (isset($synonyms['original_name']) && trim($synonyms['original_name']) !== '')  ? $synonyms['original_name'] : null;
            $intentionsString = (isset($synonyms['intentions_string']) && trim($synonyms['intentions_string']) !== '')  ? $synonyms['intentions_string'] : null;
            $objectsString = (isset($synonyms['objects_string']) && trim($synonyms['objects_string']) !== '')  ? $synonyms['objects_string'] : null;
            $simpleWordsString = (isset($synonyms['simple_words_string']) && trim($synonyms['simple_words_string']) !== '')  ? $synonyms['simple_words_string'] : null;

            if ($originalName) {
                $sqlOriginalName = '
                CASE WHEN (p.projectName = :originalName) THEN 500 ELSE 0 END 
                ';
                $queryBuilder->setParameter('originalName', $originalName);
            }

            if ($objectsString) {
                $sqlObjects = '
                CASE WHEN (MATCH_AGAINST(p.projectName) AGAINST(:objects_string IN BOOLEAN MODE) > 1) THEN 90 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:objects_string IN BOOLEAN MODE) > 1) THEN 10 ELSE 0 END 
                ';

                $objects = str_getcsv($objectsString, ' ', '"');
                if (count($objects) > 0) {
                    $sqlObjects .= ' + ';
                }
                for ($i = 0; $i < count($objects); $i++) {

                    $sqlObjects .= '
                        CASE WHEN (p.projectName LIKE :objects' . $i . ') THEN 30 ELSE 0 END
                    ';
                    if ($i < count($objects) - 1) {
                        $sqlObjects .= ' + ';
                    }
                    $queryBuilder->setParameter('objects' . $i, '%' . $objects[$i] . '%');
                }

                $queryBuilder->setParameter('objects_string', $objectsString);
            }
            if ($intentionsString && $objectsString) {
                $sqlIntentions = '
                CASE WHEN (MATCH_AGAINST(p.projectName) AGAINST(:intentions_string IN BOOLEAN MODE) > 1) THEN 5 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:intentions_string IN BOOLEAN MODE) > 1) THEN 1 ELSE 0 END 
                ';
                $queryBuilder->setParameter('intentions_string', $intentionsString);
            }

            if ($simpleWordsString) {
                $sqlSimpleWords = '
                CASE WHEN (MATCH_AGAINST(p.projectName) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1) THEN 30 ELSE 0 END +
                CASE WHEN (MATCH_AGAINST(p.description) AGAINST(:simple_words_string IN BOOLEAN MODE) > 1) THEN 5 ELSE 0 END 
                ';
                $queryBuilder->setParameter('simple_words_string', $simpleWordsString);
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

            if ($sqlTotal !== '') {
                $scoreTotalAvailable = true;
                $queryBuilder->addSelect('(' . $sqlTotal . ') as score_total');
                $queryBuilder->andHaving('score_total >= ' . $scoreTotalMin);
            }
        }
        if ($perimeter instanceof Perimeter && $perimeter->getLatitude() && $perimeter->getLongitude() && $radius !== null) {
            $queryBuilder
                ->addSelect('(((ACOS(SIN(:lat * PI() / 180) * SIN(perimeter.latitude * PI() / 180) + COS(:lat * PI() / 180) *
                 COS(perimeter.latitude * PI() / 180) * COS((:lng - perimeter.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1.6093) AS dist')
                ->innerJoin('p.organization', 'organization')
                ->innerJoin('organization.perimeter', 'perimeter')
                ->setParameter('lat', $perimeter->getLatitude())
                ->setParameter('lng', $perimeter->getLongitude())
                ->andHaving('dist <= :distanceKm')
                ->setParameter('distanceKm', $radius)
                ->orderBy('dist', 'ASC');
            ;
        }

        if (isset($scoreTotalAvailable)) {
            $queryBuilder->orderBy('score_total', 'DESC');
        }

        if ($maxResults) {
            $queryBuilder->setMaxResults($maxResults);
        }

        $results = $queryBuilder->getQuery()->getResult();
        $projects = [];
        foreach ($results as $result) {
            if ($result instanceof ProjectValidated) {
                $projects[] = $result;
                continue;
            } else {
                if (isset($result[0])) {
                    if (isset($result['dist'])) {
                        $result[0]->setDistance($result['dist'] ?? null);
                    }
                    $projects[] = $result[0];
                }
            }
        }

        return $projects;
    }

    public function findCustom(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);


        $results = $qb->getQuery()->getResult();
        $projects = [];
        foreach ($results as $result) {
            if ($result instanceof ProjectValidated) {
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

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $keyword = $params['keyword'] ?? null;
        $intentions_string = $params['intentions_string'] ?? null;
        $objects_string = $params['objects_string'] ?? null;
        $simple_words_string = $params['simple_words_string'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $radius = $params['radius'] ?? null;
        $organizationType = $params['organizationType'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if (
            $perimeter instanceof Perimeter
            && $perimeter->getLatitude()
            && $perimeter->getLongitude()
        ) {
            $qb
                ->addSelect('(((ACOS(SIN(:lat * PI() / 180) * SIN(perimeter.latitude * PI() / 180) + COS(:lat * PI() / 180) *
            COS(perimeter.latitude * PI() / 180) * COS((:lng - perimeter.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1.6093) AS dist')
                ->innerJoin('p.organization', 'organization')
                ->innerJoin('organization.perimeter', 'perimeter')
                ->setParameter('lat', $perimeter->getLatitude())
                ->setParameter('lng', $perimeter->getLongitude())
                ->orderBy('dist', 'ASC')
            ;
            if ($radius) {
                $qb
                    ->having('dist <= :distanceKm')
                    ->setParameter('distanceKm', $radius)
                ;
            }
        }

        if ($organizationType instanceof OrganizationType && $organizationType->getId()) {
            $qb
                ->innerJoin('p.organization', 'organizationForType')
                ->andWhere('organizationForType.organizationType = :organizationType')
                ->setParameter('organizationType', $organizationType)
            ;
        }

        if ($keyword !== null) {
            $qb
                ->andWhere('p.projectName LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%')
            ;
        }

        if ($objects_string !== null) {
            $qb
                ->andWhere('
                MATCH_AGAINST(p.projectName) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                OR MATCH_AGAINST(p.description) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                ')
                ->setParameter('objects_string', $objects_string)
            ;
        }

        return $qb;
    }
}
