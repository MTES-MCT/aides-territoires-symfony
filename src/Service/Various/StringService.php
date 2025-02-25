<?php

namespace App\Service\Various;

class StringService
{
    /**
     * Nettoie une chaine de caractères pour une recherche booléenne (MATCH_AGAINST)
     *
     * @param string $string
     * @return string
     */
    public function sanitizeBooleanSearch(string $string): string
    {
        // Limite la longueur de la chaîne
        $string = substr($string, 0, 255);

        // Supprime les caractères dangereux pour SQL
        $string = str_replace(['=', '<>', '>=', '<=', 'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', ';', '--'], ' ', $string);

        // Conserve certains caractères spéciaux légitimes mais retire ceux qui pourraient être dangereux
        $string = preg_replace('/[^\p{L}\p{N}\s\-\+\@\.\,\']/u', ' ', $string);

        // Supprime les espaces multiples
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }

    /**
     * Nettoie une chaine de caractères
     *
     * @param string $string
     * @return string
     */
    public function cleanString(string $string): string
    {
        return trim(strip_tags($string));
    }

    public function truncate(string $text, int $length): string
    {
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $parts_count = count($parts);

        $lengthCalcul = 0;
        $last_part = 0;
        for (; $last_part < $parts_count; ++$last_part) {
            $lengthCalcul += strlen($parts[$last_part]);
            if ($lengthCalcul > $length) {
                break;
            }
        }

        $return = implode(array_slice($parts, 0, $last_part));
        if (strlen($text) > $length) {
            $return = substr($return, 0, $length - 3) . '...';
        }

        return $return;
    }

    /**
     * Fonction qui retourne un nom nettoyé (sans accent, espace, etc...)
     */
    public function getSlug(string $string, string $replacement = '-'): string
    {
        $string = strtolower(self::getNoAccent($string));

        // retire tout ce qui n'est ni chiffres ni lettres
        $pattern = '/[^a-z0-9]/';

        $string = preg_replace($pattern, $replacement, $string);

        while (substr($string, -1) == '-') {
            $string = substr($string, 0, strlen($string) - 1);
        }
        $string = preg_replace($pattern, $replacement, $string);

        $pattern = '/-+/i';
        $replacement = '-';

        return preg_replace($pattern, $replacement, $string);
    }

    /**
     * Fonction qui retire les accents
     * @param String $string, chaine à nettoyer
     * @return String
     * @author RB 2012_05
     */
    public function getNoAccent(string $string): string
    {
        $a = [
            'À',
            'Á',
            'Â',
            'Ã',
            'Ä',
            'Å',
            'Æ',
            'Ç',
            'È',
            'É',
            'Ê',
            'Ë',
            'Ì',
            'Í',
            'Î',
            'Ï',
            'Ð',
            'Ñ',
            'Ò',
            'Ó',
            'Ô',
            'Õ',
            'Ö',
            'Ø',
            'Ù',
            'Ú',
            'Û',
            'Ü',
            'Ý',
            'ß',
            'à',
            'á',
            'â',
            'ã',
            'ä',
            'å',
            'æ',
            'ç',
            'è',
            'é',
            'ê',
            'ë',
            'ì',
            'í',
            'î',
            'ï',
            'ñ',
            'ò',
            'ó',
            'ô',
            'õ',
            'ö',
            'ø',
            'ù',
            'ú',
            'û',
            'ü',
            'ý',
            'ÿ',
            'Ā',
            'ā',
            'Ă',
            'ă',
            'Ą',
            'ą',
            'Ć',
            'ć',
            'Ĉ',
            'ĉ',
            'Ċ',
            'ċ',
            'Č',
            'č',
            'Ď',
            'ď',
            'Đ',
            'đ',
            'Ē',
            'ē',
            'Ĕ',
            'ĕ',
            'Ė',
            'ė',
            'Ę',
            'ę',
            'Ě',
            'ě',
            'Ĝ',
            'ĝ',
            'Ğ',
            'ğ',
            'Ġ',
            'ġ',
            'Ģ',
            'ģ',
            'Ĥ',
            'ĥ',
            'Ħ',
            'ħ',
            'Ĩ',
            'ĩ',
            'Ī',
            'ī',
            'Ĭ',
            'ĭ',
            'Į',
            'į',
            'İ',
            'ı',
            'Ĳ',
            'ĳ',
            'Ĵ',
            'ĵ',
            'Ķ',
            'ķ',
            'Ĺ',
            'ĺ',
            'Ļ',
            'ļ',
            'Ľ',
            'ľ',
            'Ŀ',
            'ŀ',
            'Ł',
            'ł',
            'Ń',
            'ń',
            'Ņ',
            'ņ',
            'Ň',
            'ň',
            'ŉ',
            'Ō',
            'ō',
            'Ŏ',
            'ŏ',
            'Ő',
            'ő',
            'Œ',
            'œ',
            'Ŕ',
            'ŕ',
            'Ŗ',
            'ŗ',
            'Ř',
            'ř',
            'Ś',
            'ś',
            'Ŝ',
            'ŝ',
            'Ş',
            'ş',
            'Š',
            'š',
            'Ţ',
            'ţ',
            'Ť',
            'ť',
            'Ŧ',
            'ŧ',
            'Ũ',
            'ũ',
            'Ū',
            'ū',
            'Ŭ',
            'ŭ',
            'Ů',
            'ů',
            'Ű',
            'ű',
            'Ų',
            'ų',
            'Ŵ',
            'ŵ',
            'Ŷ',
            'ŷ',
            'Ÿ',
            'Ź',
            'ź',
            'Ż',
            'ż',
            'Ž',
            'ž',
            'ſ',
            'ƒ',
            'Ơ',
            'ơ',
            'Ư',
            'ư',
            'Ǎ',
            'ǎ',
            'Ǐ',
            'ǐ',
            'Ǒ',
            'ǒ',
            'Ǔ',
            'ǔ',
            'Ǖ',
            'ǖ',
            'Ǘ',
            'ǘ',
            'Ǚ',
            'ǚ',
            'Ǜ',
            'ǜ',
            'Ǻ',
            'ǻ',
            'Ǽ',
            'ǽ',
            'Ǿ',
            'ǿ'
        ];
        $b = [
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'AE',
            'C',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'D',
            'N',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'Y',
            's',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'ae',
            'c',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'n',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'A',
            'a',
            'A',
            'a',
            'A',
            'a',
            'C',
            'c',
            'C',
            'c',
            'C',
            'c',
            'C',
            'c',
            'D',
            'd',
            'D',
            'd',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'G',
            'g',
            'G',
            'g',
            'G',
            'g',
            'G',
            'g',
            'H',
            'h',
            'H',
            'h',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'IJ',
            'ij',
            'J',
            'j',
            'K',
            'k',
            'L',
            'l',
            'L',
            'l',
            'L',
            'l',
            'L',
            'l',
            'l',
            'l',
            'N',
            'n',
            'N',
            'n',
            'N',
            'n',
            'n',
            'O',
            'o',
            'O',
            'o',
            'O',
            'o',
            'OE',
            'oe',
            'R',
            'r',
            'R',
            'r',
            'R',
            'r',
            'S',
            's',
            'S',
            's',
            'S',
            's',
            'S',
            's',
            'T',
            't',
            'T',
            't',
            'T',
            't',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'W',
            'w',
            'Y',
            'y',
            'Y',
            'Z',
            'z',
            'Z',
            'z',
            'Z',
            'z',
            's',
            'f',
            'O',
            'o',
            'U',
            'u',
            'A',
            'a',
            'I',
            'i',
            'O',
            'o',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'A',
            'a',
            'AE',
            'ae',
            'O',
            'o'
        ];
        $string = strtr($string, array_combine($a, $b));
        return $string;
    }

    /**
     * Normalize a string for comparison
     *
     * @param string $string
     * @return string
     */
    public function normalizeString(string $string): string
    {
        $string = strtolower($string);

        // Remplace les caractères accentués par leurs équivalents non accentués
        $unwanted_array = [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            'œ' => 'oe'
        ];
        $string = strtr($string, $unwanted_array);

        // Retire les espaces, tirets, et autres caractères non alphabétiques
        $string = preg_replace('/[^a-z0-9]/', '', $string);

        return $string;
    }

    /**
     * Parcourt un tableau et filtre les éléments invalides ou vides.
     * Force les éléments à être des entiers et retire les éléments égaux à 0.
     *
     * @param array<int|string> $array Le tableau à traiter
     * @return array<int> Le tableau filtré et forcé à être des entiers
     */
    public function forceElementsToInt(array $array): array
    {
        return array_filter(array_map(function ($value) {
            // Extraire l'entier de la chaîne de caractères
            if (preg_match('/\d+/', $value, $matches)) {
                $id = filter_var($matches[0], FILTER_VALIDATE_INT);
            } else {
                $id = filter_var($value, FILTER_VALIDATE_INT);
            }

            // Retirer les éléments invalides ou égaux à 0
            return $id !== false && $id !== 0 ? $id : null;
        }, $array));
    }

    /**
     * Parcourt un tableau et force les éléments en chaîne de caractères.
     * Retire les éléments invalides ou vides.
     *
     * @param array<int|string> $array Le tableau à traiter
     * @return array<string> Le tableau avec les éléments forcés en chaîne de caractères
     */
    public function forceElementsToString(array $array): array
    {
        $result = [];

        foreach ($array as $element) {
            // Vérifier si l'élément est valide et non vide
            if (!empty($element)) {
                // Forcer l'élément à être une chaîne de caractères
                $stringElement = (string) $element;

                // Ajouter l'élément converti au résultat
                $result[] = $stringElement;
            }
        }

        return $result;
    }
}
