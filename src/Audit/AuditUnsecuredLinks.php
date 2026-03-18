<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CrawlerObserver\UnsecuredLinksAuditObserver;

class AuditUnsecuredLinks implements AuditInterface
{
    public function getKey(): string
    {
        return 'unsecured_links';
    }

    public function getTitle(): string
    {
        return 'Unsecured links (HTTP)';
    }

    public function getDescription(): string
    {
        return 'Crawls all pages and detects resources loaded over HTTP instead of HTTPS. Mixed content degrades security, triggers browser warnings, and can negatively impact SEO ranking.';
    }

    public function getIcon(): string
    {
        return 'icon-unlock';
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
            'unsecured_url' => 'Unsecured URL',
            'element' => 'Element',
            'link_text' => 'Link text',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'links_checked', 'label' => 'Links checked', 'type' => 'custom'],
            ['key' => 'issues', 'label' => 'Unsecured links', 'type' => 'total_issues', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            UnsecuredLinksAuditObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $entries = $observerResults['unsecured_links_audit'] ?? [];

        foreach ($entries as $entry) {
            $results[] = [
                'url' => $entry['page_url'],
                'type' => 'unsecured_link',
                'severity' => 'warning',
                'message' => 'HTTP resource: ' . $entry['element'],
                'unsecured_url' => $entry['unsecured_url'],
                'element' => $entry['element'],
                'link_text' => $entry['link_text'],
            ];
        }

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
        return 15;
    }
}
