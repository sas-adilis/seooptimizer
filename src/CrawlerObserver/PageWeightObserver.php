<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\URLResolver;

class PageWeightObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var int threshold in KB for "light" (below = good) */
    private $thresholdLight;

    /** @var int threshold in KB for "heavy" (above = heavy, between = moderate) */
    private $thresholdHeavy;

    /** @var int */
    private $lightCount = 0;

    /** @var int */
    private $moderateCount = 0;

    /** @var int */
    private $heavyCount = 0;

    /** @var array already measured asset URLs to avoid duplicate requests */
    private $assetSizeCache = [];

    /** @var string|null */
    private $shopDomain;

    /**
     * Resource type mapping from HTMLExtractor types to PageWeight types.
     *
     * @var array<string, string>
     */
    private static $typeMap = [
        'script' => 'js',
        'stylesheet' => 'css',
        'image' => 'image',
    ];

    public function __construct()
    {
        $this->thresholdLight = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_LIGHT') ?: 1024;
        $this->thresholdHeavy = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_HEAVY') ?: 3072;
    }

    public function getKey(): string
    {
        return 'page_weight';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $htmlSize = strlen($content);
        $assets = $this->extractAssetUrls($url, $extractor);

        // Separate cached from uncached URLs
        $toFetch = [];
        $cachedResults = [];
        foreach ($assets as $asset) {
            if (isset($this->assetSizeCache[$asset['url']])) {
                $cachedResults[] = [
                    'type' => $asset['type'],
                    'size' => $this->assetSizeCache[$asset['url']],
                ];
            } else {
                $toFetch[] = $asset;
            }
        }

        // Batch fetch sizes for uncached URLs
        $fetchedResults = [];
        if (!empty($toFetch)) {
            $urlList = array_column($toFetch, 'url');
            $sizes = CurlBatch::getContentLengths($urlList);

            foreach ($toFetch as $asset) {
                $size = isset($sizes[$asset['url']]) ? $sizes[$asset['url']] : 0;
                $this->assetSizeCache[$asset['url']] = $size;
                $fetchedResults[] = [
                    'type' => $asset['type'],
                    'size' => $size,
                ];
            }
        }

        $assetSizes = array_merge($cachedResults, $fetchedResults);

        $totalSize = $htmlSize;
        $detailHtml = $htmlSize;
        $detailImages = 0;
        $detailCss = 0;
        $detailJs = 0;

        foreach ($assetSizes as $asset) {
            $totalSize += $asset['size'];

            switch ($asset['type']) {
                case 'image':
                    $detailImages += $asset['size'];
                    break;
                case 'css':
                    $detailCss += $asset['size'];
                    break;
                case 'js':
                    $detailJs += $asset['size'];
                    break;
            }
        }

        $totalKb = round($totalSize / 1024);

        if ($totalKb <= $this->thresholdLight) {
            $severity = 'good';
            $this->lightCount++;
        } elseif ($totalKb <= $this->thresholdHeavy) {
            $severity = 'warning';
            $this->moderateCount++;
        } else {
            $severity = 'critical';
            $this->heavyCount++;
        }

        $this->results[] = [
            'url' => $url,
            'total_kb' => $totalKb,
            'html_kb' => round($detailHtml / 1024),
            'images_kb' => round($detailImages / 1024),
            'css_kb' => round($detailCss / 1024),
            'js_kb' => round($detailJs / 1024),
            'assets_count' => count($assets),
            'severity' => $severity,
        ];
    }

    /**
     * @param string $pageUrl
     * @param string $content
     * @return array<int, array{url: string, type: string}>
     */
    private function extractAssetUrls(string $pageUrl, HTMLExtractor $extractor): array
    {
        $resources = $extractor->extractResources();

        if ($this->shopDomain === null) {
            $this->shopDomain = URLResolver::getShopDomain();
        }

        $assets = [];
        foreach ($resources as $resource) {
            $resolved = URLResolver::resolve($resource['url'], $pageUrl);
            if ($resolved === null) {
                continue;
            }
            if (!URLResolver::isInternal($resolved, $this->shopDomain)) {
                continue;
            }
            $type = isset(self::$typeMap[$resource['type']]) ? self::$typeMap[$resource['type']] : $resource['type'];
            $assets[] = ['url' => $resolved, 'type' => $type];
        }

        return $assets;
    }

    /**
     * @return int
     */
    public function getLightCount(): int
    {
        return $this->lightCount;
    }

    /**
     * @return int
     */
    public function getModerateCount(): int
    {
        return $this->moderateCount;
    }

    /**
     * @return int
     */
    public function getHeavyCount(): int
    {
        return $this->heavyCount;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
