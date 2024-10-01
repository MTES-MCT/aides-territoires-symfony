<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Repository\Log\LogAidViewRepository;

class LogAidViewService
{
    public function __construct(
        private LogAidViewRepository $logAidViewRepository
    )
    {
    }

    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $NbEntriesByDay = [];
        $nbEntries = $this->logAidViewRepository->countByDay(
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