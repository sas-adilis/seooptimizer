<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InternalLinksObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array<string, array> per-page outgoing internal links */
    private $outgoing = [];

    /** @var array<string, int> count of incoming internal links per URL */
    private $incoming = [];

    /** @var string */
    private $shopDomain;

    /** @var int */
    private $totalLinksChecked = 0;

    /** @var int pages with no outgoing internal links */
    private $noOutgoingCount = 0;

    /** @var int pages with very few outgoing internal links (<3) */
    private $fewOutgoingCount = 0;

    public function __construct()
    {
        $this->shopDomain = $this->getShopDomain();
    }

    public function getKey(): string
    {
        return 'internal_links';
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $bodyContent = $content;
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $content, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }

        if (empty(trim($bodyContent))) {
            $this->outgoing[$url] = [
                'links' => [],
                'count' => 0,
                'unique_count' => 0,
                'empty_anchors' => 0,
                'nofollow_count' => 0,
            ];
            $this->noOutgoingCount++;
            return;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $bodyContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $links = [];
        $uniqueHrefs = [];
        $emptyAnchors = 0;
        $nofollowCount = 0;

        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if (!$href) {
                continue;
            }

            // Skip anchors, javascript, mailto, tel
            if (strpos($href, '#') === 0
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

            // Only internal links
            $linkDomain = parse_url($resolved, PHP_URL_HOST);
            if ($linkDomain && $linkDomain !== $this->shopDomain) {
                continue;
            }

            $this->totalLinksChecked++;

            $text = trim(strip_tags($anchor->nodeValue));
            $rel = $anchor->getAttribute('rel');
            $isNofollow = $rel && strpos($rel, 'nofollow') !== false;

            if ($isNofollow) {
                $nofollowCount++;
            }

            if (empty($text)) {
                $emptyAnchors++;
            }

            $links[] = [
                'href' => $resolved,
                'text' => mb_substr($text, 0, 80),
                'nofollow' => $isNofollow,
            ];

            // Normalize URL for uniqueness (remove fragment)
            $cleanHref = preg_replace('/#.*$/', '', $resolved);
            $uniqueHrefs[$cleanHref] = true;

            // Track incoming links
            if (!isset($this->incoming[$cleanHref])) {
                $this->incoming[$cleanHref] = 0;
            }
            $this->incoming[$cleanHref]++;
        }

        $count = count($links);
        $uniqueCount = count($uniqueHrefs);

        $this->outgoing[$url] = [
            'links' => $links,
            'count' => $count,
            'unique_count' => $uniqueCount,
            'empty_anchors' => $emptyAnchors,
            'nofollow_count' => $nofollowCount,
        ];

        if ($count === 0) {
            $this->noOutgoingCount++;
        } elseif ($uniqueCount < 3) {
            $this->fewOutgoingCount++;
        }
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
        return $this->totalLinksChecked;
    }

    /**
     * @return int
     */
    public function getNoOutgoingCount(): int
    {
        return $this->noOutgoingCount;
    }

    /**
     * @return int
     */
    public function getFewOutgoingCount(): int
    {
        return $this->fewOutgoingCount;
    }

    /**
     * @return array<string, int>
     */
    public function getIncomingCounts(): array
    {
        return $this->incoming;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return [
            'outgoing' => $this->outgoing,
            'incoming' => $this->incoming,
        ];
    }
}
