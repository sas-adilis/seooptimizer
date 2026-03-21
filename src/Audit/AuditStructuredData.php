<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\StructuredDataObserver;

class AuditStructuredData implements AuditInterface
{
    public function getKey(): string
    {
        return 'structured_data';
    }

    public function getTitle(): string
    {
        return 'Structured data (Schema.org)';
    }

    public function getDescription(): string
    {
        return 'Checks JSON-LD structured data for missing schemas, duplicates, and malformed blocks.';
    }

    public function getIcon(): string
    {
        return 'brackets-curly';
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
            'schema_type' => 'Schema',
            'message' => 'Issue',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            [
                'key' => 'critical_count',
                'label' => 'Critical',
                'type' => 'count_severity',
                'value' => 'critical',
                'danger_if_positive' => true,
            ],
            [
                'key' => 'warning_count',
                'label' => 'Warnings',
                'type' => 'count_severity',
                'value' => 'warning',
                'warning_if_positive' => true,
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            StructuredDataObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $sdResults = $observerResults['structured_data'] ?? [];

        foreach ($sdResults as $url => $data) {
            foreach ($data['issues'] as $issue) {
                // Skip info-level issues for BO storage (no_faq, etc.)
                if ($issue['severity'] === 'info') {
                    continue;
                }

                $results[] = [
                    'url' => $url,
                    'type' => $issue['type'],
                    'severity' => $issue['severity'],
                    'schema_type' => $issue['schema_type'],
                    'message' => $issue['message'],
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
            'critical' => 15,
            'warning' => 5,
        ];
    }

    public function getScoreWeight(): int
    {
        return 8;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
