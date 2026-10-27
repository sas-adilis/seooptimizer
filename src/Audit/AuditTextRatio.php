<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;

class AuditTextRatio implements AuditInterface
{
    public function getKey(): string
    {
        return 'text_ratio';
    }

    public function getTitle(): string
    {
        return 'Content length';
    }

    public function getDescription(): string
    {
        return 'Counts the words of the main content of each page (the <main> element when the theme provides one). Pages with very little text may be treated as thin content. Thresholds are configurable in the Configuration tab.';
    }

    public function getIcon(): string
    {
        return 'text-aa';
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
            ['key' => 'medium_count', 'label' => 'Improvable (' . $thresholdLow . '-' . $thresholdGood . ' words)', 'type' => 'custom'],
            ['key' => 'low_count', 'label' => 'Insufficient (< ' . $thresholdLow . ' words)', 'type' => 'custom', 'warning_if_positive' => true],
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
                $severity = 'warning';
                $statusLabel = 'Insufficient';
            } elseif ($wordCount < $thresholdGood) {
                $severity = 'info';
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
            'warning' => 8,
        ];
    }

    public function getScoreWeight(): int
    {
        return 10;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
