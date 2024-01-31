<?php

namespace App\Repository\Organization;

use App\Entity\Organization\Organization;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 *
 * @method Organization|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organization|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organization[]    findAll()
 * @method Organization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function countWithUserBeneficiary(?array $params = null): int
    {
        $params['hasUserBeneficiary'] = true;
        return $this->countCustom($params);
    }

    public function countWithUserContributor(?array $params = null): int
    {
        $params['hasUserContributor'] = true;
        return $this->countCustom($params);
    }

    public function countCommune(?array $params = null) : int
    {
        $params['typeSlug'] = OrganizationType::SLUG_COMMUNE;
        $params['perimeterScale'] = Perimeter::SCALE_COMMUNE;
        $params['perimeterIsObsolete'] = false;
        $params['isImported'] = false;
        return $this->countCustom($params);        
    }

    public function countInterco(?array $params = null) : int
    {
        $params['typeSlug'] = OrganizationType::SLUG_EPCI;
        $params['perimeterScale'] = Perimeter::SCALE_EPCI;
        $params['perimeterIsObsolete'] = false;
        $params['isImported'] = false;
        return $this->countCustom($params);        
    }

    public function countEpci(?array $params = null) : int
    {
        $params['typeSlug'] = OrganizationType::SLUG_EPCI;
        $params['perimeterScale'] = Perimeter::SCALE_EPCI;
        $params['perimeterIsObsolete'] = false;
        $params['isImported'] = false;
        $params['intercommunalityType'] = ["CC", "CA", "CU", "METRO"];
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(perimeterForScale.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;      
    }

    public function countByType(?array $params = null): array
    {
        $qb = $this->getQueryBuilder($params);
        $qb->innerJoin('o.organizationType', 'organizationType');
        $qb->select('IFNULL(COUNT(o.id), 0) AS nb, organizationType.name AS typeName');
        $qb->groupBy('typeName');
        $qb->orderBy('nb', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function countCustom(array $params = null) : int {
        $qb = $this->getQueryBuilder($params);

        $qb->select('IFNULL(COUNT(DISTINCT(o.id)), 0) AS nb');

        return $qb->getQuery()->getResult()[0]['nb'] ?? 0;
    }

    public function countCollaborators(User $user): int
    {
        $result = $this->createQueryBuilder('o')
        ->select('COUNT(o.id) AS nb')
        ->innerJoin('o.beneficiairies','beneficiairies')
        ->andWhere('o = :userOrganization')
        ->setParameter('userOrganization', $user->getDefaultOrganization())
        ->andWhere('beneficiairies != :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult()
        ;
        return $result[0]['nb'] ?? 0;

    }

    public function findCounties(Organization $organization)
    {

    }

    public function getQueryBuilder(?array $params = null): QueryBuilder
    {
        $typeSlug = $params['typeSlug'] ?? null;
        $perimeterScale = $params['perimeterScale'] ?? null;
        $perimeterIsObsolete = $params['perimeterIsObsolete'] ?? null;
        $isImported = $params['isImported'] ?? null;
        $intercommunalityTypes = $params['intercommunalityTypes'] ?? null;
        $dateCreateMin = $params['dateCreateMin'] ?? null;
        $dateCreateMax = $params['dateCreateMax'] ?? null;
        $hasUserBeneficiary = $params['hasUserBeneficiary'] ?? null;
        $hasUserContributor = $params['hasUserContributor'] ?? null;
        $hasPerimeter = $params['hasPerimeter'] ?? null;

        $qb = $this->createQueryBuilder('o');

        if ($hasPerimeter) {
            $qb->innerJoin('o.perimeter', 'perimeterForPerimeter');
        }
        if ($hasUserContributor) {
            $qb
                ->innerJoin('o.beneficiairies', 'beneficiairiesForContributor')
                ->andWhere('beneficiairiesForContributor.isContributor = :isContributorTrue')
                ->setParameter('isContributorTrue', true)
                ;
        }

        if ($hasUserBeneficiary) {
            $qb
                ->innerJoin('o.beneficiairies', 'beneficiairiesForBeneficiary')
                ->andWhere('beneficiairiesForBeneficiary.isBeneficiary = :isBeneficiaryTrue')
                ->setParameter('isBeneficiaryTrue', true)
                ;
        }

        if ($dateCreateMin instanceof \DateTime) {
            $qb->andWhere('o.dateCreate >= :dateCreateMin')
            ->setParameter('dateCreateMin', $dateCreateMin);
        }

        if ($dateCreateMax instanceof \DateTime) {
            $qb->andWhere('o.dateCreate <= :dateCreateMax')
            ->setParameter('dateCreateMax', $dateCreateMax);
        }

        if (is_array($intercommunalityTypes)) {
            $qb
                ->andWhere('o.intercommunalityType IN (:intercommunalityTypes)')
                ->setParameter('intercommunalityTypes', $intercommunalityTypes)
                ;
        }
        if ($isImported !== null) {
            $qb->andWhere('o.isImported = :isImported')
            ->setParameter('isImported', $isImported);
        }
        if ($typeSlug !== null)
        {
            $qb->innerJoin('o.organizationType', 'organizationType')
            ->andWhere('organizationType.slug = :typeSlug')
            ->setParameter('typeSlug', $typeSlug);
        }

        if ($perimeterScale !== null) {
            $qb->innerJoin('o.perimeter', 'perimeterForScale')
            ->andWhere('perimeterForScale.scale = :perimeterScale')
            ->setParameter('perimeterScale', $perimeterScale);
        }

        if ($perimeterIsObsolete !== null) {
            $qb->innerJoin('o.perimeter', 'perimeterForObsolete')
            ->andWhere('perimeterForObsolete.isObsolete = :perimeterIsObsolete')
            ->setParameter('perimeterIsObsolete', $perimeterIsObsolete);
        }

        return $qb;
    }
}
