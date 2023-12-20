<?php

namespace App\Entity\Aid;

use App\Entity\Keyword\KeywordSynonymlist;
use App\Entity\Organization\OrganizationType;
use App\Entity\Perimeter\Perimeter;
use App\Repository\Aid\AidDestinationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

class AidSearch
{
    public ?OrganizationType $targeted_audiences;
    public ?Perimeter $perimeter;
    public ?KeywordSynonymlist $keyword;
    public ?array $categorysearch;
    public ?int $integration;

    public function getKeyword()
    {
        return $this->keyword;
    }

}
