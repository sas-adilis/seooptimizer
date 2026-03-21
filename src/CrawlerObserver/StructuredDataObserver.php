<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;

class StructuredDataObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    public function getKey(): string
    {
        return 'structured_data';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);

        $jsonLdItems = $extractor->extractJsonLd();
        $isProduct = $extractor->isProductPage();

        // Collect all @type values with their count
        $typeCounts = [];
        foreach ($jsonLdItems as $item) {
            if (!isset($item['@type'])) {
                continue;
            }
            $itemTypes = is_array($item['@type']) ? $item['@type'] : [$item['@type']];
            foreach ($itemTypes as $type) {
                if (!isset($typeCounts[$type])) {
                    $typeCounts[$type] = 0;
                }
                $typeCounts[$type]++;
            }
        }

        // Expected types depend on page type
        $expectedTypes = $isProduct
            ? ['Product', 'BreadcrumbList', 'Offer', 'Organization', 'WebPage']
            : ['BreadcrumbList', 'Organization', 'WebPage'];

        $issues = [];
        $schemas = [];

        // Check expected types
        foreach ($expectedTypes as $type) {
            $found = isset($typeCounts[$type]);
            $schemas[] = ['name' => $type, 'found' => $found];

            if (!$found) {
                $severity = $this->getMissingSeverity($type, $isProduct);
                $issues[] = [
                    'type' => 'missing_schema',
                    'severity' => $severity,
                    'message' => sprintf('Schema %s missing', $type),
                    'schema_type' => $type,
                ];
            }
        }

        // Add found types not in expected list
        foreach ($typeCounts as $type => $count) {
            if (!in_array($type, $expectedTypes, true)) {
                $schemas[] = ['name' => $type, 'found' => true];
            }
        }

        // Check for duplicates
        foreach ($typeCounts as $type => $count) {
            if ($count > 1) {
                $issues[] = [
                    'type' => 'duplicate_schema',
                    'severity' => 'warning',
                    'message' => sprintf('Schema %s declared %d times', $type, $count),
                    'schema_type' => $type,
                    'count' => $count,
                ];
            }
        }

        // Product-specific checks
        if ($isProduct) {
            if (isset($typeCounts['Product']) && !isset($typeCounts['Offer'])) {
                $issues[] = [
                    'type' => 'product_no_offer',
                    'severity' => 'warning',
                    'message' => 'Product schema without Offer — price won\'t show in results',
                    'schema_type' => 'Offer',
                ];
            }

            if (!isset($typeCounts['AggregateRating']) && preg_match('/avis|review|note|rating/i', $content)) {
                $schemas[] = ['name' => 'AggregateRating', 'found' => false];
                $issues[] = [
                    'type' => 'reviews_not_structured',
                    'severity' => 'warning',
                    'message' => 'Reviews detected but no AggregateRating schema',
                    'schema_type' => 'AggregateRating',
                ];
            } elseif (isset($typeCounts['AggregateRating'])) {
                $schemas[] = ['name' => 'AggregateRating', 'found' => true];
            }
        }

        // FAQ bonus check
        if (!isset($typeCounts['FAQPage'])) {
            $issues[] = [
                'type' => 'no_faq',
                'severity' => 'info',
                'message' => 'No FAQ schema — add FAQ to improve AI search visibility',
                'schema_type' => 'FAQPage',
            ];
        }

        // JSON-LD validation: check for malformed blocks
        $scripts = [];
        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $content, $matches)) {
            $scripts = $matches[1];
        }
        $malformedCount = 0;
        foreach ($scripts as $raw) {
            $decoded = json_decode(trim($raw), true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                $malformedCount++;
            }
        }
        if ($malformedCount > 0) {
            $issues[] = [
                'type' => 'malformed_jsonld',
                'severity' => 'critical',
                'message' => sprintf('%d malformed JSON-LD block(s)', $malformedCount),
                'schema_type' => '',
            ];
        }

        $this->results[$url] = [
            'schemas' => $schemas,
            'type_counts' => $typeCounts,
            'found_types' => array_keys($typeCounts),
            'total_types' => count($typeCounts),
            'total_blocks' => count($jsonLdItems),
            'is_product' => $isProduct,
            'issues' => $issues,
            'duplicates' => array_filter($typeCounts, function ($c) {
                return $c > 1;
            }),
        ];
    }

    /**
     * @param string $type
     * @param bool $isProduct
     * @return string
     */
    private function getMissingSeverity(string $type, bool $isProduct): string
    {
        if ($isProduct && $type === 'Product') {
            return 'critical';
        }
        if ($type === 'BreadcrumbList') {
            return 'warning';
        }

        return 'info';
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
