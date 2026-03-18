<?php

namespace Adilis\SeoOptimizer\Pages;

use Adilis\SeoOptimizer\Audit\AuditBrokenLinks;
use Adilis\SeoOptimizer\Audit\AuditHeadingHierarchy;
use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditMetaTags;
use Adilis\SeoOptimizer\Audit\AuditInternalLinks;
use Adilis\SeoOptimizer\Audit\AuditTextRatio;
use Adilis\SeoOptimizer\Audit\AuditMissingAlt;
use Adilis\SeoOptimizer\Audit\AuditPageLoadTime;
use Adilis\SeoOptimizer\Audit\AuditPageWeight;
use Adilis\SeoOptimizer\Audit\AuditUnsecuredLinks;
use Adilis\SeoOptimizer\CacheManager;

class PagesAggregator
{
    /** @var AuditInterface[] */
    private $audits;

    /** @var array<string, array|null> */
    private $auditStates = [];

    public function __construct()
    {
        $this->audits = [
            new AuditHeadingHierarchy(),
            new AuditMissingAlt(),
            new AuditBrokenLinks(),
            new AuditPageLoadTime(),
            new AuditPageWeight(),
            new AuditUnsecuredLinks(),
            new AuditMetaTags(),
            new AuditInternalLinks(),
            new AuditTextRatio(),
        ];

        foreach ($this->audits as $audit) {
            $this->auditStates[$audit->getKey()] = CacheManager::get('audit_' . $audit->getKey());
        }
    }

    /**
     * @return AuditInterface[]
     */
    public function getAudits(): array
    {
        return $this->audits;
    }

    /**
     * @return array<string, array|null>
     */
    public function getAuditStates(): array
    {
        return $this->auditStates;
    }

    /**
     * @return bool
     */
    public function hasData(): bool
    {
        foreach ($this->auditStates as $state) {
            if ($state && isset($state['status']) && $state['status'] === 'complete') {
                return true;
            }
        }

        return false;
    }

    /**
     * Aggregate all audit results grouped by page URL.
     *
     * @return array<string, array>
     */
    public function aggregate(): array
    {
        $pages = [];

        foreach ($this->audits as $audit) {
            $key = $audit->getKey();
            $state = $this->auditStates[$key];

            if (!$state || !isset($state['status']) || $state['status'] !== 'complete') {
                continue;
            }

            // Register all crawled URLs (even those without issues)
            if (isset($state['urls'])) {
                foreach ($state['urls'] as $entry) {
                    $url = is_array($entry) ? ($entry['url'] ?? '') : (string) $entry;
                    if ($url !== '' && !isset($pages[$url])) {
                        $pages[$url] = [
                            'url' => $url,
                            'critical' => 0,
                            'warning' => 0,
                            'info' => 0,
                            'good' => 0,
                            'total' => 0,
                            'issues' => [],
                        ];
                    }
                }
            }

            $results = $state['results'] ?? [];
            foreach ($results as $row) {
                $url = $row['url'] ?? '';
                if ($url === '') {
                    continue;
                }

                if (!isset($pages[$url])) {
                    $pages[$url] = [
                        'url' => $url,
                        'critical' => 0,
                        'warning' => 0,
                        'info' => 0,
                        'good' => 0,
                        'total' => 0,
                        'issues' => [],
                    ];
                }

                $severity = $row['severity'] ?? 'info';

                // Don't count 'good' as issues
                if ($severity === 'good') {
                    $pages[$url]['good']++;
                    continue;
                }

                if (isset($pages[$url][$severity])) {
                    $pages[$url][$severity]++;
                } else {
                    $pages[$url]['info']++;
                }

                $pages[$url]['total']++;
                $pages[$url]['issues'][] = [
                    'audit' => $audit->getTitle(),
                    'audit_key' => $key,
                    'audit_icon' => $audit->getIcon(),
                    'severity' => $severity,
                    'message' => $row['message'] ?? '',
                    'type' => $row['type'] ?? '',
                ];
            }
        }

        // Sort by total issues descending, then critical descending
        uasort($pages, function ($a, $b) {
            if ($b['critical'] !== $a['critical']) {
                return $b['critical'] - $a['critical'];
            }
            if ($b['warning'] !== $a['warning']) {
                return $b['warning'] - $a['warning'];
            }

            return $b['total'] - $a['total'];
        });

        return $pages;
    }

    /**
     * Get aggregated data for a single URL.
     *
     * @param string $url
     * @return array|null
     */
    public function getPageData(string $url)
    {
        $all = $this->aggregate();

        return isset($all[$url]) ? $all[$url] : null;
    }
}
