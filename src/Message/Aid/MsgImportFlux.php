<?php
namespace App\Message\Aid;

class MsgImportFlux
{
    public function __construct(
        private string $command
    ) {
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
