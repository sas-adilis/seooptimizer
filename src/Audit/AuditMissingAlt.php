<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;

use Adilis\SeoOptimizer\Utils\Message;

class AuditMissingAlt implements AuditInterface
{
    public function getKey(): string
    {
        return 'missing_alt';
    }

    public function getTitle(): string
    {
        return Message::tr('Missing image alt attributes');
    }

    public function getDescription(): string
    {
        return Message::tr('Crawls all pages of your site and detects images without an alt attribute, which is essential for accessibility and SEO image indexing. Empty alt attributes are only reported for information, as they are the correct way to mark decorative images.');
    }

    public function getIcon(): string
    {
        return 'image';
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
            ['key' => 'crawled', 'label' => Message::tr('Pages crawled'), 'type' => 'crawled'],
            ['key' => 'missing', 'label' => Message::tr('Missing alt'), 'type' => 'count_severity', 'value' => 'critical', 'danger_if_positive' => true],
            ['key' => 'empty', 'label' => Message::tr('Empty alt'), 'type' => 'count_severity', 'value' => 'info'],
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
            'src' => Message::tr('Image'),
            'message' => Message::tr('Issue'),
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
        ];
    }

    public function getScoreWeight(): int
    {
        return 15;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
