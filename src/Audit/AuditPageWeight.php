<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\PageWeightObserver;

class AuditPageWeight implements AuditInterface
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
        return 'Measures the total weight of each page including HTML, images, CSS and JavaScript. Heavy pages slow down loading and hurt SEO. Thresholds are configurable in the Configuration tab.';
    }

    public function getIcon(): string
    {
        return 'icon-hdd-o';
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
        $thresholdLight = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_LIGHT') ?: 1024;
        $thresholdHeavy = (int) \Configuration::get('SEOO_WEIGHT_THRESHOLD_HEAVY') ?: 3072;

        foreach ($entries as $entry) {
            $totalKb = $entry['total_kb'];

            if ($totalKb <= $thresholdLight) {
                $severity = 'good';
                $statusLabel = 'Light';
            } elseif ($totalKb <= $thresholdHeavy) {
                $severity = 'warning';
                $statusLabel = 'Moderate';
            } else {
                $severity = 'critical';
                $statusLabel = 'Heavy';
            }

            $results[] = [
                'url' => $entry['url'],
                'type' => 'page_weight',
                'severity' => $severity,
                'message' => $this->formatSize($totalKb),
                'total_display' => $this->formatSize($totalKb),
                'html_display' => $this->formatSize($entry['html_kb']),
                'images_display' => $this->formatSize($entry['images_kb']),
                'css_display' => $this->formatSize($entry['css_kb']),
                'js_display' => $this->formatSize($entry['js_kb']),
                'assets_count' => $entry['assets_count'],
                'status_label' => $statusLabel,
                'total_kb' => $totalKb,
            ];
        }

        // Sort by total weight descending (heaviest first)
        usort($results, function ($a, $b) {
            return $b['total_kb'] - $a['total_kb'];
        });

        return $results;
    }

    /**
     * @param int $kb
     * @return string
     */
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
