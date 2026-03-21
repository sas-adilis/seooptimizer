<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\HeadingHierarchyObserver;

class AuditHeadingHierarchy implements AuditInterface
{
    public function getKey(): string
    {
        return 'heading_hierarchy';
    }

    public function getTitle(): string
    {
        return 'Heading hierarchy (H1-H6)';
    }

    public function getDescription(): string
    {
        return 'Crawls all pages of your site and analyzes the heading structure (H1 to H6): missing H1, duplicates, skipped levels, length issues.';
    }

    public function getIcon(): string
    {
        return 'text-h-one';
    }

    public function getVisual(): string
    {
        return 'panda-headings.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'message' => 'Issue',
            'h1' => 'H1',
            'h2' => 'H2',
            'h3' => 'H3',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'critical', 'label' => 'Critical', 'type' => 'count_severity', 'value' => 'critical', 'danger_if_positive' => true],
            ['key' => 'warnings', 'label' => 'Warnings', 'type' => 'count_severity', 'value' => 'warning', 'warning_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            HeadingHierarchyObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $headingResults = $observerResults['heading_hierarchy'] ?? [];

        foreach ($headingResults as $url => $data) {
            foreach ($data['issues'] as $issue) {
                $results[] = [
                    'url' => $url,
                    'type' => $issue['type'],
                    'severity' => $issue['severity'],
                    'message' => $issue['message'],
                    'h1' => $data['counts']['h1'],
                    'h2' => $data['counts']['h2'],
                    'h3' => $data['counts']['h3'],
                ];
            }
        }

        return $results;
    }

    public function getScoreImpact(): array
    {
        return [
            'critical' => 30,
            'warning' => 10,
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
