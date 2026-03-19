<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

class KeywordCheckObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var int */
    private $pagesWithKeywords = 0;

    /** @var int */
    private $pagesWithoutKeywords = 0;

    /** @var int */
    private $totalKeywordsChecked = 0;

    public function getKey(): string
    {
        return 'keyword_check';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        // Try keywords from our database first, fallback to meta tag
        $dbKeywords = \SeoOptimizerPage::getKeywordsByUrl($url);
        if (!empty($dbKeywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $dbKeywords)));
        } else {
            $keywords = $this->extractMetaKeywords($content);
        }

        if (empty($keywords)) {
            $this->pagesWithoutKeywords++;
            $this->results[$url] = [
                'keywords' => [],
                'has_keywords' => false,
                'zones' => [],
                'checks' => [],
            ];
            return;
        }

        $this->pagesWithKeywords++;

        // Extract all zones
        $zones = $this->extractZones($url, $content);

        $checks = [];
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }

            $this->totalKeywordsChecked++;
            $terms = $this->splitKeywordTerms($keyword);
            $termCount = count($terms);
            $minTermsRequired = $termCount >= 4 ? (int) ceil($termCount * 0.66) : $termCount;

            $keywordCheck = [
                'keyword' => $keyword,
                'zones' => [],
                'score' => 0,
                'max_score' => 0,
            ];

            foreach ($zones as $zoneName => $zoneData) {
                $zoneText = $zoneData['text'];
                $zoneWeight = $zoneData['weight'];
                $found = $this->checkKeywordInZone($keyword, $terms, $minTermsRequired, $zoneText);

                $keywordCheck['zones'][$zoneName] = [
                    'found' => $found,
                    'importance' => $zoneData['importance'],
                    'weight' => $zoneWeight,
                ];

                $keywordCheck['max_score'] += $zoneWeight;
                if ($found) {
                    $keywordCheck['score'] += $zoneWeight;
                }
            }

            $checks[] = $keywordCheck;
        }

        $this->results[$url] = [
            'keywords' => $keywords,
            'has_keywords' => true,
            'zones' => $zones,
            'checks' => $checks,
        ];
    }

    /**
     * @param string $content
     * @return array<string>
     */
    private function extractMetaKeywords(string $content): array
    {
        // Match <meta name="keywords" content="...">
        if (preg_match('/<meta[^>]+name=["\']keywords["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/is', $content, $match)) {
            $raw = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            return array_filter(array_map('trim', explode(',', $raw)));
        }
        // Reversed order
        if (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']keywords["\'][^>]*>/is', $content, $match)) {
            $raw = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            return array_filter(array_map('trim', explode(',', $raw)));
        }

        return [];
    }

    /**
     * @param string $url
     * @param string $content
     * @return array<string, array{text: string, importance: string, weight: int}>
     */
    private function extractZones(string $url, string $content): array
    {
        $zones = [];

        // 1. Meta title (HIGH)
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
        }
        $zones['meta_title'] = ['text' => $title, 'importance' => 'high', 'weight' => 15, 'label' => 'Meta title'];

        // 2. H1 (HIGH)
        $h1 = '';
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $m)) {
            $h1 = trim(strip_tags($m[1]));
        }
        $zones['h1'] = ['text' => $h1, 'importance' => 'high', 'weight' => 15, 'label' => 'H1'];

        // 3. URL (HIGH)
        $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
        $zones['url'] = ['text' => urldecode($urlPath), 'importance' => 'high', 'weight' => 10, 'label' => 'URL'];

        // 4. Meta description (MEDIUM)
        $desc = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/is', $content, $m)) {
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\'][^>]*>/is', $content, $m)) {
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        $zones['meta_description'] = ['text' => $desc, 'importance' => 'medium', 'weight' => 10, 'label' => 'Meta description'];

        // 5. First 100 words of content (MEDIUM)
        $bodyText = $this->extractBodyText($content);
        $words = preg_split('/\s+/', $bodyText);
        $first100 = implode(' ', array_slice($words, 0, 100));
        $zones['content_start'] = ['text' => $first100, 'importance' => 'medium', 'weight' => 10, 'label' => 'First 100 words'];

        // 6. Main image alt (MEDIUM)
        $mainAlt = $this->extractMainImageAlt($content);
        $zones['image_alt'] = ['text' => $mainAlt, 'importance' => 'medium', 'weight' => 10, 'label' => 'Image alt'];

        // 7. Category name from breadcrumb (LOW)
        $categoryName = $this->extractCategoryFromBreadcrumb($content);
        $zones['category'] = ['text' => $categoryName, 'importance' => 'low', 'weight' => 5, 'label' => 'Category'];

        return $zones;
    }

    /**
     * @param string $content
     * @return string
     */
    private function extractBodyText(string $content): string
    {
        $body = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $m)) {
            $body = $m[1];
        }

        // Remove non-content elements
        $body = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $body);
        $body = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
        $body = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $body);
        $body = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $body);
        $body = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $body);

        $text = strip_tags($body);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * @param string $content
     * @return string
     */
    private function extractMainImageAlt(string $content): string
    {
        // Look for product image (common PS classes)
        if (preg_match('/<img[^>]+class=["\'][^"\']*(?:product-cover|js-main-image|product-image)[^"\']*["\'][^>]+alt=["\']([^"\']*)["\'][^>]*>/is', $content, $m)) {
            return trim($m[1]);
        }
        // Reversed: alt before class
        if (preg_match('/<img[^>]+alt=["\']([^"\']*)["\'][^>]+class=["\'][^"\']*(?:product-cover|js-main-image|product-image)[^"\']*["\'][^>]*>/is', $content, $m)) {
            return trim($m[1]);
        }

        // Fallback: first img inside main content area
        $body = $content;
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $content, $m)) {
            $body = $m[1];
        } elseif (preg_match('/<div[^>]+id=["\']content["\'][^>]*>(.*?)<\/div>/is', $content, $m)) {
            $body = $m[1];
        }

        if (preg_match('/<img[^>]+alt=["\']([^"\']+)["\'][^>]*>/is', $body, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * @param string $content
     * @return string
     */
    private function extractCategoryFromBreadcrumb(string $content): string
    {
        // JSON-LD BreadcrumbList
        if (preg_match('/"@type"\s*:\s*"BreadcrumbList"[^}]*"itemListElement"\s*:\s*\[(.*?)\]/is', $content, $m)) {
            if (preg_match_all('/"name"\s*:\s*"([^"]+)"/i', $m[1], $names)) {
                $items = $names[1];
                // Return the second-to-last item (last before the current page)
                if (count($items) >= 2) {
                    return html_entity_decode($items[count($items) - 2], ENT_QUOTES, 'UTF-8');
                }
            }
        }

        // HTML breadcrumb
        if (preg_match('/<nav[^>]+class=["\'][^"\']*breadcrumb[^"\']*["\'][^>]*>(.*?)<\/nav>/is', $content, $m)) {
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $m[1], $links)) {
                $items = array_map('trim', $links[1]);
                if (count($items) >= 2) {
                    return $items[count($items) - 1];
                }
            }
        }

        // ol.breadcrumb (Bootstrap)
        if (preg_match('/<ol[^>]+class=["\'][^"\']*breadcrumb[^"\']*["\'][^>]*>(.*?)<\/ol>/is', $content, $m)) {
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $m[1], $links)) {
                $items = array_map('trim', $links[1]);
                if (count($items) >= 2) {
                    return $items[count($items) - 1];
                }
            }
        }

        return '';
    }

    /**
     * @param string $keyword
     * @return array<string>
     */
    private function splitKeywordTerms(string $keyword): array
    {
        $terms = preg_split('/\s+/', $keyword);

        return array_filter($terms, function ($t) {
            return mb_strlen($t) >= 2;
        });
    }

    /**
     * @param string $keyword
     * @param array $terms
     * @param int $minTermsRequired
     * @param string $text
     * @return bool
     */
    private function checkKeywordInZone(string $keyword, array $terms, int $minTermsRequired, string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $normalizedText = $this->normalize($text);
        $normalizedKeyword = $this->normalize($keyword);

        // Exact match
        if (strpos($normalizedText, $normalizedKeyword) !== false) {
            return true;
        }

        // Partial match: check how many terms are present
        $foundTerms = 0;
        foreach ($terms as $term) {
            $normalizedTerm = $this->normalize($term);
            if (mb_strlen($normalizedTerm) < 2) {
                continue;
            }
            if (strpos($normalizedText, $normalizedTerm) !== false) {
                $foundTerms++;
            }
        }

        return $foundTerms >= $minTermsRequired;
    }

    /**
     * @param string $string
     * @return string
     */
    private function normalize(string $string): string
    {
        $string = mb_strtolower($string);
        $string = $this->removeAccents($string);
        $string = preg_replace('/[^a-z0-9\s]/', ' ', $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }

    /**
     * @param string $string
     * @return string
     */
    private function removeAccents(string $string): string
    {
        $transliterations = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ÿ' => 'y', 'ý' => 'y',
            'æ' => 'ae', 'œ' => 'oe',
        ];

        return strtr($string, $transliterations);
    }

    /**
     * @return int
     */
    public function getPagesWithKeywords(): int
    {
        return $this->pagesWithKeywords;
    }

    /**
     * @return int
     */
    public function getPagesWithoutKeywords(): int
    {
        return $this->pagesWithoutKeywords;
    }

    /**
     * @return int
     */
    public function getTotalKeywordsChecked(): int
    {
        return $this->totalKeywordsChecked;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
