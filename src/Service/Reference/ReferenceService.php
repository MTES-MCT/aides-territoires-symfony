<?php

namespace App\Service\Reference;

use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use Symfony\Component\HttpFoundation\Session\Session;

class ReferenceService
{
    public function __construct(
        private KeywordReferenceRepository $keywordReferenceRepository,
        private ProjectReferenceRepository $projectReferenceRepository,
    ) {
    }

    public function keywordHasSynonym(KeywordReference $keywordReference, string $synonym): bool
    {
        if (strtolower($keywordReference->getName()) == strtolower($synonym)) {
            return true;
        }
        foreach ($keywordReference->getKeywordReferences() as $keywordReferenceChild) {
            if (strtolower($keywordReferenceChild->getName()) == strtolower($synonym)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @var string[]
     */
    protected array $articles = [
        'le',
        'la',
        "l'",
        'les',
        'l’',
        "l' ",
        'l’ ',
        'l’',
        "l'",
        'un',
        'une',
        'des',
        'de',
        'au',
        'aux',
        'ce',
        'du',
        'des',
        'd’une',
        "d'une",
        'd’un',
        "d'un",
        'd’ ',
        "d' ",
        'd’',
        "d'",
        'de la',
        "de l'",
        'je',
        'on',
        'nous',
        'à la',
        "à l'",
        'à',
        'et',
        'en',
        'pour',
        'sur',
        'dans',
    ];

    /**
     * @return array<string, string>
     */
    public function getSynonymes(string $project_name): ?array
    {
        $original_name = $project_name;

        // regarde si c'est un projet référent
        $projectReference = $this->projectReferenceRepository->findOneBy([
            'name' => $project_name,
        ]);

        // nettoie la string
        $project_name = str_replace(['/', '(', ')', ',', ':', '–', '-'], ' ', strtolower($project_name));

        // sépare sur les espaces
        $keywords = explode(' ', $project_name);
        foreach ($keywords as $key => $keyword) {
            $keywords[$key] = trim($keyword);
            if (empty($keywords[$key])) {
                unset($keywords[$key]);
            }
        }

        // rend le tableau unique
        $keywords = array_unique($keywords);

        // genere les combinaisons possibles avec les termes restants et les ajoutes au tableau
        $keywords = $this->enleverArticlesFromArray($keywords);
        $keywords = array_merge($keywords, $this->genererToutesCombinaisons($keywords));
        // retire tous les élements vides
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        // Prépare deux tableaux pour les intentions et les objets
        $intentions = [];
        $objects = [];

        // on fait un tableau avec les mots clés exlus
        $excludedKeywordReferenceNames = [];
        if ($projectReference instanceof ProjectReference) {
            foreach ($projectReference->getExcludedKeywordReferences() as $excludedKeywordReference) {
                $excludedKeywordReferenceNames[] = $excludedKeywordReference->getName();
                foreach ($excludedKeywordReference->getKeywordReferences() as $subKeyword) {
                    $excludedKeywordReferenceNames[] = $subKeyword->getName();
                }
                if (
                    $excludedKeywordReference->getParent()
                    && $excludedKeywordReference->getParent()->getId() !== $excludedKeywordReference->getId()
                ) {
                    $excludedKeywordReferenceNames[] = $excludedKeywordReference->getParent()->getName();
                    foreach ($excludedKeywordReference->getParent()->getKeywordReferences() as $subKeyword) {
                        $excludedKeywordReferenceNames[] = $subKeyword->getName();
                    }
                }
            }
        }
        $excludedKeywordReferenceNames = array_unique($excludedKeywordReferenceNames);

        // on regarde si ça corresponds à des mots clés de la base de données
        $keywordReferences = $this->keywordReferenceRepository->findFromKewyordsOrOriginalName(
            $keywords,
            $original_name
        );

        // parcours les mots clés restant
        foreach ($keywordReferences as $key => $result) {
            // si dans la liste d'exclusion
            if (in_array($result->getName(), $excludedKeywordReferenceNames)) {
                unset($keywordReferences[$key]);
                continue;
            }

            // ajoute le mot
            if ($result->isIntention()) {
                $intentions[] = $result->getName();
            } else {
                $objects[] = $result->getName();
            }

            // si il a des enfants
            foreach ($result->getKeywordReferences() as $keywordReference) {
                if ($keywordReference->isIntention()) {
                    $intentions[] = $keywordReference->getName();
                } else {
                    $objects[] = $keywordReference->getName();
                }
                foreach ($keywordReference->getKeywordReferences() as $subKeyword) {
                    if ($subKeyword->isIntention()) {
                        $intentions[] = $subKeyword->getName();
                    } else {
                        $objects[] = $subKeyword->getName();
                    }
                }
            }

            // si il a un parent qui n'est pas lui même
            if ($result->getParent() && $result->getParent()->getId() !== $result->getId()) {
                if ($result->getParent()->isIntention()) {
                    $intentions[] = $result->getParent()->getName();
                } else {
                    $objects[] = $result->getParent()->getName();
                }
                foreach ($result->getParent()->getKeywordReferences() as $subKeyword) {
                    if ($subKeyword->isIntention()) {
                        $intentions[] = $subKeyword->getName();
                    } else {
                        $objects[] = $subKeyword->getName();
                    }
                }
            }
        }

        // // optimisation a valider
        // $keywordReferences = $this->keywordReferenceRepository->findArrayOfAllSynonyms([
        //   'names' => $keywords,
        //   'excludeds' => $excludedKeywordReferenceNames
        // ]);
        // foreach ($keywordReferences as $key => $result) {
        //   // si dans la liste d'exclusion
        //   if (in_array($result['name'], $excludedKeywordReferenceNames)) {
        //     unset($keywordReferences[$key]);
        //     continue;
        //   }

        //   // ajoute le mot
        //   if ($result['intention']) {
        //     $intentions[] = $result['name'];
        //   } else {
        //     $objects[] = $result['name'];
        //   }
        // }

        // rends les tableaux unique
        $intentions = array_unique($intentions);
        $objects = array_unique($objects);

        // transforme les tableau en string
        $intentions_string = $this->arrayToStringWithQuotes($intentions);
        $objects_string = $this->arrayToStringWithQuotes($objects);
        $simple_words_string = $this->enleverArticles($project_name, $this->articles);

        // recupere le tableau de toutes les intentions
        $intentionNames = $this->keywordReferenceRepository->findIntentionNames();

        // on retire les intentions de $simple_words_string
        foreach ($intentionNames as $intentionName) {
            $pattern = '/(?<=\s|^)' . preg_quote($intentionName, '/') . '(?=\s|$)/';
            $simple_words_string = preg_replace($pattern, ' ', $simple_words_string);
        }

        // Supprimer les espaces supplémentaires
        $simple_words_string = preg_replace('/\s+/', ' ', trim($simple_words_string));

        // retour
        return [
            'intentions_string' => $intentions_string,
            'objects_string' => $objects_string,
            'simple_words_string' => $simple_words_string,
            'original_name' => $original_name,
        ];
    }

    public function setHighlightedWords(?array $synonyms, ?string $currentKeyword): array
    {
        $highlightedWords = $this->getHighlightedWords($synonyms, $currentKeyword);

        $session = new Session();
        $session->set('highlightedWords', $highlightedWords);

        return $highlightedWords;
    }

    /**
     * @return string[]
     */
    public function getHighlightedWords(?array $synonyms, ?string $currentKeyword): array
    {
        $session = new Session();
        $highlightedWords = $session->get('highlightedWords', []);

        if (isset($synonyms['intentions_string']) && isset($synonyms['objects_string'])) {
            $keywords = str_getcsv($synonyms['intentions_string'], ' ', '"');
            foreach ($keywords as $keyword) {
                if ($keyword && '' !== trim($keyword)) {
                    $highlightedWords[] = $keyword;
                }
            }
        }
        if (isset($synonyms['objects_string'])) {
            $keywords = str_getcsv($synonyms['objects_string'], ' ', '"');
            foreach ($keywords as $keyword) {
                if ($keyword && '' !== trim($keyword)) {
                    $highlightedWords[] = $keyword;
                }
            }
        }
        if (
            isset($synonyms['simple_words_string'])
            && (
                !isset($synonyms['objects_string'])
                || '' == $synonyms['objects_string']
            )
        ) {
            $keywords = str_getcsv($synonyms['simple_words_string'], ' ', '"');
            foreach ($keywords as $keyword) {
                if ($keyword && '' !== trim($keyword)) {
                    $highlightedWords[] = $keyword;
                }
            }
        }

        // si la gestion des synonymes n'a pas fonctionné, on met directement la recherche
        if (empty($highlightedWords) && $currentKeyword) {
            // on met la recherche dans les highlights
            $keywords = explode(' ', $currentKeyword);
            foreach ($keywords as $keyword) {
                if ($keyword && '' !== trim($keyword) && strlen($keyword) > 2) {
                    $highlightedWords[] = $keyword;
                }
            }
        }

        return $highlightedWords;
    }

    /**
     * @param string[] $array
     *
     * @return string[]
     */
    private function enleverArticlesFromArray(array $array): array
    {
        foreach ($array as $key => $value) {
            $value = str_replace(['/', '(', ')', ',', ':', '–'], ' ', strtolower($value));
            if (in_array($value, $this->articles)) {
                unset($array[$key]);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param string[] $articles
     */
    private function enleverArticles(string $content, array $articles): string
    {
        $content = str_replace(['/', '(', ')', ',', ':', '–'], ' ', strtolower($content));
        foreach ($articles as $article) {
            $content = preg_replace('/\b' . preg_quote($article, '/') . '\b/u', '', $content);
        }

        return trim(preg_replace('/\s+/', ' ', $content));
    }

    /**
     * combinaisons de mots.
     *
     * @param string[] $keywords
     *
     * @return string[]
     */
    private function genererToutesCombinaisons(array $keywords): array
    {
        sort($keywords);
        $combinaisons = [];
        $nombreDeMots = count($keywords);

        // Si aucun mot ou un seul mot est fourni, retourne les mots tels quels.
        if ($nombreDeMots <= 1) {
            return $keywords;
        }

        for ($i = 0; $i < $nombreDeMots; ++$i) {
            for ($j = 0; $j < $nombreDeMots; ++$j) {
                if ($i != $j) {
                    // Ajoute la combinaison du mot actuel avec chaque autre mot.
                    $combinaisons[] = $keywords[$i] . ' ' . $keywords[$j];
                }
            }
        }

        return $combinaisons;
    }

    /**
     * @param string[] $array
     */
    private function arrayToStringWithQuotes(array $array): string
    {
        $transformed = array_map(function ($item) {
            if (false !== strpos($item, ' ')) {
                return '"' . $item . '"';
            }

            return $item;
        }, $array);

        return implode(' ', $transformed);
    }
}
