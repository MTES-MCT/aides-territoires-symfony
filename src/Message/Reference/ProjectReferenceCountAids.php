<?php

namespace App\Message\Reference;

class ProjectReferenceCountAids
{
    private int $idProjectReference;

    public function __construct(int $idProjectReference)
    {
        $this->idProjectReference = $idProjectReference;
    }

    public function getIdProjectReference(): int
    {
        return $this->idProjectReference;
    }
}
