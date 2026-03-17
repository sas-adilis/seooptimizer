<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

class PageLoadTimeObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array */
    private $results = [];

    /** @var float */
    private $startTime = 0.0;

    /** @var int threshold in ms for "good" (below = good) */
    private $thresholdGood;

    /** @var int threshold in ms for "slow" (above = slow, between good and slow = medium) */
    private $thresholdSlow;

    /** @var float sum of all load times in ms */
    private $totalLoadTime = 0.0;

    /** @var int number of pages measured */
    private $pagesMeasured = 0;

    /** @var int */
    private $goodCount = 0;

    /** @var int */
    private $mediumCount = 0;

    /** @var int */
    private $slowCount = 0;

    public function __construct()
    {
        $this->thresholdGood = (int) \Configuration::get('SEOO_PERF_THRESHOLD_GOOD') ?: 750;
        $this->thresholdSlow = (int) \Configuration::get('SEOO_PERF_THRESHOLD_SLOW') ?: 1000;
    }

    public function getKey(): string
    {
        return 'page_load_time';
    }

    /**
     * @param string $url
     */
    public function observeBeforeRequest(string $url)
    {
        $this->startTime = microtime(true);
    }

    /**
     * @param string $url
     * @param string $content
     */
    public function observeAfterRequest(string $url, string $content)
    {
        $elapsed = (microtime(true) - $this->startTime) * 1000;
        $loadTimeMs = round($elapsed);

        $this->pagesMeasured++;
        $this->totalLoadTime += $loadTimeMs;

        if ($loadTimeMs <= $this->thresholdGood) {
            $severity = 'good';
            $this->goodCount++;
        } elseif ($loadTimeMs <= $this->thresholdSlow) {
            $severity = 'warning';
            $this->mediumCount++;
        } else {
            $severity = 'critical';
            $this->slowCount++;
        }

        $this->results[] = [
            'url' => $url,
            'load_time_ms' => $loadTimeMs,
            'severity' => $severity,
        ];
    }

    /**
     * @return int
     */
    public function getGoodCount(): int
    {
        return $this->goodCount;
    }

    /**
     * @return int
     */
    public function getMediumCount(): int
    {
        return $this->mediumCount;
    }

    /**
     * @return int
     */
    public function getSlowCount(): int
    {
        return $this->slowCount;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
