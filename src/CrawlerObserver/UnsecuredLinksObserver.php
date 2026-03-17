<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

class UnsecuredLinksObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    private $links = [];

    private $regexPattern = '/(?:href|src)=["\']?(http:\/\/[^"\'>]+)["\']?/i';

    public function observeAfterRequest(string $url, string $content)
    {
        preg_match_all($this->regexPattern, $content, $matches);
        $this->links = array_merge($this->links, $matches[1]);
    }

    public function getResults(): array
    {
        return array_unique($this->links);
    }

    public function getKey(): string
    {
        return 'unsecured_links';
    }
}
