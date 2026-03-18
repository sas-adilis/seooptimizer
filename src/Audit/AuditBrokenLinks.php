<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CrawlerObserver\BrokenLinksObserver;

class AuditBrokenLinks implements AuditInterface
{
    public function getKey(): string
    {
        return 'broken_links';
    }

    public function getTitle(): string
    {
        return 'Broken links (404)';
    }

    public function getDescription(): string
    {
        return 'Crawls all pages and checks every internal link (pages, images, scripts, stylesheets) for 404 errors. Broken links hurt SEO and user experience.';
    }

    public function getIcon(): string
    {
        return 'icon-unlink';
    }

    public function getVisual(): string
    {
        return 'panda-broken-links.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'broken_url' => 'Broken URL',
            'link_text' => 'Link text',
            'http_code' => 'HTTP code',
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
            ['key' => 'issues', 'label' => 'Broken links', 'type' => 'total_issues', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            BrokenLinksObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $brokenLinks = $observerResults['broken_links'] ?? [];

        foreach ($brokenLinks as $entry) {
            $results[] = [
                'url' => $entry['page_url'],
                'type' => 'broken_link',
                'severity' => $entry['http_code'] === 404 ? 'critical' : 'warning',
                'message' => sprintf('HTTP %d', $entry['http_code']),
                'broken_url' => $entry['broken_url'],
                'link_text' => $entry['link_text'],
                'http_code' => $entry['http_code'],
            ];
        }

        return $results;
    }

    public function getScoreImpact(): array
    {
        return [
            'critical' => 20,
            'warning' => 10,
        ];
    }

    public function getScoreWeight(): int
    {
        return 25;
    }
}
