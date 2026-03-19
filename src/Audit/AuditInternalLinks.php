<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\InternalLinksObserver;

class AuditInternalLinks implements AuditInterface
{
    const MIN_OUTGOING_LINKS = 3;

    public function getKey(): string
    {
        return 'internal_links';
    }

    public function getTitle(): string
    {
        return 'Internal links';
    }

    public function getDescription(): string
    {
        return 'Analyzes the internal linking structure of your site: pages with no or too few outgoing internal links, orphan pages with no incoming links, empty anchor texts and nofollow links.';
    }

    public function getIcon(): string
    {
        return 'icon-link';
    }

    public function getVisual(): string
    {
        return 'panda-internal-links.png';
    }

    /**
     * @return array<string, string>
     */
    public function getResultColumns(): array
    {
        return [
            'message' => 'Issue',
            'outgoing' => 'Outgoing',
            'incoming' => 'Incoming',
            'detail' => 'Detail',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getKpiDefinitions(): array
    {
        return [
            ['key' => 'crawled', 'label' => 'Pages crawled', 'type' => 'crawled'],
            ['key' => 'links_checked', 'label' => 'Internal links found', 'type' => 'custom'],
            ['key' => 'no_outgoing_count', 'label' => 'No outgoing links', 'type' => 'custom', 'danger_if_positive' => true],
            ['key' => 'few_outgoing_count', 'label' => 'Few outgoing links (< ' . self::MIN_OUTGOING_LINKS . ')', 'type' => 'custom', 'warning_if_positive' => true],
            ['key' => 'critical_issues', 'label' => 'Critical (orphan/no links)', 'type' => 'count_severity', 'value' => 'critical', 'danger_if_positive' => true],
        ];
    }

    /**
     * @return string[]
     */
    public function getObserverClasses(): array
    {
        return [
            InternalLinksObserver::class,
        ];
    }

    /**
     * @param array $observerResults
     * @return array
     */
    public function formatResults(array $observerResults): array
    {
        $results = [];
        $data = $observerResults['internal_links'] ?? [];
        $outgoing = $data['outgoing'] ?? [];
        $incoming = $data['incoming'] ?? [];

        // Collect all crawled URLs to detect orphans
        $allCrawledUrls = array_keys($outgoing);

        foreach ($outgoing as $url => $pageData) {
            $count = $pageData['unique_count'];
            $emptyAnchors = $pageData['empty_anchors'];
            $nofollowCount = $pageData['nofollow_count'];

            // No outgoing internal links
            if ($count === 0) {
                $results[] = [
                    'url' => $url,
                    'type' => 'no_outgoing',
                    'severity' => 'critical',
                    'message' => 'No outgoing internal links',
                    'outgoing' => 0,
                    'incoming' => $this->getIncomingCount($url, $incoming),
                    'detail' => '',
                ];
            } elseif ($count < self::MIN_OUTGOING_LINKS) {
                // Too few outgoing internal links
                $results[] = [
                    'url' => $url,
                    'type' => 'few_outgoing',
                    'severity' => 'warning',
                    'message' => sprintf('Only %d unique outgoing internal link(s)', $count),
                    'outgoing' => $count,
                    'incoming' => $this->getIncomingCount($url, $incoming),
                    'detail' => '',
                ];
            }

            // Empty anchor texts
            if ($emptyAnchors > 0) {
                $results[] = [
                    'url' => $url,
                    'type' => 'empty_anchor',
                    'severity' => 'warning',
                    'message' => sprintf('%d link(s) with empty anchor text', $emptyAnchors),
                    'outgoing' => $count,
                    'incoming' => $this->getIncomingCount($url, $incoming),
                    'detail' => sprintf('%d empty', $emptyAnchors),
                ];
            }

            // Too many nofollow on internal links
            if ($nofollowCount > 0 && $pageData['count'] > 0) {
                $pct = round(($nofollowCount / $pageData['count']) * 100);
                if ($pct > 50) {
                    $results[] = [
                        'url' => $url,
                        'type' => 'excessive_nofollow',
                        'severity' => 'warning',
                        'message' => sprintf('%d/%d internal links are nofollow (%d%%)', $nofollowCount, $pageData['count'], $pct),
                        'outgoing' => $count,
                        'incoming' => $this->getIncomingCount($url, $incoming),
                        'detail' => sprintf('%d nofollow', $nofollowCount),
                    ];
                }
            }
        }

        // Detect orphan pages (crawled pages that receive 0 incoming internal links)
        foreach ($allCrawledUrls as $url) {
            $cleanUrl = preg_replace('/#.*$/', '', $url);
            $inCount = isset($incoming[$cleanUrl]) ? $incoming[$cleanUrl] : 0;
            if ($inCount === 0) {
                $outCount = isset($outgoing[$url]) ? $outgoing[$url]['unique_count'] : 0;
                $results[] = [
                    'url' => $url,
                    'type' => 'orphan_page',
                    'severity' => 'critical',
                    'message' => 'Orphan page — no internal link points to this page',
                    'outgoing' => $outCount,
                    'incoming' => 0,
                    'detail' => 'Orphan',
                ];
            }
        }

        return $results;
    }

    /**
     * @param string $url
     * @param array<string, int> $incoming
     * @return int
     */
    private function getIncomingCount(string $url, array $incoming): int
    {
        $cleanUrl = preg_replace('/#.*$/', '', $url);

        return isset($incoming[$cleanUrl]) ? $incoming[$cleanUrl] : 0;
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
        return 15;
    }

    public function requiresIndexablePage(): bool
    {
        return true;
    }
}
