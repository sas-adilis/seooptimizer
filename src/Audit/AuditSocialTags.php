<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\SocialTagsObserver;
use Adilis\SeoOptimizer\Utils\Message;

class AuditSocialTags implements AuditInterface
{
    public function getKey(): string
    {
        return 'social_tags';
    }

    public function getTitle(): string
    {
        return Message::tr('Social and technical tags');
    }

    public function getDescription(): string
    {
        return Message::tr('Crawls all pages and checks the Open Graph and Twitter card tags, which control how a shared link looks, as well as the language of the page and the viewport tag required by mobile-first indexing.');
    }

    public function getIcon(): string
    {
        return 'share-network';
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
            ['key' => 'issues', 'label' => Message::tr('Tag issues'), 'type' => 'total_issues', 'warning_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            SocialTagsObserver::class,
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

        foreach ($observerResults['social_tags'] ?? [] as $url => $data) {
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
            'warning' => 8,
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
