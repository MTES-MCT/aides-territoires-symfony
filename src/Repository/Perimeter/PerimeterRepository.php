<?php

namespace App\Repository\Perimeter;

use App\Entity\Aid\Aid;
use App\Entity\Perimeter\Perimeter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Perimeter>
 *
 * @method Perimeter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Perimeter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Perimeter[]    findAll()
 * @method Perimeter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PerimeterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Perimeter::class);
    }

    public static function completeQueryBuilderForSearch(QueryBuilder $qb, string $query): QueryBuilder
    {
        // c'est un code postal
        if (preg_match('/^[\d]{5}$/', $query)) {
            $qb
                ->andWhere('
                    p.zipcodes LIKE :zipcodes
                ')
                ->setParameter('zipcodes', '%' . $query . '%');
        } else {
            $query = trim(preg_replace('/[+\-<>\(\)~*@]/', ' ', $query));
            $qb
            ->addSelect('MATCH_AGAINST(p.name) AGAINST(:name) AS HIDDEN relevance_score')
            ->addSelect('CASE WHEN p.name LIKE :startMatch THEN 1 ELSE 0 END AS HIDDEN start_match')
            ->andWhere('MATCH_AGAINST(p.name) AGAINST(:name) > 0 OR p.name LIKE :partialMatch')
            ->setParameter('name', $query . '*')
            ->setParameter('startMatch', $query . '%')
            ->setParameter('partialMatch', '%' . $query . '%');

            // Trier d'abord par les correspondances qui commencent par la recherche
            // Ensuite, trier par score de pertinence pour les résultats de `MATCH_AGAINST`
            $qb->orderBy('start_match', 'DESC')
                ->addOrderBy('relevance_score', 'DESC');
        }


        return $qb;
    }

    /**
     * @param int $idPerimeter
     * @param array<string, mixed>|null $params
     * @return Perimeter|null
     */
    public function getBiggestCity($idPerimeter, ?array $params = null): ?Perimeter
    {
        $params['scale'] = Perimeter::SCALE_COMMUNE;
        $params['orderBy'] = [
            'sort' => 'p.population',
            'order' => 'DESC'
        ];
        $params['idParent'] = $idPerimeter;
        $params['maxResults'] = 1;

        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Perimeter>
     */
    public function getDepartments(?array $params = null): array
    {
        $params['scale'] = Perimeter::SCALE_DEPARTEMENT;

        $qb = $this->getQueryBuilder($params)
            ->orderBy('p.code', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function countNbByOrganization(array $params = []): array
    {
        $scalePerimeter = $params['scalePerimeter'] ?? null;
        $organizationTypeSlug = $params['organizationTypeSlug'] ?? null;
        if (!$scalePerimeter) {
            return [];
        }
        $sqlParams = [
            'scalePerimeter' => $scalePerimeter
        ];

        if ($organizationTypeSlug) {
            $subSqlOrganization = '
                SELECT count(o.id)
                FROM organization o 
                INNER JOIN organization_type on organization_type.id = o.organization_type_id
                WHERE o.perimeter_id = p.id
                AND organization_type.slug = :organizationTypeSlug
            ';
            $sqlParams['organizationTypeSlug'] = $organizationTypeSlug;
        } else {
            $subSqlOrganization = '
                SELECT count(o.id)
                FROM organization o 
                WHERE o.perimeter_id = p.id
            ';
        }


        $sql = '
        SELECT COUNT(name) as nb_perimeter, nb_organization
        FROM (
            SELECT DISTINCT (p.name) as name,
            (
                ' . $subSqlOrganization . '
            ) as nb_organization
            FROM perimeter p 
            WHERE p.`scale`  = :scalePerimeter
        ) t
        GROUP BY nb_organization
        ORDER BY nb_organization;
        ';
        // lance la requete sql
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $result = $stmt->executeQuery($sqlParams);

        return $result->fetchAllAssociative();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, int>
     */
    public function getIdPerimetersContainedIn(array $params = null): array
    {
        $ids = [];

        $qb = $this->createQueryBuilder('p')
            ->select('perimetersFrom.id')
            ->innerJoin('p.perimetersFrom', 'perimetersFrom')
            ->andWhere('p = :perimeter')
            ->setParameter('perimeter', $params['perimeter']);

        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        $qb = $this->createQueryBuilder('p')
            ->select('perimetersTo.id')
            ->innerJoin('p.perimetersTo', 'perimetersTo')
            ->andWhere('p = :perimeter')
            ->setParameter('perimeter', $params['perimeter']);

        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        return array_unique($ids);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array<string, mixed>>
     */
    public function findNbBackersByCounty(array $params = null): array
    {
        $qb = $this->createQueryBuilder('p')
            // ->select('IFNULL(COUNT(DISTINCT(backer.id)), 0) AS nbBacker, p.name, p.code')
            ->select('
                backersFrom.id as backersFromId,
                backersTo.id as backersToId,
                p.name,
                p.code,
                perimetersFrom.name as nameTo
            ')
            ->innerJoin('p.perimetersFrom', 'perimetersFrom')
            ->innerJoin('perimetersFrom.aids', 'aidsFrom')

            ->innerJoin('perimetersFrom.backers', 'backersFrom')
            ->innerJoin('p.perimetersTo', 'perimetersTo')
            ->innerJoin('perimetersTo.backers', 'backersTo')
            ->innerJoin('perimetersTo.aids', 'aidsTo')
            // ->innerJoin('aids.aidFinancers', 'aidFinancers')
            // ->innerJoin('aidFinancers.backer', 'backer')
            // aid live
            ->andWhere('aidsFrom.status = :statusPublished')
            ->setParameter('statusPublished', Aid::STATUS_PUBLISHED)
            ->andWhere('(aidsFrom.dateStart <= :today OR aidsFrom.dateStart IS NULL)')
            ->andWhere('(aidsFrom.dateSubmissionDeadline > :today OR aidsFrom.dateSubmissionDeadline IS NULL)')
            ->setParameter('today', new \DateTime(date('Y-m-d')))

            ->andWhere('aidsTo.status = :statusPublished')
            ->andWhere('(aidsTo.dateStart <= :today OR aidsTo.dateStart IS NULL)')
            ->andWhere('(aidsTo.dateSubmissionDeadline > :today OR aidsTo.dateSubmissionDeadline IS NULL)')

            // departement
            ->andWhere('p.scale = :scaleCounty')
            ->setParameter('scaleCounty', Perimeter::SCALE_COUNTY)
            // ->groupBy('p.code')
            ->andWhere('p.code = :code')->setParameter('code', '91');


        $results = $qb->getQuery()->getResult();

        $resultsByCode = [];
        foreach ($results as $result) {
            if (!isset($resultsByCode[$result['code']])) {
                $resultsByCode[$result['code']] = [
                    'nbBackers' => 0,
                    'backersFrom' => [],
                    'backersTo' => [],
                    'backers' => [],
                    'name' => $result['name'],
                    'code' => $result['code']
                ];
            }

            if ($result['backersFromId']) {
                $resultsByCode[$result['code']]['backersFrom'][] = $result['backersFromId'];
            }
            if ($result['backersToId']) {
                $resultsByCode[$result['code']]['backersTo'][] = $result['backersToId'];
            }
        }

        foreach ($resultsByCode as $key => $resultByCode) {
            $resultsByCode[$key]['backersFrom'] = array_unique($resultByCode['backersFrom']);
            $resultsByCode[$key]['backersTo'] = array_unique($resultByCode['backersTo']);
            $resultsByCode[$key]['backers'] = array_merge($resultByCode['backersFrom'], $resultByCode['backersTo']);
            $resultsByCode[$key]['backers'] = array_unique($resultsByCode[$key]['backers']);
        }

        return $resultsByCode;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Perimeter>
     */
    public function findCounties(array $params = null): array
    {
        $params['scale'] = Perimeter::SCALE_COUNTY;
        $params['orderBy'] = [
            'sort' => 'p.code',
            'order' => 'ASC'
        ];
        $qb = $this->getQueryBuilder($params);

        $results = $qb->getQuery()->getResult();
        return $results;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countEpci(?array $params = null): int
    {
        $params['scale'] = Perimeter::SCALE_EPCI;
        $params['isObsolete'] = false;
        return $this->countCustom($params);
    }

    /**
     * @param array<string, mixed>|null $params
     * @return int
     */
    public function countCustom(array $params = null): int
    {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(p.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, array<string, mixed>>
     */
    public function findCommunesContained(?array $params = null): array
    {

        $qb = $this->getQueryBuilder($params);
        $qb->select('p.code, p.name');
        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return array<int, Perimeter>
     */
    public function findCustom(array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed>|null $params
     * @return QueryBuilder
     */
    public function getQueryBuilder(array $params = null): QueryBuilder
    {
        $scale = $params['scale'] ?? null;
        $orderBy =
            (isset($params['orderBy'])
            && isset($params['orderBy']['sort'])
            && isset($params['orderBy']['order']))
                ? $params['orderBy']
                : null
        ;
        $codes = $params['codes'] ?? null;
        $insees = $params['insees'] ?? null;
        $zipcodes = $params['zipcodes'] ?? null;
        $firstResult = $params['firstResult'] ?? null;
        $maxResults = $params['maxResults'] ?? null;
        $isVisibleToUsers = $params['isVisibleToUsers'] ?? null;
        $scaleLowerThan = $params['scaleLowerThan'] ?? null;
        $isObsolete = $params['isObsolete'] ?? null;
        $ids = $params['ids'] ?? null;
        $regions = $params['regions'] ?? null;
        $idParent = $params['idParent'] ?? null;
        $searchLike = $params['searchLike'] ?? null;

        $qb = $this->createQueryBuilder('p');

        if ($idParent !== null) {
            $qb
                ->innerJoin('p.perimetersTo', 'perimetersTo')
                ->andWhere('perimetersTo.id = :idParent')
                ->setParameter('idParent', $idParent)
            ;
        }

        if (is_array($regions) && count($regions) > 0) {
            $qb
                ->andWhere("JSON_CONTAINS(p.regions, :regions, '$') = 1 ")
                ->setParameter('regions', json_encode($regions))
            ;
        }

        if (is_array($ids) && count($ids) > 0) {
            $qb
                ->andWhere('p.id IN (:ids)')
                ->setParameter('ids', $ids)
            ;
        }
        if ($isObsolete !== null) {
            $qb->andWhere('p.isObsolete = :isObsolete')
                ->setParameter('isObsolete', $isObsolete);
        }

        if ($searchLike !== null) {
            $qb = self::completeQueryBuilderForSearch($qb, $searchLike);
        }

        if (is_array($insees)) {
            $qb
                ->andWhere('p.insee IN (:insees)')
                ->setParameter('insees', $insees)
            ;
        }

        if (is_array($zipcodes)) {
            $sqlZipcodes = '';
            for ($i = 0; $i < count($zipcodes); $i++) {
                $zipcodes[$i] = (int) $zipcodes[$i];
                $sqlZipcodes .= 'p.zipcodes LIKE :zipcode' . $i;
                if ($i < count($zipcodes) - 1) {
                    $sqlZipcodes .= ' OR ';
                }
                $qb
                    ->setParameter('zipcode' . $i, '%' . $zipcodes[$i] . '%');
            }
            $qb
                ->andWhere($sqlZipcodes);
        }

        if ($isVisibleToUsers !== null) {
            $qb
                ->andWhere('p.isVisibleToUsers = :isVisibleToUsers')
                ->setParameter('isVisibleToUsers', $isVisibleToUsers);
        }

        if ($scaleLowerThan !== null) {
            $qb
                ->andWhere('p.scale < :scaleLowerThan')
                ->setParameter('scaleLowerThan', $scaleLowerThan);
        }

        if ($scale !== null) {
            $qb
                ->andWhere('p.scale = :scale')
                ->setParameter('scale', $scale)
            ;
        }

        if (is_array($codes) && count($codes) > 0) {
            $qb
                ->andWhere('p.code IN (:codes)')
                ->setParameter('codes', $codes)
            ;
        }

        if ($orderBy !== null) {
            $qb->orderBy($orderBy['sort'], $orderBy['order']);
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
