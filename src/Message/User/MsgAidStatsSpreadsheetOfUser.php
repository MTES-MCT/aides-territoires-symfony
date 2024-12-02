<?php

namespace App\Message\User;

class MsgAidStatsSpreadsheetOfUser
{
    private int $idUser;
    private \DateTime $dateMin;
    private \DateTime $dateMax;
    private ?string $forceEmail;
    private ?string $forceSubject;

    public function __construct(
        int $idUser,
        \DateTime $dateMin,
        \DateTime $dateMax,
        ?string $forceEmail = null,
        ?string $forceSubject = null
    ) {
        $this->idUser = $idUser;
        $this->dateMin = $dateMin;
        $this->dateMax = $dateMax;
        $this->forceEmail = $forceEmail;
        $this->forceSubject = $forceSubject;
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

    public function getForceEmail(): ?string
    {
        return $this->forceEmail;
    }

    public function getForceSubject(): ?string
    {
        return $this->forceSubject;
    }
}
