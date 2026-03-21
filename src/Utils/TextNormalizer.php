<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TextNormalizer
{
    /**
     * Normalize text for keyword matching: lowercase, remove accents, strip special chars.
     *
     * @param string $text
     * @return string
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = self::removeAccents($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * Normalize a URL path for keyword matching.
     *
     * @param string $urlPath
     * @return string
     */
    public static function normalizeUrl(string $urlPath): string
    {
        $path = str_replace(['/', '-', '_', '.html', '.php'], ' ', $urlPath);
        return self::normalize($path);
    }

    /**
     * Check if a keyword is found in the given text.
     * Uses exact match first, then partial match (66% of terms).
     *
     * @param string $keyword
     * @param string $text
     * @return bool
     */
    public static function keywordFoundIn(string $keyword, string $text): bool
    {
        $normalizedKeyword = self::normalize($keyword);
        $normalizedText = self::normalize($text);

        if (empty($normalizedKeyword) || empty($normalizedText)) {
            return false;
        }

        if (strpos($normalizedText, $normalizedKeyword) !== false) {
            return true;
        }

        $terms = array_filter(explode(' ', $normalizedKeyword));
        $termCount = count($terms);
        if ($termCount <= 1) {
            return false;
        }

        $minRequired = (int) ceil($termCount * 0.66);
        $found = 0;
        foreach ($terms as $term) {
            if (strpos($normalizedText, $term) !== false) {
                $found++;
            }
        }

        return $found >= $minRequired;
    }

    /**
     * Truncate text to a max length, breaking at word boundary.
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    public static function truncate(string $text, int $maxLength): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated;
    }

    /**
     * Count words in text (after stripping HTML).
     *
     * @param string $html
     * @return int
     */
    public static function countWords(string $html): int
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (empty($text)) {
            return 0;
        }
        return count(array_filter(explode(' ', $text)));
    }

    /**
     * Remove accents from a string. Uses intl if available, otherwise manual map.
     *
     * @param string $text
     * @return string
     */
    private static function removeAccents(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
            if ($result !== false) {
                return $result;
            }
        }

        $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c', 'ß' => 'ss',
            'æ' => 'ae', 'œ' => 'oe',
        ];

        return strtr($text, $map);
    }
}
