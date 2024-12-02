<?php

namespace App\Message\Aid;

class AidPropagateUpdate
{
    private int $idAidGeneric;
    private int $idAidLocal;

    public function __construct(int $idAidGeneric, int $idAidLocal)
    {
        $this->idAidGeneric = $idAidGeneric;
        $this->idAidLocal = $idAidLocal;
    }

    public function getIdAidGeneric(): int
    {
        return $this->idAidGeneric;
    }

    public function getIdAidLocal(): int
    {
        return $this->idAidLocal;
    }
}
