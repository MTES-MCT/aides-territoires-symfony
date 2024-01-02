<?php

namespace App\Repository\Project;

use App\Entity\Perimeter\Perimeter;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectValidated;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectValidated::class);
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

        $queryBuilder = $this->createQueryBuilder('p');

        if ($perimeter instanceof Perimeter && $perimeter->getLatitude() && $perimeter->getLongitude() && $radius !== null) {
            $queryBuilder
            ->addSelect('(((ACOS(SIN(:lat * PI() / 180) * SIN(perimeter.latitude * PI() / 180) + COS(:lat * PI() / 180) *
                 COS(perimeter.latitude * PI() / 180) * COS((:lng - perimeter.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1.6093) AS dist')
            ->innerJoin('p.organization', 'organization')
            ->innerJoin('organization.perimeter', 'perimeter')
            ->setParameter('lat', $perimeter->getLatitude())
            ->setParameter('lng', $perimeter->getLongitude())
            ->having('dist <= :distanceKm')
            ->setParameter('distanceKm', $radius)
            ->orderBy('dist', 'ASC');
            ;
        }


        if ($keyword !== null) {
            $queryBuilder
                ->andWhere('p.projectName LIKE :keyword')
                ->setParameter('keyword', '%'.$keyword.'%')
                ;
        }

        if ($objects_string !== null && $objects_string !== '') {
            $queryBuilder
                ->andWhere('
                MATCH_AGAINST(p.projectName) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                OR MATCH_AGAINST(p.description) AGAINST (:objects_string IN BOOLEAN MODE) > 5
                ')
                ->setParameter('objects_string', $objects_string)
                ->addSelect(
                    '(
                    MATCH_AGAINST(p.projectName) AGAINST (:objects_string IN BOOLEAN MODE)
                    + MATCH_AGAINST(p.description) AGAINST (:objects_string IN BOOLEAN MODE)
                    ) AS score_match
                    '
                )
                ->addOrderBy('score_match', 'DESC')
            ;
        }

        if ($simple_words_string !== null && $objects_string == '') {
            $queryBuilder
                ->andWhere('
                MATCH_AGAINST(p.projectName) AGAINST (:simple_words_string IN BOOLEAN MODE) > 2
                OR MATCH_AGAINST(p.description) AGAINST (:simple_words_string IN BOOLEAN MODE) > 2
                ')
                ->setParameter('simple_words_string', $simple_words_string)
                ->addSelect(
                    '(
                    MATCH_AGAINST(p.projectName) AGAINST (:simple_words_string IN BOOLEAN MODE)
                    + MATCH_AGAINST(p.description) AGAINST (:simple_words_string IN BOOLEAN MODE)
                    ) AS score_match
                    '
                )
                ->addOrderBy('score_match', 'DESC')
            ;
        }

        $results = $queryBuilder->getQuery()->getResult();
        $projects=[];
        foreach($results as $result){
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

    public function findCustom(?array $params): array
    {
        $qb = $this->getQueryBuilder($params);


        $results = $qb->getQuery()->getResult();
        $projects = [];
        foreach($results as $result) {
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

    public function getQueryBuilder(?array $params): QueryBuilder
    {
        $keyword = $params['keyword'] ?? null;
        $intentions_string = $params['intentions_string'] ?? null;
        $objects_string = $params['objects_string'] ?? null;
        $simple_words_string = $params['simple_words_string'] ?? null;
        $perimeter = $params['perimeter'] ?? null;
        $radius = $params['radius'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if ($perimeter instanceof Perimeter
        && $perimeter->getLatitude()
        && $perimeter->getLongitude()) {
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

        if ($keyword !== null) {
            $qb
                ->andWhere('p.projectName LIKE :keyword')
                ->setParameter('keyword', '%'.$keyword.'%')
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
