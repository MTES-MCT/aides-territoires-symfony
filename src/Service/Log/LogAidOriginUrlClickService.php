<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Repository\Log\LogAidOriginUrlClickRepository;

class LogAidOriginUrlClickService
{
    public function __construct(
        private LogAidOriginUrlClickRepository $logAidOriginUrlClickRepository
    ) {
    }

    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $NbEntriesByDay = [];
        $nbEntries = $this->logAidOriginUrlClickRepository->countByDay(
            [
                'aid' => $aid,
                'dateMin' => $dateMin,
                'dateMax' => $dateMax
            ]
        );
        foreach ($nbEntries as $nbEntry) {
            $NbEntriesByDay[$nbEntry['dateDay']] = $nbEntry['nb'];
        }

        return $NbEntriesByDay;
    }
}
