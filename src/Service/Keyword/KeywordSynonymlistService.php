<?php

namespace App\Service\Keyword;

use App\Entity\Keyword\KeywordSynonymlist;

class KeywordSynonymlistService
{
    public function getSmartName(KeywordSynonymlist $keywordSynonymlist) : string
    {
        return 'Suggestion : «'.$keywordSynonymlist->getName().'» et ses synonymes';
    }
}
