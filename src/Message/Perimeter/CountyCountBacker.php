<?php

namespace App\Message\Perimeter;

class CountyCountBacker
{
    private int $idPerimeter;

    public function __construct(int $idPerimeter)
    {
        $this->idPerimeter = $idPerimeter;
    }

    public function getIdPerimeter(): int
    {
        return $this->idPerimeter;
    }
}
