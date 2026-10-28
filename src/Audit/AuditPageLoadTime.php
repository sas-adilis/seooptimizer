<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\PageLoadTimeObserver;

use Adilis\SeoOptimizer\Utils\Message;

class AuditPageLoadTime implements AuditInterface
{
    public function getKey(): string
    {
        return 'page_load_time';
    }

    public function getTitle(): string
    {
        return Message::tr('Server response time');
    }

    public function getDescription(): string
    {
        return Message::tr('Measures the time to first byte of every page, as seen from your own server. It does not include browser rendering, JavaScript or images, and the first request after a cache flush is usually slower. A slow server response delays the display of the page. Thresholds are configurable in the Configuration tab.');
    }

    public function getIcon(): string
    {
        return 'gauge';
    }

    public function getVisual(): string
    {
        return 'panda-pagespeed.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'load_time_display' => Message::tr('Response time'),
            'status_label' => Message::tr('Status'),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        $thresholdGood = (int) \Configuration::get('SEOO_PERF_THRESHOLD_GOOD') ?: 800;
        $thresholdSlow = (int) \Configuration::get('SEOO_PERF_THRESHOLD_SLOW') ?: 1800;

        return [
            ['key' => 'crawled', 'label' => Message::tr('Pages crawled'), 'type' => 'crawled'],
            ['key' => 'good_count', 'label' => Message::tr('Good (< %d ms)', $thresholdGood), 'type' => 'custom'],
            ['key' => 'medium_count', 'label' => Message::tr('Medium (%d-%d ms)', $thresholdGood, $thresholdSlow), 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'slow_count', 'label' => Message::tr('Slow (> %d ms)', $thresholdSlow), 'type' => 'custom', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            PageLoadTimeObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $entries = $observerResults['page_load_time'] ?? [];
        $thresholdGood = (int) \Configuration::get('SEOO_PERF_THRESHOLD_GOOD') ?: 800;
        $thresholdSlow = (int) \Configuration::get('SEOO_PERF_THRESHOLD_SLOW') ?: 1800;

        foreach ($entries as $entry) {
            $loadTimeMs = $entry['load_time_ms'];

            if ($loadTimeMs <= $thresholdGood) {
                $severity = 'good';
                $statusLabel = 'Good';
            } elseif ($loadTimeMs <= $thresholdSlow) {
                $severity = 'warning';
                $statusLabel = 'Medium';
            } else {
                $severity = 'critical';
                $statusLabel = 'Slow';
            }

            if ($loadTimeMs >= 1000) {
                $loadTimeDisplay = number_format($loadTimeMs / 1000, 2) . ' s';
            } else {
                $loadTimeDisplay = $loadTimeMs . ' ms';
            }

            $results[] = [
                'url' => $entry['url'],
                'type' => 'page_load_time',
                'severity' => $severity,
                'message' => $loadTimeDisplay,
                'load_time_display' => $loadTimeDisplay,
                'status_label' => $statusLabel,
                'load_time_ms' => $loadTimeMs,
            ];
        }

        // Sort by load time descending (slowest first)
        usort($results, function ($a, $b) {
            return $b['load_time_ms'] - $a['load_time_ms'];
        });

        return $results;
    }

    public function getScoreImpact(): array
    {
        return [
            'critical' => 25,
            'warning' => 15,
        ];
    }

    public function getScoreWeight(): int
    {
        return 20;
    }

    public function requiresIndexablePage(): bool
    {
        return false;
    }
}
