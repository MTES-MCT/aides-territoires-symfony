<?php

namespace App\Message\Aid;

class AidExtractKeyword
{
    private int $idAid;

    public function __construct(int $idAid)
    {
        $this->idAid = $idAid;
    }

    public function getIdAid(): int
    {
        return $this->idAid;
    }
}
