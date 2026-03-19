<?php

namespace Adilis\SeoOptimizer\Pages;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditBrokenLinks;
use Adilis\SeoOptimizer\Audit\AuditHeadingHierarchy;
use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditInternalLinks;
use Adilis\SeoOptimizer\Audit\AuditKeywordCheck;
use Adilis\SeoOptimizer\Audit\AuditMetaTags;
use Adilis\SeoOptimizer\Audit\AuditMissingAlt;
use Adilis\SeoOptimizer\Audit\AuditRedirectedLinks;
use Adilis\SeoOptimizer\Audit\AuditPageLoadTime;
use Adilis\SeoOptimizer\Audit\AuditPageWeight;
use Adilis\SeoOptimizer\Audit\AuditTextRatio;
use Adilis\SeoOptimizer\Audit\AuditUnsecuredLinks;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

class PagesAggregator
{
    /** @var AuditInterface[] */
    private $audits;

    public function __construct()
    {
        $this->audits = [
            new AuditHeadingHierarchy(),
            new AuditMissingAlt(),
            new AuditBrokenLinks(),
            new AuditRedirectedLinks(),
            new AuditPageLoadTime(),
            new AuditPageWeight(),
            new AuditUnsecuredLinks(),
            new AuditMetaTags(),
            new AuditInternalLinks(),
            new AuditTextRatio(),
            new AuditKeywordCheck(),
        ];
    }

    /**
     * @return AuditInterface[]
     */
    public function getAudits(): array
    {
        return $this->audits;
    }

    /**
     * @return bool
     */
    public function hasData(): bool
    {
        return AuditRunStorage::hasAnyComplete();
    }

    /**
     * @return array<string, array>
     */
    public function aggregate(): array
    {
        $pages = [];

        // Read pages from DB (already has counters and scores)
        $idShop = (int) \Context::getContext()->shop->id;
        $pageRows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE id_shop = ' . $idShop . ' AND url != ""
            ORDER BY score ASC'
        );

        if (!$pageRows) {
            return $pages;
        }

        foreach ($pageRows as $row) {
            $url = $row['url'];
            $score = (float) $row['score'];
            $grade = $row['grade'] ?: '-';

            $pages[$url] = [
                'url' => $url,
                'entity_type' => $row['entity_type'],
                'id_entity' => (int) $row['id_entity'],
                'critical' => (int) $row['count_critical'],
                'warning' => (int) $row['count_warning'],
                'info' => (int) $row['count_info'],
                'total' => (int) $row['count_total'],
                'score' => $score,
                'grade' => $grade,
                'grade_color' => \SeoOptimizerPage::gradeToColor($grade),
                'issues' => [],
            ];
        }

        // Load issues from audit_result
        $allResults = AuditResultStorage::getAllGroupedByUrl($idShop);

        $auditMeta = [];
        foreach ($this->audits as $audit) {
            $auditMeta[$audit->getKey()] = [
                'title' => $audit->getTitle(),
                'icon' => $audit->getIcon(),
            ];
        }

        foreach ($allResults as $url => $results) {
            if (!isset($pages[$url])) {
                continue;
            }

            foreach ($results as $row) {
                $severity = $row['severity'] ?? 'info';
                if ($severity === 'good') {
                    continue;
                }

                $auditKey = $row['audit_key'] ?? '';
                $meta = isset($auditMeta[$auditKey]) ? $auditMeta[$auditKey] : ['title' => $auditKey, 'icon' => 'icon-search'];

                $pages[$url]['issues'][] = [
                    'audit' => $meta['title'],
                    'audit_key' => $auditKey,
                    'audit_icon' => $meta['icon'],
                    'severity' => $severity,
                    'message' => $row['message'] ?? '',
                    'type' => $row['type'] ?? '',
                ];
            }
        }

        // Sort by score ascending (worst first)
        uasort($pages, function ($a, $b) {
            return $a['score'] <=> $b['score'];
        });

        return $pages;
    }

    /**
     * @param string $url
     * @return array|null
     */
    public function getPageData(string $url)
    {
        $all = $this->aggregate();
        return isset($all[$url]) ? $all[$url] : null;
    }
}
