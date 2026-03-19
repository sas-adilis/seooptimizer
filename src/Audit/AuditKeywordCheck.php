<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\KeywordCheckObserver;

class AuditKeywordCheck implements AuditInterface
{
    /**
     * Zone weights for scoring.
     * high = 15/15/10, medium = 10/10/10, low = 5
     * max per keyword = 75 points
     */
    const ZONE_LABELS = [
        'meta_title' => 'Meta title',
        'h1' => 'H1',
        'url' => 'URL',
        'meta_description' => 'Meta description',
        'content_start' => 'First 100 words',
        'image_alt' => 'Image alt',
        'category' => 'Category',
    ];

    public function getKey(): string
    {
        return 'keyword_check';
    }

    public function getTitle(): string
    {
        return 'Keyword check';
    }

    public function getDescription(): string
    {
        return 'Checks if target keywords (from meta keywords) are present in key SEO zones: title, H1, URL, meta description, content, image alt and category. Matching is accent-insensitive with partial term support.';
    }

    public function getIcon(): string
    {
        return 'icon-bullseye';
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
            'keyword' => 'Keyword',
            'zones_summary' => 'Zones found',
            'score_display' => 'Score',
            'missing_zones' => 'Missing in',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumnCallbacks(): array
    {
        return [
            'score_display' => 'displayScoreBadge',
            'zones_summary' => 'displayZonesList',
            'missing_zones' => 'displayMissingZones',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'pages_with_keywords', 'label' => 'Pages with keywords', 'type' => 'custom'],
            ['key' => 'pages_without_keywords', 'label' => 'Pages without keywords', 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'total_keywords_checked', 'label' => 'Keywords checked', 'type' => 'custom'],
            ['key' => 'issues', 'label' => 'Issues found', 'type' => 'total_issues', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            KeywordCheckObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $data = $observerResults['keyword_check'] ?? [];

        foreach ($data as $url => $pageData) {
            if (!$pageData['has_keywords']) {
                // Page without keywords = warning
                $results[] = [
                    'url' => $url,
                    'type' => 'no_keywords',
                    'severity' => 'warning',
                    'message' => 'No meta keywords defined',
                    'keyword' => '-',
                    'zones_summary' => '-',
                    'score_display' => '-',
                    'missing_zones' => 'No keywords to check',
                ];
                continue;
            }

            foreach ($pageData['checks'] as $check) {
                $keyword = $check['keyword'];
                $score = $check['score'];
                $maxScore = $check['max_score'];
                $pct = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;

                // Build zone summaries
                $foundZones = [];
                $missingZones = [];
                foreach ($check['zones'] as $zoneName => $zoneInfo) {
                    $label = isset(self::ZONE_LABELS[$zoneName]) ? self::ZONE_LABELS[$zoneName] : $zoneName;
                    if ($zoneInfo['found']) {
                        $foundZones[] = $label;
                    } else {
                        $missingZones[] = $label;
                    }
                }

                // Determine severity based on score percentage
                if ($pct >= 80) {
                    $severity = 'good';
                } elseif ($pct >= 50) {
                    $severity = 'warning';
                } else {
                    $severity = 'critical';
                }

                // Only report issues (not perfect scores)
                if ($severity === 'good') {
                    continue;
                }

                // Determine message based on what's missing
                $missingHigh = [];
                $missingMedium = [];
                foreach ($check['zones'] as $zoneName => $zoneInfo) {
                    if (!$zoneInfo['found']) {
                        $label = isset(self::ZONE_LABELS[$zoneName]) ? self::ZONE_LABELS[$zoneName] : $zoneName;
                        if ($zoneInfo['importance'] === 'high') {
                            $missingHigh[] = $label;
                        } elseif ($zoneInfo['importance'] === 'medium') {
                            $missingMedium[] = $label;
                        }
                    }
                }

                $message = '';
                if (!empty($missingHigh)) {
                    $message = 'Missing in critical zones: ' . implode(', ', $missingHigh);
                } elseif (!empty($missingMedium)) {
                    $message = 'Missing in important zones: ' . implode(', ', $missingMedium);
                } else {
                    $message = 'Low optimization (' . $pct . '%)';
                }

                $results[] = [
                    'url' => $url,
                    'type' => 'keyword_missing',
                    'severity' => $severity,
                    'message' => $message,
                    'keyword' => $keyword,
                    'zones_summary' => implode(', ', $foundZones) ?: 'None',
                    'score_display' => $pct . '/100',
                    'missing_zones' => implode(', ', $missingZones) ?: '-',
                    'keyword_score_pct' => $pct,
                ];
            }
        }

        // Sort by score ascending (worst first)
        usort($results, function ($a, $b) {
            $sa = isset($a['keyword_score_pct']) ? $a['keyword_score_pct'] : 0;
            $sb = isset($b['keyword_score_pct']) ? $b['keyword_score_pct'] : 0;
            return $sa - $sb;
        });

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function getScoreImpact(): array
    {
        return [
            'critical' => 15,
            'warning' => 5,
        ];
    }

    public function getScoreWeight(): int
    {
        return 15;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
