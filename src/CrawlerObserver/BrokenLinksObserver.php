<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrokenLinksObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var array already checked URLs to avoid duplicate requests */
    private $checkedUrls = [];

    /** @var int total links checked */
    private $linksChecked = 0;

    public function getKey(): string
    {
        return 'broken_links';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $links = $this->extractLinks($url, $content);

        if (empty($links)) {
            return;
        }

        // Filter out already checked and external links
        $shopDomain = $this->getShopDomain();
        $toCheck = [];
        foreach ($links as $link) {
            $href = $link['href'];

            // Skip anchors, javascript, mailto, tel
            if (empty($href)
                || strpos($href, '#') === 0
                || strpos($href, 'javascript:') === 0
                || strpos($href, 'mailto:') === 0
                || strpos($href, 'tel:') === 0
                || strpos($href, 'data:') === 0
            ) {
                continue;
            }

            // Resolve relative URLs
            $resolved = $this->resolveUrl($href, $url);
            if (!$resolved) {
                continue;
            }

            // Only check internal links (same domain)
            $linkDomain = parse_url($resolved, PHP_URL_HOST);
            if ($linkDomain && $linkDomain !== $shopDomain) {
                continue;
            }

            if (isset($this->checkedUrls[$resolved])) {
                // Already checked, reuse result
                if ($this->checkedUrls[$resolved] === 404) {
                    $this->addResult($url, $resolved, $link['text'], 404);
                }
                continue;
            }

            $toCheck[] = [
                'resolved' => $resolved,
                'text' => $link['text'],
            ];
        }

        // Batch check with cURL multi
        $this->batchCheck($url, $toCheck);
    }

    /**
     * @param string $pageUrl
     * @param string $content
     * @return array
     */
    private function extractLinks(string $pageUrl, string $content): array
    {
        $links = [];

        $bodyContent = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }

        if (empty(trim($bodyContent))) {
            return $links;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $bodyContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Links <a href>
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href) {
                $links[] = [
                    'href' => $href,
                    'text' => trim(strip_tags($anchor->nodeValue)),
                    'tag' => 'a',
                ];
            }
        }

        // Images <img src>
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && strpos($src, 'data:') !== 0) {
                $links[] = [
                    'href' => $src,
                    'text' => $img->getAttribute('alt') ?: '[image]',
                    'tag' => 'img',
                ];
            }
        }

        // Scripts <script src>
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            if ($src) {
                $links[] = [
                    'href' => $src,
                    'text' => '[script]',
                    'tag' => 'script',
                ];
            }
        }

        // Stylesheets <link href>
        $linkElements = $dom->getElementsByTagName('link');
        foreach ($linkElements as $linkEl) {
            $href = $linkEl->getAttribute('href');
            $rel = $linkEl->getAttribute('rel');
            if ($href && $rel === 'stylesheet') {
                $links[] = [
                    'href' => $href,
                    'text' => '[stylesheet]',
                    'tag' => 'link',
                ];
            }
        }

        return $links;
    }

    /**
     * @param string $pageUrl
     * @param array $toCheck
     */
    private function batchCheck(string $pageUrl, array $toCheck)
    {
        if (empty($toCheck)) {
            return;
        }

        $this->linksChecked += count($toCheck);

        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($toCheck as $i => $entry) {
            $ch = curl_init($entry['resolved']);
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
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $resolved = $toCheck[$i]['resolved'];

            $this->checkedUrls[$resolved] = $httpCode;

            if ($httpCode === 404 || $httpCode === 0) {
                $this->addResult(
                    $pageUrl,
                    $resolved,
                    $toCheck[$i]['text'],
                    $httpCode ?: 0
                );
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }

    /**
     * @param string $pageUrl
     * @param string $brokenUrl
     * @param string $linkText
     * @param int $httpCode
     */
    private function addResult(string $pageUrl, string $brokenUrl, string $linkText, int $httpCode)
    {
        $this->results[] = [
            'page_url' => $pageUrl,
            'broken_url' => $brokenUrl,
            'link_text' => mb_substr($linkText, 0, 80),
            'http_code' => $httpCode,
        ];
    }

    /**
     * @param string $href
     * @param string $baseUrl
     * @return string|null
     */
    private function resolveUrl(string $href, string $baseUrl)
    {
        // Already absolute
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        // Protocol-relative
        if (strpos($href, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }

        $parsed = parse_url($baseUrl);
        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $base = $parsed['scheme'] . '://' . $parsed['host'];

        // Absolute path
        if (strpos($href, '/') === 0) {
            return $base . $href;
        }

        // Relative path
        $dir = isset($parsed['path']) ? rtrim(dirname($parsed['path']), '/') : '';
        return $base . $dir . '/' . $href;
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
    public function getLinksChecked(): int
    {
        return $this->linksChecked;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
