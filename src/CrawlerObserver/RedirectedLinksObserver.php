<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\URLResolver;

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
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $links = $extractor->extractAnchors();

        if (empty($links)) {
            return;
        }

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
                $cached = $this->checkedUrls[$resolved];
                if ($cached['redirected']) {
                    $this->addResult($url, $resolved, $link['text'], $cached['http_code'], $cached['redirect_to']);
                }
                continue;
            }

            $toCheck[$resolved] = $link['text'];
        }

        if (empty($toCheck)) {
            return;
        }

        $this->linksChecked += count($toCheck);

        // Batch check with shared cache (no redirect following)
        $batchResults = LinkStatusCache::check(array_keys($toCheck));

        foreach ($batchResults as $checkedUrl => $info) {
            $httpCode = $info['http_code'];
            $redirectUrl = $info['redirect_url'];

            if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                $this->checkedUrls[$checkedUrl] = [
                    'redirected' => true,
                    'http_code' => $httpCode,
                    'redirect_to' => $redirectUrl,
                ];

                $this->addResult($url, $checkedUrl, $toCheck[$checkedUrl], $httpCode, $redirectUrl);
            } else {
                $this->checkedUrls[$checkedUrl] = [
                    'redirected' => false,
                    'http_code' => $httpCode,
                    'redirect_to' => '',
                ];
            }
        }
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
