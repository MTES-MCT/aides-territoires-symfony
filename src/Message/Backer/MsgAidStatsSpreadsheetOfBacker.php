<?php

namespace App\Message\Backer;

class MsgAidStatsSpreadsheetOfBacker
{
    private int $idBacker;
    private \DateTime $dateMin;
    private \DateTime $dateMax;
    private string $targetEmail;

    public function __construct(
        int $idBacker,
        \DateTime $dateMin,
        \DateTime $dateMax,
        string $targetEmail
    ) {
        $this->idBacker = $idBacker;
        $this->dateMin = $dateMin;
        $this->dateMax = $dateMax;
        $this->targetEmail = $targetEmail;
    }

    public function getIdBacker(): int
    {
        return $this->idBacker;
    }

    public function getDateMin(): \DateTime
    {
        return $this->dateMin;
    }

    public function getDateMax(): \DateTime
    {
        return $this->dateMax;
    }

    public function getTargetEmail(): string
    {
        return $this->targetEmail;
    }
}
