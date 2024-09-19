<?php

namespace App\Service\Aid;

use App\Entity\Aid\Aid;
use App\Repository\Aid\AidProjectRepository;

class AidProjectService
{
    public function __construct(
        private AidProjectRepository $aidProjectRepository
    )
    {
    }

    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax, ?bool $projectPublic = null): array
    {
        $NbEntriesByDay = [];
        $criterias = [
            'aid' => $aid,
            'dateMin' => $dateMin,
            'dateMax' => $dateMax
        ];
        if ($projectPublic !== null) {
            $criterias['projectPublic'] = $projectPublic;
        }
        $nbEntries = $this->aidProjectRepository->countProjectByAidByDay(
            $aid, 
            $criterias
        );
        foreach ($nbEntries as $nbEntry) {
            $NbEntriesByDay[$nbEntry['dateDay']] = $nbEntry['nb'];
        }

        return $NbEntriesByDay;
    }
}