<?php

namespace App\Service\Reference;

use App\Entity\Reference\KeywordReference;
use App\Repository\Reference\KeywordReferenceRepository;

class KeywordReferenceService
{
    public function __construct(
        private KeywordReferenceRepository $keywordReferenceRepository
    ) {
        
    }

    public function getKeywordReferenceAndSynonyms(?string $keyword): string
    {
        if (!$keyword) {
            return '';
        }

        $keywordReference = $this->keywordReferenceRepository->findOneBy(['name' => $keyword]);

        if (!$keywordReference instanceof KeywordReference) {
            return $keyword;
        }

        $return = $keywordReference->getName();
        foreach ($keywordReference->getKeywordReferences() as $synonym) {
            if ($synonym->getName() !== $keywordReference->getName()) {
                $return .= ', ' . $synonym->getName();
            }
        }

        return $return;
    }
}