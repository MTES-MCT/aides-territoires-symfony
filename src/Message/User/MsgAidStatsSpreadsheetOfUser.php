<?php

namespace App\Message\User;

class MsgAidStatsSpreadsheetOfUser
{
    private $idUser;
    private $dateMin;
    private $dateMax;

    public function __construct(
        int $idUser,
        \DateTime $dateMin,
        \DateTime $dateMax
    )
    {
        $this->idUser = $idUser;
        $this->dateMin = $dateMin;
        $this->dateMax = $dateMax;
    }

    public function getIdUser(): int
    {
        return $this->idUser;
    }

    public function getDateMin(): \DateTime
    {
        return $this->dateMin;
    }

    public function getDateMax(): \DateTime
    {
        return $this->dateMax;
    }
}
