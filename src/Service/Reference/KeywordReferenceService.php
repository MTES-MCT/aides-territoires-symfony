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

        // on recupère tous les mots clés correspondants
        $keywordReferences = $this->keywordReferenceRepository->findBy(['name' => $keyword]);
        // ça ne correspons à aucun mot clé
        if (empty($keywordReferences)) {
            return $keyword;
        }

        $return = [];
        foreach ($keywordReferences as $keywordReference) {
            if (!$keywordReference->getParent()) {
                $return[] = $keywordReference->getName();
                continue;
            }

            $return[] = $keywordReference->getParent()->getName();
            foreach ($keywordReference->getParent()->getKeywordReferences() as $synonym) {
                if ($synonym->getName() !== $keywordReference->getParent()->getName()) {
                    $return[] = $synonym->getName();
                }
            }
        }

        $return = array_unique($return);
        return join(', ', $return);
    }
}
