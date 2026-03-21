<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\URLResolver;

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
        $this->shopDomain = URLResolver::getShopDomain();
    }

    public function getKey(): string
    {
        return 'internal_links';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $bodyHtml = $extractor->extractBodyHTML();

        if (empty(trim($bodyHtml))) {
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

        $anchors = $extractor->extractAnchors();
        $links = [];
        $uniqueHrefs = [];
        $emptyAnchors = 0;
        $nofollowCount = 0;

        foreach ($anchors as $anchor) {
            $href = $anchor['href'];

            if (URLResolver::isSkippable($href)) {
                continue;
            }

            $resolved = URLResolver::resolve($href, $url);
            if (!$resolved) {
                continue;
            }

            if (!URLResolver::isInternal($resolved, $this->shopDomain)) {
                continue;
            }

            $this->totalLinksChecked++;

            $text = $anchor['text'];
            $isNofollow = strpos($anchor['rel'], 'nofollow') !== false;

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
