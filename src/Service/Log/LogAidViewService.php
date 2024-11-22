<?php

namespace App\Service\Log;

use App\Entity\Aid\Aid;
use App\Repository\Log\LogAidViewRepository;

class LogAidViewService
{
    public function __construct(
        private LogAidViewRepository $logAidViewRepository
    ) {
    }

    /**
     * Undocumented function
     *
     * @param Aid $aid
     * @param \DateTime $dateMin
     * @param \DateTime $dateMax
     * @return array<string, int>
     */
    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $nbEntriesByDay = [];
        $nbEntries = $this->logAidViewRepository->countByDay(
            [
                'aid' => $aid,
                'dateMin' => $dateMin,
                'dateMax' => $dateMax
            ]
        );
        foreach ($nbEntries as $nbEntry) {
            $nbEntriesByDay[$nbEntry['dateDay']] = $nbEntry['nb'];
        }

        return $nbEntriesByDay;
    }
}
