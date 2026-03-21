<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\MetaTagsObserver;

class AuditMetaTags implements AuditInterface
{
    public function getKey(): string
    {
        return 'meta_tags';
    }

    public function getTitle(): string
    {
        return 'Meta tags (title & description)';
    }

    public function getDescription(): string
    {
        return 'Crawls all pages and checks the <title> tag and meta description for missing, empty, too short or too long values. Thresholds are configurable in the Configuration tab.';
    }

    public function getIcon(): string
    {
        return 'text-align-left';
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
            'field' => 'Field',
            'message' => 'Issue',
            'length' => 'Length',
            'value_preview' => 'Preview',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        $titleMin = (int) \Configuration::get('SEOO_TITLE_MIN_LENGTH') ?: 50;
        $titleMax = (int) \Configuration::get('SEOO_TITLE_MAX_LENGTH') ?: 70;
        $descMin = (int) \Configuration::get('SEOO_META_TITLE_MIN_LENGTH') ?: 140;
        $descMax = (int) \Configuration::get('SEOO_META_TITLE_MAX_LENGTH') ?: 170;

        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            [
                'key' => 'good_count',
                'label' => 'Good (title ' . $titleMin . '-' . $titleMax . ' / desc ' . $descMin . '-' . $descMax . ')',
                'type' => 'custom',
            ],
            [
                'key' => 'warning_count',
                'label' => 'Warnings (too short/long)',
                'type' => 'custom',
                'warning_if_positive' => true,
            ],
            [
                'key' => 'critical_count',
                'label' => 'Critical (missing/empty)',
                'type' => 'custom',
                'danger_if_positive' => true,
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            MetaTagsObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $metaResults = $observerResults['meta_tags'] ?? [];

        foreach ($metaResults as $url => $data) {
            foreach ($data['issues'] as $issue) {
                $preview = '';
                if ($issue['field'] === 'title' && $data['title'] !== null) {
                    $preview = mb_substr($data['title'], 0, 60);
                    if (mb_strlen($data['title']) > 60) {
                        $preview .= '...';
                    }
                } elseif ($issue['field'] === 'description' && $data['description'] !== null) {
                    $preview = mb_substr($data['description'], 0, 60);
                    if (mb_strlen($data['description']) > 60) {
                        $preview .= '...';
                    }
                }

                $results[] = [
                    'url' => $url,
                    'type' => $issue['type'],
                    'severity' => $issue['severity'],
                    'field' => ucfirst($issue['field']),
                    'message' => $issue['message'],
                    'length' => $issue['length'],
                    'value_preview' => $preview,
                ];
            }
        }

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function getScoreImpact(): array
    {
        return [
            'critical' => 25,
            'warning' => 8,
        ];
    }

    public function getScoreWeight(): int
    {
        return 20;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
