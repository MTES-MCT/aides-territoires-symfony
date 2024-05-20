<?php

namespace App\Message\Alert;

class AlertMessage
{
    private $idAlert;

    // id alert est une string
    public function __construct(string $idAlert)
    {
        $this->idAlert = $idAlert;
    }

    public function getIdAlert(): string
    {
        return $this->idAlert;
    }
}
