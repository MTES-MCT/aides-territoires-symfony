<?php

namespace App\Service\Reference;

use App\Entity\Reference\KeywordReference;
use App\Entity\Reference\ProjectReference;
use App\Repository\Reference\KeywordReferenceRepository;
use App\Repository\Reference\ProjectReferenceRepository;
use Doctrine\Inflector\InflectorFactory;

class ReferenceService
{

    public function __construct(
        private KeywordReferenceRepository $keywordReferenceRepository,
        private ProjectReferenceRepository $projectReferenceRepository
    )
    {
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
      // nettoie la string
      $project_name = str_replace(array("/","(",")",",",":","–","-"), " ",strtolower($project_name));

      // regarde si c'est un projet référent
      $projectReference = $this->projectReferenceRepository->findOneBy([
          'name' => $project_name
      ]);

      // sépare sur les virgules
      $keywords = explode(',', $project_name);
      foreach ($keywords as $key => $keyword) {
        $keywords[$key] = trim($keyword);
        if (empty($keywords[$key])) {
          unset($keywords[$key]);
        }
      }

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

      // on regarde si c'est un mot clé de la base de données
      $keywordReferences = $this->keywordReferenceRepository->findCustom([
          'names' => $keywords
      ]);
      $keywordReferencesByName = [];

    
      foreach ($keywordReferences as $keywordReference) {
        // retire de keywords les mots clés trouvés
        $key = array_search($keywordReference->getName(), $keywords);
        if ($key !== false) {
          unset($keywords[$key]);
        }
        // stock dans un tableau
        $keywordReferencesByName[$keywordReference->getName()] = $keywordReference;
      }

        // genere les combinaisons possibles avec les termes restants
        $keywords = $this->enleverArticlesFromArray($keywords);
        $projetKeywordsCombinaisons = $this->genererToutesCombinaisons($keywords);
  
        // regarde si on trouve d'autre mots clés référents
        $keywordReferencesBis = $this->keywordReferenceRepository->findCustom([
          'names' => $projetKeywordsCombinaisons
        ]);
        foreach ($keywordReferencesBis as $keywordReference) {
          // retire des combinaisons les mots clés trouvés
          $key = array_search($keywordReference->getName(), $projetKeywordsCombinaisons);
          if ($key !== false) {
            unset($projetKeywordsCombinaisons[$key]);
          }
          // ajoute au tableau
          if (!isset($keywordReferencesByName[$keywordReference->getName()])) {
            $keywordReferencesByName[$keywordReference->getName()] = $keywordReference;
            $keywordReferences[] = $keywordReference;
          }
        }
      

      // Prépare deux tableaux pour les intentions et les objets
      $intentions = [];
      $objects = [];

      // on fait un tableau avec les mots clés exlus
      $excludedKeywordReferences = [];
      if ($projectReference instanceof ProjectReference) {
        foreach ($projectReference->getExcludedKeywordReferences() as $excludedKeywordReference) {
          $excludedKeywordReferences[] = $excludedKeywordReference->getName();

          // on retire du tableau des mots clés trouvés
          if (isset($keywordReferencesByName[$excludedKeywordReference->getName()])) {
            unset($keywordReferencesByName[$excludedKeywordReference->getName()]);
          }
        }
      }

      // on enlève les mots exclus du projet rérérent le cas échéant
      foreach ($projetKeywordsCombinaisons as $key => $synonym) {
        if (in_array($synonym, $excludedKeywordReferences)) {
          unset($projetKeywordsCombinaisons[$key]);
        }
      }

      // parcours les mots clés
      foreach ($keywordReferences as $result) {
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

          // si il a un parent
          if ($result->getParent()) {
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

      

      // Parcours les combinaisons de mots
      foreach ($projetKeywordsCombinaisons as $synonym) {
        $results = $this->keywordReferenceRepository->findCustom([
          'name' => $synonym
        ]);
        /** @var KeywordReference $result */
        foreach ($results as $result) {
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

          // si il a un parent
          if ($result->getParent()) {
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

    private function enleverArticlesFromArray(array $array): array
    {
      foreach($array as $key => $value) {
        $value = str_replace(array("/","(",")",",",":","–"), " ",strtolower($value));
        if (in_array($value, $this->articles)) {
          unset($array[$key]);
        } else {
          $array[$key] = $value;
        }
      }

      return $array;
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
      if (!empty($words) && in_array($words[0], $articles)) {
          array_shift($words);
      }
      // Vérifier si le dernier mot est un article et le supprimer
      if (!empty($words) && in_array(end($words), $articles)) {
          array_pop($words);
      }
      return implode(" ", $words);
  }
  
  private function genererToutesCombinaisons($keywords) {
    if (empty($keywords)) {
        return [''];
    }

    $premierMot = array_shift($keywords);
    $combinaisonsSansPremier = $this->genererToutesCombinaisons($keywords);
    $combinaisonsAvecPremier = [];

    foreach ($combinaisonsSansPremier as $combinaison) {
        $combinaisonsAvecPremier[] = $combinaison;
        if (!empty($combinaison)) {
            $combinaisonsAvecPremier[] = $premierMot . ' ' . $combinaison;
        } else {
            $combinaisonsAvecPremier[] = $premierMot;
        }
    }

    return $combinaisonsAvecPremier;
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
