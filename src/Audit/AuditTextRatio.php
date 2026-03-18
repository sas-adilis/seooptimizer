<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;

class AuditTextRatio implements AuditInterface
{
    public function getKey(): string
    {
        return 'text_ratio';
    }

    public function getTitle(): string
    {
        return 'Text content ratio';
    }

    public function getDescription(): string
    {
        return 'Analyzes the amount of text content on each page. Pages with too little text are poorly indexed by search engines. Thresholds are configurable in the Configuration tab.';
    }

    public function getIcon(): string
    {
        return 'icon-font';
    }

    public function getVisual(): string
    {
        return '';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'word_count_display' => 'Words',
            'text_ratio_display' => 'Text ratio',
            'status_label' => 'Status',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        $thresholdLow = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_LOW') ?: 100;
        $thresholdGood = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_GOOD') ?: 300;

        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'good_count', 'label' => 'Good (> ' . $thresholdGood . ' words)', 'type' => 'custom'],
            ['key' => 'medium_count', 'label' => 'Improvable (' . $thresholdLow . '-' . $thresholdGood . ' words)', 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'low_count', 'label' => 'Insufficient (< ' . $thresholdLow . ' words)', 'type' => 'custom', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            TextRatioObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $entries = $observerResults['text_ratio'] ?? [];

        $thresholdLow = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_LOW') ?: 100;
        $thresholdGood = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_GOOD') ?: 300;

        foreach ($entries as $entry) {
            $wordCount = $entry['word_count'];

            if ($wordCount < $thresholdLow) {
                $severity = 'critical';
                $statusLabel = 'Insufficient';
            } elseif ($wordCount < $thresholdGood) {
                $severity = 'warning';
                $statusLabel = 'Improvable';
            } else {
                $severity = 'good';
                $statusLabel = 'Good';
            }

            $results[] = [
                'url' => $entry['url'],
                'type' => 'text_ratio',
                'severity' => $severity,
                'message' => $wordCount . ' words (' . $entry['text_ratio'] . '% text)',
                'word_count_display' => number_format($wordCount),
                'text_ratio_display' => $entry['text_ratio'] . '%',
                'status_label' => $statusLabel,
                'word_count' => $wordCount,
            ];
        }

        // Sort by word count ascending (least words first)
        usort($results, function ($a, $b) {
            return $a['word_count'] - $b['word_count'];
        });

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function getScoreImpact(): array
    {
        return [
            'critical' => 20,
            'warning' => 8,
        ];
    }

    public function getScoreWeight(): int
    {
        return 15;
    }
}
