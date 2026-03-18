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
use Adilis\SeoOptimizer\CrawlerObserver\CrawlerObserverInterface;
use Adilis\SeoOptimizer\SitemapIndexer\SitemapIndexer;

class FullAuditRunner
{
    const CACHE_KEY = 'full_audit';
    const URLS_PER_BATCH = 5;

    /**
     * @var array<string, string>
     */
    private static $typeLabels = [
        'product' => 'Products',
        'category' => 'Categories',
        'cms' => 'CMS pages',
        'manufacturer' => 'Manufacturers',
        'supplier' => 'Suppliers',
        'cms_category' => 'CMS categories',
        'meta' => 'Pages',
        'module' => 'Modules',
    ];

    /**
     * @var array<string, string>
     */
    private static $typeIcons = [
        'product' => 'icon-tag',
        'category' => 'icon-th-large',
        'cms' => 'icon-file-text',
        'manufacturer' => 'icon-building',
        'supplier' => 'icon-truck',
        'cms_category' => 'icon-folder',
        'meta' => 'icon-globe',
        'module' => 'icon-puzzle-piece',
    ];

    /** @var AuditInterface[] */
    private $audits;

    /** @var array|null */
    private $state;

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
    }

    /**
     * @return AuditInterface[]
     */
    public function getAudits(): array
    {
        return $this->audits;
    }

    /**
     * @param bool $firstProcess
     */
    public function run(bool $firstProcess)
    {
        if ($firstProcess) {
            $this->initState();
            $this->returnJson('success');
        }

        $this->state = CacheManager::get(self::CACHE_KEY);

        if (!$this->state || $this->state['status'] === 'complete') {
            $this->returnJson('done');
        }

        $offset = $this->state['crawled'];
        $batch = array_slice($this->state['urls'], $offset, self::URLS_PER_BATCH);

        if (empty($batch)) {
            $this->finalize();
            $this->returnJson('done');
        }

        $this->processBatch($batch);

        $this->state['crawled'] += count($batch);

        if ($this->state['crawled'] >= $this->state['total_urls']) {
            $this->finalize();
            CacheManager::write(self::CACHE_KEY, $this->state);
            $this->returnJson('done');
        }

        CacheManager::write(self::CACHE_KEY, $this->state);
        $this->returnJson('success');
    }

    private function initState()
    {
        $urlsByType = $this->collectAllUrls();

        $urls = [];
        $items = [];
        foreach ($urlsByType as $type => $typeUrls) {
            $count = count($typeUrls);
            $items[$type] = [
                'label' => isset(self::$typeLabels[$type]) ? self::$typeLabels[$type] : ucfirst($type),
                'icon' => isset(self::$typeIcons[$type]) ? self::$typeIcons[$type] : 'icon-file-text',
                'total' => $count,
                'crawled' => 0,
                'issues_count' => 0,
                'percentage' => 0,
                'status' => 'waiting',
            ];
            foreach ($typeUrls as $url) {
                $urls[] = ['url' => $url, 'type' => $type];
            }
        }

        // Initialize per-audit state caches (empty results, same URL set)
        foreach ($this->audits as $audit) {
            $auditState = [
                'status' => 'running',
                'urls' => $urls,
                'total_urls' => count($urls),
                'crawled' => 0,
                'results' => [],
                'items' => $items,
                'custom_kpis' => [],
                'date' => date('Y-m-d H:i:s'),
            ];
            CacheManager::write('audit_' . $audit->getKey(), $auditState);
        }

        $this->state = [
            'status' => 'running',
            'urls' => $urls,
            'total_urls' => count($urls),
            'crawled' => 0,
            'items' => $items,
            'audit_results' => [],
            'audit_custom_kpis' => [],
            'date' => date('Y-m-d H:i:s'),
        ];

        // Initialize per-audit tracking
        foreach ($this->audits as $audit) {
            $this->state['audit_results'][$audit->getKey()] = [];
            $this->state['audit_custom_kpis'][$audit->getKey()] = [];
        }

        CacheManager::write(self::CACHE_KEY, $this->state);
    }

    /**
     * @param array $batch
     */
    private function processBatch(array $batch)
    {
        // Create all observers for all audits
        $auditObservers = [];
        foreach ($this->audits as $audit) {
            $observers = [];
            foreach ($audit->getObserverClasses() as $observerClass) {
                $observers[] = new $observerClass();
            }
            $auditObservers[$audit->getKey()] = [
                'audit' => $audit,
                'observers' => $observers,
            ];
        }

        foreach ($batch as $entry) {
            $url = $entry['url'];
            $type = $entry['type'];

            // observeBeforeRequest for all observers
            foreach ($auditObservers as $data) {
                foreach ($data['observers'] as $observer) {
                    if (method_exists($observer, 'observeBeforeRequest')) {
                        $observer->observeBeforeRequest($url);
                    }
                }
            }

            // Single fetch
            $content = $this->fetchUrl($url);

            if ($content !== false) {
                // observeAfterRequest for all observers
                foreach ($auditObservers as $data) {
                    foreach ($data['observers'] as $observer) {
                        if (method_exists($observer, 'observeAfterRequest')) {
                            $observer->observeAfterRequest($url, $content);
                        }
                    }
                }
            }

            // Update per-type progress
            if (isset($this->state['items'][$type])) {
                $this->state['items'][$type]['crawled']++;
                $total = $this->state['items'][$type]['total'];
                $crawled = $this->state['items'][$type]['crawled'];
                $this->state['items'][$type]['percentage'] = $total > 0 ? round(($crawled / $total) * 100) : 0;
                $this->state['items'][$type]['status'] = $crawled >= $total ? 'done' : 'processing';
            }
        }

        // Collect results per audit and accumulate
        $totalIssuesByType = [];

        foreach ($auditObservers as $auditKey => $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];
            $observers = $data['observers'];

            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();

                // Collect custom KPIs
                $this->collectCustomKpis($observer, $auditKey);
            }

            $newResults = $audit->formatResults($observerResults);

            // Accumulate results
            $this->state['audit_results'][$auditKey] = array_merge(
                $this->state['audit_results'][$auditKey],
                $newResults
            );

            // Count issues per type from batch
            foreach ($newResults as $row) {
                foreach ($batch as $batchEntry) {
                    if ($batchEntry['url'] === ($row['url'] ?? '')) {
                        $batchType = $batchEntry['type'];
                        if (!isset($totalIssuesByType[$batchType])) {
                            $totalIssuesByType[$batchType] = 0;
                        }
                        $totalIssuesByType[$batchType]++;
                        break;
                    }
                }
            }
        }

        // Update global issues count per type
        foreach ($totalIssuesByType as $type => $count) {
            if (isset($this->state['items'][$type])) {
                $this->state['items'][$type]['issues_count'] += $count;
            }
        }
    }

    /**
     * @param CrawlerObserverInterface $observer
     * @param string $auditKey
     */
    private function collectCustomKpis($observer, string $auditKey)
    {
        $kpis = &$this->state['audit_custom_kpis'][$auditKey];

        $methods = [
            'getLinksChecked' => 'links_checked',
            'getGoodCount' => 'good_count',
            'getMediumCount' => 'medium_count',
            'getSlowCount' => 'slow_count',
            'getLightCount' => 'light_count',
            'getModerateCount' => 'moderate_count',
            'getHeavyCount' => 'heavy_count',
            'getWarningCount' => 'warning_count',
            'getCriticalCount' => 'critical_count',
            'getNoOutgoingCount' => 'no_outgoing_count',
            'getFewOutgoingCount' => 'few_outgoing_count',
            'getLowCount' => 'low_count',
        ];

        foreach ($methods as $method => $kpiKey) {
            if (method_exists($observer, $method)) {
                if (!isset($kpis[$kpiKey])) {
                    $kpis[$kpiKey] = 0;
                }
                $kpis[$kpiKey] += $observer->$method();
            }
        }
    }

    private function finalize()
    {
        $this->state['status'] = 'complete';
        foreach ($this->state['items'] as &$item) {
            $item['status'] = 'done';
            $item['percentage'] = 100;
        }
        unset($item);

        // Write each audit's final cache
        foreach ($this->audits as $audit) {
            $auditKey = $audit->getKey();
            $auditState = [
                'status' => 'complete',
                'urls' => $this->state['urls'],
                'total_urls' => $this->state['total_urls'],
                'crawled' => $this->state['crawled'] ?? $this->state['total_urls'],
                'results' => $this->state['audit_results'][$auditKey] ?? [],
                'items' => $this->buildAuditItems($auditKey),
                'custom_kpis' => $this->state['audit_custom_kpis'][$auditKey] ?? [],
                'date' => date('Y-m-d H:i:s'),
            ];
            CacheManager::write('audit_' . $auditKey, $auditState);
        }
    }

    /**
     * Build per-type items with issues_count specific to one audit.
     *
     * @param string $auditKey
     * @return array
     */
    private function buildAuditItems(string $auditKey): array
    {
        $results = $this->state['audit_results'][$auditKey] ?? [];
        $items = $this->state['items'];

        // Build URL-to-type map
        $urlTypeMap = [];
        foreach ($this->state['urls'] as $entry) {
            $urlTypeMap[$entry['url']] = $entry['type'];
        }

        // Reset issues_count for this audit
        foreach ($items as &$item) {
            $item['issues_count'] = 0;
        }
        unset($item);

        // Count issues per type for this audit
        foreach ($results as $row) {
            $url = $row['url'] ?? '';
            if (isset($urlTypeMap[$url]) && isset($items[$urlTypeMap[$url]])) {
                $items[$urlTypeMap[$url]]['issues_count']++;
            }
        }

        return $items;
    }

    /**
     * @return array<string, array<string>>
     */
    private function collectAllUrls(): array
    {
        $urlsByType = [];
        $types = SitemapIndexer::getAllPagesTypes();

        foreach ($types as $type) {
            $count = SitemapIndexer::getPagesCountByType($type);
            if ($count === 0) {
                continue;
            }

            $urlsByType[$type] = [];
            $perPage = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
            $pages = $perPage > 0 ? (int) ceil($count / $perPage) : 1;

            for ($page = 1; $page <= $pages; $page++) {
                $pageData = SitemapIndexer::getPagesByType($type, $page);
                foreach ($pageData as $item) {
                    if (!empty($item['url'])) {
                        $urlsByType[$type][] = $item['url'];
                    }
                }
            }

            $urlsByType[$type] = array_unique($urlsByType[$type]);
        }

        return $urlsByType;
    }

    /**
     * @param string $url
     * @return string|false
     */
    private function fetchUrl(string $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SeoOptimizerAudit/1.0)');

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode >= 400) {
            return false;
        }

        return $content;
    }

    /**
     * @param string $status
     */
    private function returnJson(string $status)
    {
        echo json_encode([
            'status' => $status,
            'audit' => [
                'total_urls' => $this->state['total_urls'],
                'crawled' => $this->state['crawled'] ?? 0,
                'percentage' => $this->state['total_urls'] > 0
                    ? round((($this->state['crawled'] ?? 0) / $this->state['total_urls']) * 100)
                    : 0,
                'items' => $this->state['items'],
            ],
        ]);
        exit;
    }

    /**
     * Get current state for rendering.
     *
     * @return array|null
     */
    public static function getState()
    {
        return CacheManager::get(self::CACHE_KEY);
    }
}
