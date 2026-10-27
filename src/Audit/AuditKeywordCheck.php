<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\KeywordCheckObserver;

class AuditKeywordCheck implements AuditInterface
{
    /**
     * Severity of a missing keyword, per zone. Zones that are not listed
     * (URL, image alt, category) are displayed but do not count as issues.
     */
    const ZONE_SEVERITY = [
        'meta_title' => 'warning',
        'h1' => 'warning',
        'meta_description' => 'info',
        'content_start' => 'info',
    ];

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
        return 'Checks if the target keywords of each page are present in its key SEO zones. A keyword missing from the title or the H1 is a warning; a keyword missing from the meta description or the first 100 words is only a hint. URL, image alt and category are shown for information. Matching is accent-insensitive with partial term support.';
    }

    public function getIcon(): string
    {
        return 'crosshair';
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
            'missing_zones' => 'Missing in',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumnCallbacks(): array
    {
        return [
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
            ['key' => 'pages_without_keywords', 'label' => 'Pages without keywords', 'type' => 'custom'],
            ['key' => 'total_keywords_checked', 'label' => 'Keywords checked', 'type' => 'custom'],
            ['key' => 'issues', 'label' => 'Issues found', 'type' => 'total_issues', 'warning_if_positive' => true],
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
                // Page without keywords: informational only
                $results[] = [
                    'url' => $url,
                    'type' => 'no_keywords',
                    'severity' => 'info',
                    'message' => 'No target keyword defined',
                    'keyword' => '-',
                    'zones_summary' => '-',
                    'missing_zones' => 'No keywords to check',
                ];
                continue;
            }

            foreach ($pageData['checks'] as $check) {
                $foundZones = [];
                $missingZones = [];
                $missingCounted = [];
                $severity = 'good';
                foreach ($check['zones'] as $zoneName => $zoneInfo) {
                    $label = isset(self::ZONE_LABELS[$zoneName]) ? self::ZONE_LABELS[$zoneName] : $zoneName;
                    if ($zoneInfo['found']) {
                        $foundZones[] = $label;
                        continue;
                    }

                    $missingZones[] = $label;
                    if (isset(self::ZONE_SEVERITY[$zoneName])) {
                        $missingCounted[] = $label;
                        if (self::ZONE_SEVERITY[$zoneName] === 'warning') {
                            $severity = 'warning';
                        } elseif ($severity === 'good') {
                            $severity = 'info';
                        }
                    }
                }

                // Only report issues
                if ($severity === 'good') {
                    continue;
                }

                $results[] = [
                    'url' => $url,
                    'type' => 'keyword_missing',
                    'severity' => $severity,
                    'message' => 'Keyword missing in: ' . implode(', ', $missingCounted),
                    'keyword' => $check['keyword'],
                    'zones_summary' => implode(', ', $foundZones) ?: 'None',
                    'missing_zones' => implode(', ', $missingZones) ?: '-',
                ];
            }
        }

        // Warnings first
        $order = ['warning' => 0, 'info' => 1];
        usort($results, function ($a, $b) use ($order) {
            $oa = isset($order[$a['severity']]) ? $order[$a['severity']] : 2;
            $ob = isset($order[$b['severity']]) ? $order[$b['severity']] : 2;

            return $oa - $ob;
        });

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function getScoreImpact(): array
    {
        return [
            'warning' => 5,
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
