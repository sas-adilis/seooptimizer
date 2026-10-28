<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\Message;

class StructuredDataObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** Types that must be declared only once per page */
    const UNIQUE_TYPES = ['BreadcrumbList', 'WebSite', 'Organization', 'WebPage'];

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
    public function observeAfterRequest(string $url, string $content, ?HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);

        $jsonLdItems = $extractor->extractJsonLd();
        $isProduct = $extractor->isProductPage();

        // Top-level JSON-LD types only: used to detect duplicated blocks
        $topLevelCounts = [];
        foreach ($jsonLdItems as $item) {
            if (!isset($item['@type'])) {
                continue;
            }
            $itemTypes = is_array($item['@type']) ? $item['@type'] : [$item['@type']];
            foreach ($itemTypes as $type) {
                if (!is_string($type)) {
                    continue;
                }
                $type = HTMLExtractor::normalizeSchemaType($type);
                $topLevelCounts[$type] = (isset($topLevelCounts[$type]) ? $topLevelCounts[$type] : 0) + 1;
            }
        }

        // All types present on the page: nested JSON-LD types and microdata included
        $typeCounts = $extractor->extractJsonLdTypesDeep();
        foreach ($extractor->extractMicrodataTypes() as $type => $count) {
            $typeCounts[$type] = (isset($typeCounts[$type]) ? $typeCounts[$type] : 0) + $count;
        }

        // An AggregateOffer carries its own Offers
        $hasOffer = isset($typeCounts['Offer']) || isset($typeCounts['AggregateOffer']);

        // Expected types depend on page type
        $expectedTypes = $isProduct
            ? ['Product', 'BreadcrumbList', 'Offer', 'Organization', 'WebPage']
            : ['BreadcrumbList', 'Organization', 'WebPage'];

        $issues = [];
        $schemas = [];

        // Check expected types
        foreach ($expectedTypes as $type) {
            $found = $type === 'Offer' ? $hasOffer : isset($typeCounts[$type]);
            $schemas[] = ['name' => $type, 'found' => $found];

            if (!$found) {
                $severity = $this->getMissingSeverity($type, $isProduct);
                $issues[] = [
                    'type' => 'missing_schema',
                    'severity' => $severity,
                    'message' => Message::t('Schema %s missing', $type),
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

        // Check for duplicated top-level blocks (only types that must be unique on a page)
        $duplicates = [];
        foreach (self::UNIQUE_TYPES as $type) {
            $count = isset($topLevelCounts[$type]) ? $topLevelCounts[$type] : 0;
            if ($count > 1) {
                $duplicates[$type] = $count;
                $issues[] = [
                    'type' => 'duplicate_schema',
                    'severity' => 'warning',
                    'message' => Message::t('Schema %s declared %d times', $type, $count),
                    'schema_type' => $type,
                    'count' => $count,
                ];
            }
        }

        // Product-specific checks
        if ($isProduct) {
            if (isset($typeCounts['Product']) && !$hasOffer) {
                $issues[] = [
                    'type' => 'product_no_offer',
                    'severity' => 'warning',
                    'message' => Message::t('Product schema without Offer — price won\'t show in results'),
                    'schema_type' => 'Offer',
                ];
            }

            if (isset($typeCounts['AggregateRating'])) {
                $schemas[] = ['name' => 'AggregateRating', 'found' => true];
            }

            foreach ($this->getProductIssues($jsonLdItems) as $issue) {
                $issues[] = $issue;
            }
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
                'message' => Message::t('%d malformed JSON-LD block(s)', $malformedCount),
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
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Completeness of the Product block declared in JSON-LD (Google merchant listings):
     * image, price, currency and availability, then brand and identifiers.
     *
     * @param array<int, array> $jsonLdItems
     * @return array<int, array<string, string>>
     */
    private function getProductIssues(array $jsonLdItems): array
    {
        $product = null;
        foreach ($jsonLdItems as $item) {
            $types = isset($item['@type']) ? (array) $item['@type'] : [];
            foreach ($types as $type) {
                if (is_string($type) && HTMLExtractor::normalizeSchemaType($type) === 'Product') {
                    $product = $item;
                    break 2;
                }
            }
        }
        if ($product === null) {
            return [];
        }

        $missing = [];
        if (empty($product['image'])) {
            $missing[] = ['warning', Message::t('Product schema without image')];
        }

        $offer = isset($product['offers']) && is_array($product['offers']) ? $product['offers'] : [];
        if (isset($offer[0]) && is_array($offer[0])) {
            $offer = $offer[0];
        }
        if (!empty($offer)) {
            $spec = isset($offer['priceSpecification']) && is_array($offer['priceSpecification']) ? $offer['priceSpecification'] : [];
            if (!isset($offer['price']) && !isset($offer['lowPrice']) && !isset($spec['price'])) {
                $missing[] = ['warning', Message::t('Offer without price')];
            }
            if (empty($offer['priceCurrency']) && empty($spec['priceCurrency'])) {
                $missing[] = ['warning', Message::t('Offer without price currency')];
            }
            if (empty($offer['availability'])) {
                $missing[] = ['info', Message::t('Offer without availability')];
            }
        }

        if (empty($product['brand'])) {
            $missing[] = ['info', Message::t('Product schema without brand')];
        }
        if (empty($product['sku']) && empty($product['gtin']) && empty($product['gtin13']) && empty($product['mpn'])) {
            $missing[] = ['info', Message::t('Product schema without SKU or GTIN')];
        }

        $issues = [];
        foreach ($missing as $entry) {
            $issues[] = [
                'type' => 'product_incomplete',
                'severity' => $entry[0],
                'message' => $entry[1],
                'schema_type' => 'Product',
            ];
        }

        return $issues;
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
