<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\TextNormalizer;

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
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        // Try keywords from our database first, fallback to meta tag
        $dbKeywords = \SeoOptimizerPage::getKeywordsByUrl($url);
        if (!empty($dbKeywords)) {
            $keywords = array_filter(array_map('trim', explode(',', $dbKeywords)));
        } else {
            $rawKeywords = $extractor->extractMetaKeywords();
            $keywords = !empty($rawKeywords)
                ? array_filter(array_map('trim', explode(',', $rawKeywords)))
                : [];
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
        $zones = $this->extractZones($url, $content, $extractor);

        $checks = [];
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (empty($keyword)) {
                continue;
            }

            $this->totalKeywordsChecked++;

            $keywordCheck = [
                'keyword' => $keyword,
                'zones' => [],
                'score' => 0,
                'max_score' => 0,
            ];

            foreach ($zones as $zoneName => $zoneData) {
                $zoneText = $zoneData['text'];
                $zoneWeight = $zoneData['weight'];
                $found = TextNormalizer::keywordFoundIn($keyword, $zoneText);

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
     * @param string $url
     * @param string $content
     * @param HTMLExtractor $extractor
     * @return array<string, array{text: string, importance: string, weight: int}>
     */
    private function extractZones(string $url, string $content, HTMLExtractor $extractor): array
    {
        $zones = [];

        // 1. Meta title (HIGH)
        $title = $extractor->extractTitle();
        $zones['meta_title'] = ['text' => $title, 'importance' => 'high', 'weight' => 15, 'label' => 'Meta title'];

        // 2. H1 (HIGH)
        $h1 = '';
        $headings = $extractor->extractHeadings();
        foreach ($headings as $heading) {
            if ($heading['level'] === 1) {
                $h1 = $heading['text'];
                break;
            }
        }
        $zones['h1'] = ['text' => $h1, 'importance' => 'high', 'weight' => 15, 'label' => 'H1'];

        // 3. URL (HIGH)
        $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
        $zones['url'] = ['text' => urldecode($urlPath), 'importance' => 'high', 'weight' => 10, 'label' => 'URL'];

        // 4. Meta description (MEDIUM)
        $desc = $extractor->extractMetaDescription();
        $zones['meta_description'] = ['text' => $desc, 'importance' => 'medium', 'weight' => 10, 'label' => 'Meta description'];

        // 5. First 100 words of content (MEDIUM)
        $bodyText = $extractor->extractBodyText();
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
