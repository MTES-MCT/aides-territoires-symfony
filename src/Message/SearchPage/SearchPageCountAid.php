<?php

namespace App\Message\SearchPage;

class SearchPageCountAid
{
    private int $idSearchPage;

    public function __construct(int $idSearchPage)
    {
        $this->idSearchPage = $idSearchPage;
    }

    public function getIdSearchPage(): int
    {
        return $this->idSearchPage;
    }
}
