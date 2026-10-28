<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;

use Adilis\SeoOptimizer\Utils\Message;

class AuditTextRatio implements AuditInterface
{
    public function getKey(): string
    {
        return 'text_ratio';
    }

    public function getTitle(): string
    {
        return Message::tr('Content length');
    }

    public function getDescription(): string
    {
        return Message::tr('Counts the words of the main content of each page (the <main> element when the theme provides one). Pages with very little text may be treated as thin content. Thresholds are configurable in the Configuration tab.');
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
            'word_count_display' => Message::tr('Words'),
            'text_ratio_display' => Message::tr('Text ratio'),
            'status_label' => Message::tr('Status'),
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
            ['key' => 'crawled', 'label' => Message::tr('Pages crawled'), 'type' => 'crawled'],
            ['key' => 'good_count', 'label' => Message::tr('Good (> %d words)', $thresholdGood), 'type' => 'custom'],
            ['key' => 'medium_count', 'label' => Message::tr('Improvable (%d-%d words)', $thresholdLow, $thresholdGood), 'type' => 'custom'],
            ['key' => 'low_count', 'label' => Message::tr('Insufficient (< %d words)', $thresholdLow), 'type' => 'custom', 'warning_if_positive' => true],
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
                'message' => Message::t('%d words (%s%% text)', $wordCount, $entry['text_ratio']),
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
