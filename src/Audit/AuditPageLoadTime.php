<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\PageLoadTimeObserver;

class AuditPageLoadTime implements AuditInterface
{
    public function getKey(): string
    {
        return 'page_load_time';
    }

    public function getTitle(): string
    {
        return 'Page load time';
    }

    public function getDescription(): string
    {
        return 'Measures the response time of every page on your site. Slow pages hurt SEO rankings and user experience. Thresholds are configurable in the Configuration tab.';
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
            'load_time_display' => 'Load time',
            'status_label' => 'Status',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        $thresholdGood = (int) \Configuration::get('SEOO_PERF_THRESHOLD_GOOD') ?: 750;
        $thresholdSlow = (int) \Configuration::get('SEOO_PERF_THRESHOLD_SLOW') ?: 1000;

        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'good_count', 'label' => 'Good (< ' . $thresholdGood . ' ms)', 'type' => 'custom'],
            ['key' => 'medium_count', 'label' => 'Medium (' . $thresholdGood . '-' . $thresholdSlow . ' ms)', 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'slow_count', 'label' => 'Slow (> ' . $thresholdSlow . ' ms)', 'type' => 'custom', 'danger_if_positive' => true],
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
        $thresholdGood = (int) \Configuration::get('SEOO_PERF_THRESHOLD_GOOD') ?: 750;
        $thresholdSlow = (int) \Configuration::get('SEOO_PERF_THRESHOLD_SLOW') ?: 1000;

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
            'critical' => 40,
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
