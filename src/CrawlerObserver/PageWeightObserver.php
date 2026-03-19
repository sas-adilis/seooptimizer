<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
    public function observeAfterRequest(string $url, string $content)
    {
        $htmlSize = strlen($content);
        $assets = $this->extractAssetUrls($url, $content);
        $assetSizes = $this->batchGetSizes($assets);

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
     * @return array
     */
    private function extractAssetUrls(string $pageUrl, string $content): array
    {
        $assets = [];
        $shopDomain = $this->getShopDomain();

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && strpos($src, 'data:') !== 0) {
                $resolved = $this->resolveUrl($src, $pageUrl);
                if ($resolved && $this->isSameDomain($resolved, $shopDomain)) {
                    $assets[] = ['url' => $resolved, 'type' => 'image'];
                }
            }
        }

        // Stylesheets
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $rel = $link->getAttribute('rel');
            if ($href && $rel === 'stylesheet') {
                $resolved = $this->resolveUrl($href, $pageUrl);
                if ($resolved && $this->isSameDomain($resolved, $shopDomain)) {
                    $assets[] = ['url' => $resolved, 'type' => 'css'];
                }
            }
        }

        // Scripts
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            if ($src) {
                $resolved = $this->resolveUrl($src, $pageUrl);
                if ($resolved && $this->isSameDomain($resolved, $shopDomain)) {
                    $assets[] = ['url' => $resolved, 'type' => 'js'];
                }
            }
        }

        return $assets;
    }

    /**
     * @param array $assets
     * @return array
     */
    private function batchGetSizes(array $assets): array
    {
        if (empty($assets)) {
            return [];
        }

        $results = [];
        $toFetch = [];

        // Check cache first
        foreach ($assets as $i => $asset) {
            if (isset($this->assetSizeCache[$asset['url']])) {
                $results[] = [
                    'type' => $asset['type'],
                    'size' => $this->assetSizeCache[$asset['url']],
                ];
            } else {
                $toFetch[$i] = $asset;
            }
        }

        if (empty($toFetch)) {
            return $results;
        }

        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($toFetch as $i => $asset) {
            $ch = curl_init($asset['url']);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SeoOptimizerAudit/1.0)');

            curl_multi_add_handle($multiHandle, $ch);
            $handles[$i] = $ch;
        }

        $running = 0;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($handles as $i => $ch) {
            $contentLength = (int) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            if ($contentLength <= 0) {
                $contentLength = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            }

            $this->assetSizeCache[$toFetch[$i]['url']] = $contentLength;

            $results[] = [
                'type' => $toFetch[$i]['type'],
                'size' => $contentLength,
            ];

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    /**
     * @param string $href
     * @param string $baseUrl
     * @return string|null
     */
    private function resolveUrl(string $href, string $baseUrl)
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        if (strpos($href, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }

        $parsed = parse_url($baseUrl);
        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $base = $parsed['scheme'] . '://' . $parsed['host'];

        if (strpos($href, '/') === 0) {
            return $base . $href;
        }

        $dir = isset($parsed['path']) ? rtrim(dirname($parsed['path']), '/') : '';
        return $base . $dir . '/' . $href;
    }

    /**
     * @param string $url
     * @param string $shopDomain
     * @return bool
     */
    private function isSameDomain(string $url, string $shopDomain): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host && $host === $shopDomain;
    }

    /**
     * @return string
     */
    private function getShopDomain(): string
    {
        $shopUrl = \Context::getContext()->shop->getBaseURL();
        return parse_url($shopUrl, PHP_URL_HOST) ?: '';
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
