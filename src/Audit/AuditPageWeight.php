<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\PageWeightObserver;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedScore;

class AuditPageWeight implements AuditInterface, ScoredAuditInterface
{
    public function getKey(): string
    {
        return 'page_weight';
    }

    public function getTitle(): string
    {
        return 'Page weight';
    }

    public function getDescription(): string
    {
        return 'Measures the total weight of each page including HTML, images, CSS and JavaScript. Heavy pages slow down loading and hurt SEO. Thresholds are configurable in the Configuration tab. When a page has been analyzed by Lighthouse (PageSpeed tab), its Lighthouse performance score replaces this measure.';
    }

    public function getIcon(): string
    {
        return 'hard-drives';
    }

    public function getVisual(): string
    {
        return 'panda-page-weight.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'total_display' => 'Total',
            'html_display' => 'HTML',
            'images_display' => 'Images',
            'css_display' => 'CSS',
            'js_display' => 'JS',
            'assets_count' => 'Assets',
            'status_label' => 'Status',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        $thresholdLight = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_LIGHT') ?: 1024;
        $thresholdHeavy = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_HEAVY') ?: 3072;

        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'light_count', 'label' => 'Light (< ' . $this->formatSize($thresholdLight) . ')', 'type' => 'custom'],
            ['key' => 'moderate_count', 'label' => 'Moderate (' . $this->formatSize($thresholdLight) . '-' . $this->formatSize($thresholdHeavy) . ')', 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'heavy_count', 'label' => 'Heavy (> ' . $this->formatSize($thresholdHeavy) . ')', 'type' => 'custom', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            PageWeightObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $entries = $observerResults['page_weight'] ?? [];
        foreach ($entries as $entry) {
            $results[] = $this->resolveStatus([
                'url' => $entry['url'],
                'type' => 'page_weight',
                'message' => $this->formatSize($entry['total_kb']),
                'total_display' => $this->formatSize($entry['total_kb']),
                'html_display' => $this->formatSize($entry['html_kb']),
                'images_display' => $this->formatSize($entry['images_kb']),
                'css_display' => $this->formatSize($entry['css_kb']),
                'js_display' => $this->formatSize($entry['js_kb']),
                'assets_count' => $entry['assets_count'],
                'total_kb' => $entry['total_kb'],
            ]);
        }

        // Sort by total weight descending (heaviest first)
        usort($results, function ($a, $b) {
            return $b['total_kb'] - $a['total_kb'];
        });

        return $results;
    }

    /**
     * Severity and status of a result: from the Lighthouse score when the page was analyzed
     * by PageSpeed Insights, otherwise from the measured weight.
     *
     * @param array $row Result with at least url and total_kb
     *
     * @return array
     */
    public function resolveStatus(array $row): array
    {
        $lighthouse = PageSpeedScore::forUrl($row['url']);
        if ($lighthouse !== null) {
            $row['severity'] = PageSpeedScore::severity($lighthouse);
            $row['status_label'] = 'Lighthouse ' . $lighthouse . '/100';
            $row['lighthouse'] = $lighthouse;

            return $row;
        }

        unset($row['lighthouse']);
        $thresholdLight = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_LIGHT') ?: 1024;
        $thresholdHeavy = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_HEAVY') ?: 3072;

        if ($row['total_kb'] <= $thresholdLight) {
            $row['severity'] = 'good';
            $row['status_label'] = 'Light';
        } elseif ($row['total_kb'] <= $thresholdHeavy) {
            $row['severity'] = 'warning';
            $row['status_label'] = 'Moderate';
        } else {
            $row['severity'] = 'critical';
            $row['status_label'] = 'Heavy';
        }

        return $row;
    }

    /**
     * The Lighthouse score replaces the score derived from the weight, for the pages analyzed.
     *
     * @param array<string, int|float> $scores
     *
     * @return array<string, int|float>
     */
    public function overridePageScores(array $scores): array
    {
        foreach (PageSpeedScore::getAll() as $url => $score) {
            if (isset($scores[$url])) {
                $scores[$url] = $score;
            }
        }

        return $scores;
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

    private function formatSize(int $kb): string
    {
        if ($kb >= 1024) {
            return number_format($kb / 1024, 1) . ' MB';
        }

        return $kb . ' KB';
    }
}
