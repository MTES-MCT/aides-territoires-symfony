<?php

namespace App\Service\Reference;

use App\Repository\Reference\KeywordReferenceRepository;

class KeywordReferenceService
{
    public function __construct(
        private KeywordReferenceRepository $keywordReferenceRepository
    ) {}

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

    public function getKeywordReferenceAndSynonymsPlit(?string $keyword): array
    {
        $intentions = [];
        $objects = [];

        if (!$keyword) {
            return [
                'intentions' => $intentions,
                'objects' => $objects,
                'keyword' => $keyword
            ];
        }

        // on recupère tous les mots clés correspondants
        $keywordReferences = $this->keywordReferenceRepository->findBy(['name' => $keyword]);
        // ça ne correspons à aucun mot clé
        if (empty($keywordReferences)) {
            return [
                'intentions' => $intentions,
                'objects' => $objects,
                'keyword' => $keyword
            ];
        }

        foreach ($keywordReferences as $keywordReference) {
            if (!$keywordReference->getParent()) {
                if ($keywordReference->isIntention()) {
                    $intentions[] = $keywordReference->getName();
                } else {
                    $objects[] = $keywordReference->getName();
                }
                continue;
            }

            if ($keywordReference->getParent()->isIntention()) {
                $intentions[] = $keywordReference->getParent()->getName();
            } else {
                $objects[] = $keywordReference->getParent()->getName();
            }

            foreach ($keywordReference->getParent()->getKeywordReferences() as $synonym) {
                if ($synonym->getName() !== $keywordReference->getParent()->getName()) {
                    if ($synonym->isIntention()) {
                        $intentions[] = $synonym->getName();
                    } else {
                        $objects[] = $synonym->getName();
                    }
                }
            }
        }

        // rends les tableaux unique
        $intentions = array_unique($intentions);
        $objects = array_unique($objects);

        return [
            'intentions' => $intentions,
            'objects' => $objects,
            'keyword' => $keyword
        ];
    }
}
