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

        if (!$keywordReference->getParent()) {
            return $keywordReference->getName();
        }

        $return = $keywordReference->getParent()->getName();
        foreach ($keywordReference->getParent()->getKeywordReferences() as $synonym) {
            if ($synonym->getName() !== $keywordReference->getParent()->getName()) {
                $return .= ', ' . $synonym->getName();
            }
        }

        return $return;
    }
}
