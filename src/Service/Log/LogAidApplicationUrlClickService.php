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

    /**
     *
     * @param Aid $aid
     * @param \DateTime $dateMin
     * @param \DateTime $dateMax
     * @return array<string, int>
     */
    public function getCountByDay(Aid $aid, \DateTime $dateMin, \DateTime $dateMax): array
    {
        $nbEntriesByDay = [];
        $nbEntries = $this->logAidApplicationUrlClickRepository->countByDay(
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
