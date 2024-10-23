<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Repository\Log\LogAidApplicationUrlClickRepository;

class LogAidApplicationUrlClickService
{
    public function __construct(
        private LogAidApplicationUrlClickRepository $logAidApplicationUrlClickRepository
    ) {
    }

    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $NbEntriesByDay = [];
        $nbEntries = $this->logAidApplicationUrlClickRepository->countByDay(
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
