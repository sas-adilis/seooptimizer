<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

class DuplicateH1 extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    private $duplicates = [];

    public function observeAfterRequest(string $url, string $content)
    {
        preg_match_all('/<h1.*?>(.*?)<\/h1>/', $content, $matches);

        if (count($matches[0]) > 1) {
            $this->duplicates[$url] = count($matches[0]);
        }
    }

    public function getResults(): array
    {
        return $this->duplicates;
    }
}
