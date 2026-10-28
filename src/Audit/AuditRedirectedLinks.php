<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\RedirectedLinksObserver;

use Adilis\SeoOptimizer\Utils\Message;

class AuditRedirectedLinks implements AuditInterface
{
    public function getKey(): string
    {
        return 'redirected_links';
    }

    public function getTitle(): string
    {
        return Message::tr('Redirected links');
    }

    public function getDescription(): string
    {
        return Message::tr('Detects internal links that point to a URL which redirects (301, 302) to another page. These links should be updated to point directly to the final destination to avoid unnecessary redirects, improve page load speed and preserve link equity.');
    }

    public function getIcon(): string
    {
        return 'arrows-split';
    }

    public function getVisual(): string
    {
        return 'panda-redirect-links.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'link_url' => Message::tr('Link URL'),
            'link_text' => Message::tr('Link text'),
            'http_code' => Message::tr('HTTP code'),
            'redirect_to' => Message::tr('Redirects to'),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => Message::tr('Pages crawled'), 'type' => 'crawled'],
            ['key' => 'links_checked', 'label' => Message::tr('Links checked'), 'type' => 'custom'],
            ['key' => 'redirected_count', 'label' => Message::tr('Redirected links'), 'type' => 'custom', 'warning_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            RedirectedLinksObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $entries = $observerResults['redirected_links'] ?? [];

        foreach ($entries as $entry) {
            $severity = $entry['http_code'] === 301 ? 'warning' : 'info';

            $results[] = [
                'url' => $entry['page_url'],
                'type' => 'redirected_link',
                'severity' => $severity,
                'message' => Message::t('HTTP %d → %s', $entry['http_code'], $entry['redirect_to']),
                'link_url' => $entry['link_url'],
                'link_text' => $entry['link_text'],
                'http_code' => $entry['http_code'],
                'redirect_to' => $entry['redirect_to'],
            ];
        }

        return $results;
    }

    public function getScoreImpact(): array
    {
        return [
            'warning' => 5,
            'info' => 2,
        ];
    }

    public function getScoreWeight(): int
    {
        return 10;
    }

    public function requiresIndexablePage(): bool
    {
        return false;
    }
}
