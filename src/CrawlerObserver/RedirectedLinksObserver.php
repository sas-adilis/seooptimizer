<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RedirectedLinksObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var array already checked URLs to avoid duplicate requests */
    private $checkedUrls = [];

    /** @var int total links checked */
    private $linksChecked = 0;

    /** @var int total redirected links found */
    private $redirectedCount = 0;

    public function getKey(): string
    {
        return 'redirected_links';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $links = $this->extractAnchorLinks($url, $content);

        if (empty($links)) {
            return;
        }

        $shopDomain = $this->getShopDomain();
        $toCheck = [];
        foreach ($links as $link) {
            $href = $link['href'];

            if (empty($href)
                || strpos($href, '#') === 0
                || strpos($href, 'javascript:') === 0
                || strpos($href, 'mailto:') === 0
                || strpos($href, 'tel:') === 0
                || strpos($href, 'data:') === 0
            ) {
                continue;
            }

            $resolved = $this->resolveUrl($href, $url);
            if (!$resolved) {
                continue;
            }

            // Only check internal links
            $linkDomain = parse_url($resolved, PHP_URL_HOST);
            if ($linkDomain && $linkDomain !== $shopDomain) {
                continue;
            }

            if (isset($this->checkedUrls[$resolved])) {
                $cached = $this->checkedUrls[$resolved];
                if ($cached['redirected']) {
                    $this->addResult($url, $resolved, $link['text'], $cached['http_code'], $cached['redirect_to']);
                }
                continue;
            }

            $toCheck[] = [
                'resolved' => $resolved,
                'text' => $link['text'],
            ];
        }

        $this->batchCheck($url, $toCheck);
    }

    /**
     * @param string $pageUrl
     * @param string $content
     * @return array
     */
    private function extractAnchorLinks(string $pageUrl, string $content): array
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

        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href) {
                $links[] = [
                    'href' => $href,
                    'text' => trim(strip_tags($anchor->nodeValue)),
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
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
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
            $redirectTo = '';

            if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                $redirectTo = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                if (empty($redirectTo)) {
                    $headers = curl_multi_getcontent($ch);
                    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
                        $redirectTo = trim($m[1]);
                    }
                }

                $this->checkedUrls[$resolved] = [
                    'redirected' => true,
                    'http_code' => $httpCode,
                    'redirect_to' => $redirectTo,
                ];

                $this->addResult($pageUrl, $resolved, $toCheck[$i]['text'], $httpCode, $redirectTo);
            } else {
                $this->checkedUrls[$resolved] = [
                    'redirected' => false,
                    'http_code' => $httpCode,
                    'redirect_to' => '',
                ];
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }

    /**
     * @param string $pageUrl
     * @param string $linkUrl
     * @param string $linkText
     * @param int $httpCode
     * @param string $redirectTo
     */
    private function addResult(string $pageUrl, string $linkUrl, string $linkText, int $httpCode, string $redirectTo)
    {
        $this->redirectedCount++;

        $this->results[] = [
            'page_url' => $pageUrl,
            'link_url' => $linkUrl,
            'link_text' => mb_substr($linkText, 0, 80),
            'http_code' => $httpCode,
            'redirect_to' => $redirectTo,
        ];
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
     * @return int
     */
    public function getRedirectedCount(): int
    {
        return $this->redirectedCount;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
