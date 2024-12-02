<?php

namespace App\Message\User;

class AidsExportPdf
{
    private int $idUser;
    private int $idOrganization;

    public function __construct(int $idUser, int $idOrganization)
    {
        $this->idUser = $idUser;
        $this->idOrganization = $idOrganization;
    }

    public function getIdUser(): int
    {
        return $this->idUser;
    }

    public function getIdOrganization(): int
    {
        return $this->idOrganization;
    }
}
