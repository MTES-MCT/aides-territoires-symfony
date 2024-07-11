<?php

namespace App\Message\Perimeter;

class MsgPerimeterCombine
{
    private $idPerimeter;
    private $idPerimeterToAdd;

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
