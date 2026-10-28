<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\IndexationObserver;
use Adilis\SeoOptimizer\Utils\Message;

class AuditIndexation implements AuditInterface
{
    public function getKey(): string
    {
        return 'indexation';
    }

    public function getTitle(): string
    {
        return Message::tr('Indexation');
    }

    public function getDescription(): string
    {
        return Message::tr('Crawls all pages and checks the signals that decide whether they are indexed: noindex (meta tag or HTTP header), robots.txt, canonical URL, redirection, hreflang and URL quality. A page that cannot be indexed will never rank.');
    }

    public function getIcon(): string
    {
        return 'eye';
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
            'message' => Message::tr('Issue'),
            'detail' => Message::tr('Detail'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => Message::tr('Pages crawled'), 'type' => 'crawled'],
            ['key' => 'issues', 'label' => Message::tr('Indexation issues'), 'type' => 'total_issues', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            IndexationObserver::class,
        ];
    }

    /**
     * Only the problems are stored: the checks that pass are displayed by the front-office panel.
     *
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];

        foreach ($observerResults['indexation'] ?? [] as $url => $data) {
            foreach ($data['checks'] as $check) {
                if ($check['severity'] !== 'critical' && $check['severity'] !== 'warning') {
                    continue;
                }

                $results[] = [
                    'url' => $url,
                    'type' => $check['type'],
                    'severity' => $check['severity'],
                    'message' => $check['message'],
                    'detail' => $check['detail'],
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
            'critical' => 40,
            'warning' => 10,
        ];
    }

    public function getScoreWeight(): int
    {
        return 15;
    }

    public function requiresIndexablePage(): bool
    {
        return false;
    }
}
