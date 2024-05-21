<?php

namespace App\Message\Alert;

class AlertResume
{
    private $alertFrequency;

    public function __construct(string $alertFrequency)
    {
        $this->alertFrequency = $alertFrequency;
    }

    public function getAlertFrequency(): string
    {
        return $this->alertFrequency;
    }
}
