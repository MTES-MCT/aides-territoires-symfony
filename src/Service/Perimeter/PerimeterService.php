<?php

namespace App\Service\Perimeter;

use App\Entity\Perimeter\Perimeter;
use App\Repository\Perimeter\PerimeterRepository;

class PerimeterService
{
    public function __construct(
        protected PerimeterRepository $perimeterRepository
    ) {
    }

    /**
     *
     * @param array<string> $inseeCodes
     * @return string
     */
    public function getAdhocNameFromInseeCodes(array $inseeCodes): string
    {
        $regionCodes = [];
        $perimeters = $this->perimeterRepository
            ->findCustom(['scale' => Perimeter::SCALE_COMMUNE, 'codes' => $inseeCodes]);

        foreach ($perimeters as $key => $perimeter) {
            if ($perimeter->getRegions()) {
                foreach ($perimeter->getRegions() as $region) {
                    $regionCodes[] = $region;
                }
            }
            unset($perimeters[$key]);
        }
        $regionCodes = array_unique($regionCodes);
        sort($regionCodes);
        return 'regions_' . join('_', $regionCodes);
    }

    /**
     *
     * @param array<string> $regionCodes
     * @return string
     */
    public function getAdhocNameFromRegionCodes(array $regionCodes): string
    {
        sort($regionCodes);
        return 'regions_' . join('_', $regionCodes);
    }

    public function getSmartName(?Perimeter $perimeter): string
    {
        if (!$perimeter instanceof Perimeter) {
            return '';
        }
        if ($perimeter->getScale() == Perimeter::SCALE_COMMUNE) {
            if (is_array($perimeter->getZipcodes())) {
                return $perimeter->getName() . ' (Commune - ' . join(', ', $perimeter->getZipcodes()) . ')';
            } else {
                return $perimeter->getName() . ' (Commune - ' . $perimeter->getZipcodes() . ')';
            }
        } else {
            if (isset(Perimeter::SCALES_FOR_SEARCH[$perimeter->getScale()]['name'])) {
                return $perimeter->getName()
                    . ' ('
                    . Perimeter::SCALES_FOR_SEARCH[$perimeter->getScale()]['name']
                    . ')';
            } else {
                return $perimeter->getName();
            }
        }
    }

    /**
     * Si perimetre adhoc va exploder la string pour recuperer le nom de chaque region
     * sinon retourne directement le nom de la région
     *
     * @param Perimeter $perimeter
     * @return string
     */
    public function getSmartRegionNames(Perimeter $perimeter): string
    {
        if (!preg_match('/regions_(.*)/', $perimeter->getName(), $matches)) {
            return $perimeter->getName();
        } else {
            $codes = explode('_', $matches[1]);
            $perimeters = $this->perimeterRepository->findCustom(
                [
                    'scale' => Perimeter::SCALE_REGION,
                    'codes' => $codes,
                    'orderBy' => [
                        'sort' => 'p.name',
                        'order' => 'ASC'
                    ]
                ]
            );

            $strPerimeters = '';
            foreach ($perimeters as $perimeter) {
                $strPerimeters .= $perimeter->getName() . ', ';
            }
            return substr($strPerimeters, 0, -2);
        }
    }

    /**
     * Retourne les scales d'un group
     *
     * @param string $scaleGroup
     * @return array<int>
     */
    public function getScalesFromGroup(string $scaleGroup): array
    {
        $scales = [];

        if ($scaleGroup == Perimeter::SLUG_LOCAL_GROUP) {
            foreach (Perimeter::SCALES_LOCAL_GROUP as $scale) {
                $scales[] = $scale['scale'];
            }
        } elseif ($scaleGroup == Perimeter::SLUG_NATIONAL_GROUP) {
            foreach (Perimeter::SCALES_NATIONAL_GROUP as $scale) {
                $scales[] = $scale['scale'];
            }
        }

        return $scales;
    }

    /**
     * Retourne les infos d'une scale en fonction de son identifiant
     *
     * @param string $scale
     * @return array{scale: int, slug: string, name: string}|null
     */
    public function getScale(string $scale): ?array
    {
        $scales = [
            1 => ['scale' => 1, 'slug' => 'commune', 'name' => 'Commune'],
            5 => ['scale' => 5, 'slug' => 'epci', 'name' => 'EPCI'],
            8 => ['scale' => 8, 'slug' => 'basin', 'name' => 'Bassin hydrographique'],
            10 => ['scale' => 10, 'slug' => 'department', 'name' => 'Département'],
            15 => ['scale' => 15, 'slug' => 'region', 'name' => 'Région'],
            16 => ['scale' => 16, 'slug' => 'overseas', 'name' => 'Outre-mer'],
            17 => ['scale' => 17, 'slug' => 'mainland', 'name' => 'Métropole'],
            18 => ['scale' => 18, 'slug' => 'adhoc', 'name' => 'Ad-hoc'],
            20 => ['scale' => 20, 'slug' => 'country', 'name' => 'Pays'],
            25 => ['scale' => 25, 'slug' => 'continent', 'name' => 'Continent']
        ];

        return $scales[$scale] ?? null;
    }

    /**
     * Retourne les infos d'une scale en fonction de son identifiant
     *
     * @param string $scale
     * @return array{scale: int, slug: string, name: string}|null
     */
    public function getScaleFromSlug(string $scale): ?array
    {
        $scales = [
            'commune' => ['scale' => 1, 'slug' => 'commune', 'name' => 'Commune'],
            'epci' => ['scale' => 5, 'slug' => 'epci', 'name' => 'EPCI'],
            'basin' => ['scale' => 8, 'slug' => 'basin', 'name' => 'Bassin hydrographique'],
            'department' => ['scale' => 10, 'slug' => 'department', 'name' => 'Département'],
            'region' => ['scale' => 15, 'slug' => 'region', 'name' => 'Région'],
            'overseas' => ['scale' => 16, 'slug' => 'overseas', 'name' => 'Outre-mer'],
            'mainland' => ['scale' => 17, 'slug' => 'mainland', 'name' => 'Métropole'],
            'adhoc' => ['scale' => 18, 'slug' => 'adhoc', 'name' => 'Ad-hoc'],
            'country' => ['scale' => 20, 'slug' => 'country', 'name' => 'Pays'],
            'continent' => ['scale' => 25, 'slug' => 'continent', 'name' => 'Continent']
        ];

        return $scales[$scale] ?? null;
    }
}
