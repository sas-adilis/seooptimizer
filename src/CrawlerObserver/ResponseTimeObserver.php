<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

class ResponseTimeObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    private $responseTimes = [];
    private $startTime;

    public function observe(string $url, string $content): void
    {
        // Cette méthode est laissée vide, car elle n'est pas utilisée pour cet observateur spécifique
    }

    public function observeBeforeRequest(string $url): void
    {
        $this->startTime = microtime(true);
    }

    public function observeAfterRequest(string $url, string $content): void
    {
        $responseTime = microtime(true) - $this->startTime;
        $this->responseTimes[$url] = $responseTime;
    }

    public function getResults(): array
    {
        return $this->responseTimes;
    }

    public function getKey(): string
    {
        return 'response_time';
    }
}
