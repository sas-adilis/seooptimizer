<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\URLResolver;

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
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);

        // Collect all links: anchors, images, scripts, stylesheets
        $links = [];
        foreach ($extractor->extractAnchors() as $anchor) {
            $links[] = [
                'href' => $anchor['href'],
                'text' => $anchor['text'],
            ];
        }
        foreach ($extractor->extractResources() as $resource) {
            $label = '[' . $resource['type'] . ']';
            $links[] = [
                'href' => $resource['url'],
                'text' => $label,
            ];
        }

        if (empty($links)) {
            return;
        }

        // Filter out already checked and external links
        $shopDomain = URLResolver::getShopDomain();
        $toCheck = [];
        foreach ($links as $link) {
            $href = $link['href'];

            if (URLResolver::isSkippable($href)) {
                continue;
            }

            $resolved = URLResolver::resolve($href, $url);
            if (!$resolved) {
                continue;
            }

            if (!URLResolver::isInternal($resolved, $shopDomain)) {
                continue;
            }

            if (isset($this->checkedUrls[$resolved])) {
                // Already checked, reuse result
                if ($this->checkedUrls[$resolved] === 404) {
                    $this->addResult($url, $resolved, $link['text'], 404);
                }
                continue;
            }

            $toCheck[$resolved] = $link['text'];
        }

        if (empty($toCheck)) {
            return;
        }

        $this->linksChecked += count($toCheck);

        // Batch check with CurlBatch
        $batchResults = LinkStatusCache::check(array_keys($toCheck));

        foreach ($batchResults as $checkedUrl => $info) {
            $httpCode = $info['http_code'];
            $this->checkedUrls[$checkedUrl] = $httpCode;

            if ($httpCode === 404 || $httpCode === 0) {
                $this->addResult(
                    $url,
                    $checkedUrl,
                    $toCheck[$checkedUrl],
                    $httpCode
                );
            }
        }
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
