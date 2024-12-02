<?php

namespace App\Message\User;

class MsgProjectExportAids
{
    private int $idUser;
    private int $idProject;
    private string $format;

    public function __construct(int $idUser, int $idProject, string $format)
    {
        $this->idUser = $idUser;
        $this->idProject = $idProject;
        $this->format = $format;
    }

    public function getIdUser(): int
    {
        return $this->idUser;
    }

    public function getIdProject(): int
    {
        return $this->idProject;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
