<?php

namespace App\Message\Backer;

class BackerCountAid
{
    private int $idBacker;

    public function __construct(int $idBacker)
    {
        $this->idBacker = $idBacker;
    }

    public function getIdBacker(): int
    {
        return $this->idBacker;
    }
}
