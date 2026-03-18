<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;

class AuditMissingAlt implements AuditInterface
{
    public function getKey(): string
    {
        return 'missing_alt';
    }

    public function getTitle(): string
    {
        return 'Missing image alt attributes';
    }

    public function getDescription(): string
    {
        return 'Crawls all pages of your site and detects images with missing or empty alt attributes. Alt text is essential for accessibility and SEO image indexing.';
    }

    public function getIcon(): string
    {
        return 'icon-picture';
    }

    public function getVisual(): string
    {
        return 'panda-missing-alt.png';
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'missing', 'label' => 'Missing alt', 'type' => 'count_severity', 'value' => 'critical', 'danger_if_positive' => true],
            ['key' => 'empty', 'label' => 'Empty alt', 'type' => 'count_severity', 'value' => 'warning', 'warning_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            MissingAltAttributeObserver::class,
        ];
    }

    /**
     * @return array
     */
    public function getResultColumns(): array
    {
        return [
            'src' => 'Image',
            'message' => 'Issue',
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $altResults = $observerResults['missing_alt'] ?? [];

        foreach ($altResults as $url => $data) {
            foreach ($data['issues'] as $issue) {
                $results[] = [
                    'url' => $url,
                    'type' => $issue['type'],
                    'severity' => $issue['severity'],
                    'message' => $issue['message'],
                    'src' => $issue['src'],
                    'total_images' => $data['total_images'],
                    'missing_alt' => $data['missing_alt'],
                    'empty_alt' => $data['empty_alt'],
                ];
            }
        }

        return $results;
    }

    public function getScoreImpact(): array
    {
        return [
            'critical' => 5,
            'warning' => 2,
        ];
    }

    public function getScoreWeight(): int
    {
        return 15;
    }
}
