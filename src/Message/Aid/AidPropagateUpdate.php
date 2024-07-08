<?php

namespace App\Message\Aid;

class AidPropagateUpdate
{
    private $idAidGeneric;
    private $idAidLocal;

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
