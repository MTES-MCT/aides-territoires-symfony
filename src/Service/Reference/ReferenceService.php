<?php

namespace App\Service\Reference;

use App\Entity\Reference\KeywordReference;
use App\Repository\Reference\KeywordReferenceRepository;

class ReferenceService
{

    public function __construct(
        protected KeywordReferenceRepository $keywordReferenceRepository
    )
    {
    }

    public function keywordHasSynonym(KeywordReference $keywordReference, string $synonym): bool
    {
        if ($keywordReference->getName() == $synonym) {
            return true;
        }
        foreach ($keywordReference->getKeywordReferences() as $keywordReferenceChild) {
            if ($keywordReferenceChild->getName() == $synonym) {
                return true;
            }
        }
        return false;
    }

    protected array $articles = array(
        "le", "la", "l'", "les","l’","l' ","l’ ","l’","l'",
        "un", "une", "des", "de",
        "au", "aux","ce",
        "du", "des","d’une","d'une","d’un","d'un","d’ ","d' ","d’","d'","de la","de l'",
        "je","on","nous",
        "à la", "à l'","à",
        "et","en","pour","sur","dans"
    );

    public function getSynonymes(string $project_name): ?array
    {
        $original_name = $project_name;
        $project_name = str_replace(array("/","(",")",",",":","–","-"), " ",strtolower($project_name));
        $projet_keywords_combinaisons=$this->genererCombinaisons($project_name, $this->articles);
        // Tri du tableau pour d'abord chercher des expressions complètes "terrain de football" aura la priorité sur "terrain"
        usort($projet_keywords_combinaisons, array($this,"compareLength"));

        // Affichage du tableau trié
        $intentions = [];
        $objects = [];

        foreach ($projet_keywords_combinaisons as $synonym) {
          $results = $this->keywordReferenceRepository->findCustom([
            'name' => $synonym
          ]);
          /** @var KeywordReference $result */
          foreach ($results as $result) {
            if ($result->isIntention()) {
              $intentions[] = $result->getName();
            } else {
              $objects[] = $result->getName();
            }
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
          }
        }

        // rends les tableaux unique
        $intentions = array_unique($intentions);
        $objects = array_unique($objects);

        // transforme les tableau en string
        $intentions_string = $this->arrayToStringWithQuotes($intentions);
        $objects_string = $this->arrayToStringWithQuotes($objects);
        $simple_words_string = $this->enleverArticles($project_name, $this->articles);

        // retour
        return [
          'intentions_string' => $intentions_string,
          'objects_string' => $objects_string,
          'simple_words_string' => $simple_words_string,
          'original_name' => $original_name,
        ];
    }
    

    public function getHighlightedWords(?string $keyword): array
    {
        $highlightedWords = [];
        if (!$keyword) {
            return $highlightedWords;
        }
        $synonyms = $this->getSynonymes($keyword);
        if (isset($synonyms['simple_words_string'])) {
          $objects = explode(' ', $synonyms['simple_words_string']);
          foreach ($objects as $object) {
            $highlightedWords[] = str_replace(['"'], '', $object);
          }
        }

        return $highlightedWords;
    }

    private function enleverArticles($content, $articles) {
        $content = str_replace(array("/","(",")",",",":","–"), " ",strtolower($content));
        foreach ($articles as $article) {
        $content = preg_replace('/\b' . preg_quote($article, '/') . '\b/u', '', $content);
        }
        return trim(preg_replace('/\s+/', ' ', $content));
    }
    
    private function removeArticle($text, $articles) {
      // Normaliser les apostrophes et passer en minuscules
      $text = str_replace(array("/","(",")",",",":","–"), " ",strtolower($text));
      $text = str_replace(array("‘", "’"), "'", strtolower($text));
  
      $words = explode(" ", $text);
  
      // Vérifier si le premier mot est un article et le supprimer
      if (empty($words) && in_array($words[0], $articles)) {
          array_shift($words);
      }
      // Vérifier si le dernier mot est un article et le supprimer
      if (empty($words) && in_array(end($words), $articles)) {
          array_pop($words);
      }
      return implode(" ", $words);
  }
  
  
  private function genererCombinaisons($texte,$articles) {
    // Séparer le texte en mots
    $mots = explode(" ", $texte);
    $nombreDeMots = count($mots);
    $combinaisons = [];
    // Générer toutes les combinaisons possibles sans mélanger les mots
    for ($i = 0; $i < $nombreDeMots; $i++) {
        for ($j = $i; $j < $nombreDeMots; $j++) {
            $combinaison = implode(" ", array_slice($mots, $i, $j - $i + 1));
            $combinaison=trim($this->removeArticle($combinaison, $articles));
            if ($combinaison) {
              $combinaisons[] = trim($combinaison);
            }
        }
    }
    return array_unique($combinaisons);
  }
  
  
  private function arrayToStringWithQuotes($array) {
      $transformed = array_map(function($item) {
        if (strpos($item, ' ') !== false) {
          return '"' . $item . '"';
        }
        return $item;
      }, $array);
      return implode(' ', $transformed);
  }
  
  private function compareLength($a, $b) {
    return strlen($b) - strlen($a);
  }
}
