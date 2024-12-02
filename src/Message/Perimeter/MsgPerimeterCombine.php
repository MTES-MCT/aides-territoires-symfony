<?php

namespace App\Message\Perimeter;

class MsgPerimeterCombine
{
    private int $idPerimeter;
    private int $idPerimeterToAdd;

    public function __construct(int $idPerimeter, int $idPerimeterToAdd)
    {
        $this->idPerimeter = $idPerimeter;
        $this->idPerimeterToAdd = $idPerimeterToAdd;
    }

    public function getIdPerimeter(): int
    {
        return $this->idPerimeter;
    }

    public function getIdPerimeterToAdd(): int
    {
        return $this->idPerimeterToAdd;
    }
}
