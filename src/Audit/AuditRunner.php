<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Score\SeoScoreCalculator;
use Adilis\SeoOptimizer\SitemapIndexer\SitemapIndexer;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;

class AuditRunner
{
    const URLS_PER_BATCH = 10;

    /** @var AuditInterface */
    private $audit;

    /** @var array|null */
    private $state;

    /**
     * @var array<string, string>
     */
    public static $typeLabels = [
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
    public static $typeIcons = [
        'product' => 'icon-tag',
        'category' => 'icon-th-large',
        'cms' => 'icon-file-text',
        'manufacturer' => 'icon-building',
        'supplier' => 'icon-truck',
        'cms_category' => 'icon-folder',
        'meta' => 'icon-globe',
        'module' => 'icon-puzzle-piece',
    ];

    public function __construct(AuditInterface $audit)
    {
        $this->audit = $audit;
    }

    /**
     * @throws \PrestaShopException
     */
    public function process()
    {
        $isAjax = (int) \Tools::getValue('ajax');

        if ($isAjax) {
            $action = \Tools::getValue('action');

            if ($action === 'runAudit' . ucfirst($this->audit->getKey())) {
                $firstProcess = \Tools::getValue('first_process') === 'true';
                $this->ajaxProcessRun($firstProcess);
            }

            if ($action === 'exportCsvAudit' . ucfirst($this->audit->getKey())) {
                $this->ajaxProcessExportCsv();
            }

            // Skip rendering on AJAX calls (only the active audit processes above)
            return;
        }

        $content = $this->getContent();
        \Context::getContext()->smarty->assign('audit_' . $this->audit->getKey(), $content);
    }

    public function getContent(): string
    {
        $auditKey = $this->audit->getKey();
        $run = AuditRunStorage::get($auditKey);

        $totalPages = 0;
        $crawledPages = 0;
        $customKpis = [];

        if ($run) {
            $totalPages = $run['total_urls'];
            $crawledPages = $run['crawled'];
            $customKpis = $run['custom_kpis'];
        }

        $results = AuditResultStorage::getByAuditKey($auditKey);

        // Build KPI values
        $kpis = $this->computeKpis(
            $this->audit->getKpiDefinitions(),
            $results,
            $crawledPages,
            $totalPages,
            $customKpis
        );

        // Compute score
        $scoreCalculator = new SeoScoreCalculator();
        $auditScore = $scoreCalculator->computeForAudit($this->audit);

        // Generate HelperList
        $resultList = new AuditResultList($this->audit);
        $resultListHtml = $resultList->render();

        $context = \Context::getContext();
        $context->smarty->assign([
            'audit_key' => $auditKey,
            'audit_title' => $this->audit->getTitle(),
            'audit_description' => $this->audit->getDescription(),
            'audit_icon' => $this->audit->getIcon(),
            'audit_visual' => $this->audit->getVisual(),
            'audit_module_path' => __PS_BASE_URI__ . 'modules/seooptimizer/',
            'audit_total_pages' => $totalPages,
            'audit_crawled_pages' => $crawledPages,
            'audit_results_count' => count($results),
            'audit_result_list_html' => $resultListHtml,
            'audit_items' => [],
            'audit_kpis' => $kpis,
            'audit_score' => $auditScore,
            'audit_status' => $run ? $run['status'] : 'none',
            'audit_is_complete' => $run && $run['status'] === 'complete',
            'audit_is_interrupted' => $run && $run['status'] === 'running',
            'audit_percentage' => $totalPages > 0 ? round(($crawledPages / $totalPages) * 100) : 0,
            'audit_last_scan_date' => $run && $run['status'] === 'complete' && !empty($run['date_upd']) ? \Tools::displayDate($run['date_upd'], true) : '',
        ]);

        return $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/audit.tpl'
        );
    }

    private function ajaxProcessExportCsv()
    {
        $results = AuditResultStorage::getByAuditKey($this->audit->getKey());

        if (empty($results)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $columns = $this->audit->getResultColumns();
        $filename = 'audit_' . $this->audit->getKey() . '_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headerRow = ['Severity', 'URL'];
        foreach ($columns as $colLabel) {
            $headerRow[] = $colLabel;
        }
        fputcsv($output, $headerRow, ';');

        foreach ($results as $row) {
            $csvRow = [
                isset($row['severity']) ? $row['severity'] : '',
                isset($row['url']) ? $row['url'] : '',
            ];
            foreach ($columns as $colKey => $colLabel) {
                $csvRow[] = isset($row[$colKey]) ? $row[$colKey] : '';
            }
            fputcsv($output, $csvRow, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * @param bool $firstProcess
     */
    private function ajaxProcessRun(bool $firstProcess)
    {
        $auditKey = $this->audit->getKey();

        if ($firstProcess) {
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
                foreach ($typeUrls as $urlData) {
                    $urls[] = [
                        'url' => $urlData['url'],
                        'type' => $type,
                        'id_entity' => $urlData['id_entity'] ?? 0,
                    ];
                }
            }

            // Seed pages table + clear old results
            \SeoOptimizerPage::seedFromUrls($urls);
            AuditResultStorage::deleteByAuditKey($auditKey);

            $this->state = [
                'status' => 'running',
                'urls' => $urls,
                'total_urls' => count($urls),
                'crawled' => 0,
                'items' => $items,
                'custom_kpis' => [],
            ];

            AuditRunStorage::upsert($auditKey, $this->state);
            $this->returnJson('success');
        }

        $run = AuditRunStorage::get($auditKey);

        if (!$run || $run['status'] === 'complete') {
            $this->state = $run ?: ['total_urls' => 0, 'crawled' => 0, 'items' => [], 'custom_kpis' => []];
            $this->returnJson('done');
        }

        $this->state = $run;
        $offset = $this->state['crawled'];
        $batch = array_slice($this->state['urls'], $offset, self::URLS_PER_BATCH);

        if (empty($batch)) {
            $this->state['status'] = 'complete';
            foreach ($this->state['items'] as &$item) {
                $item['status'] = 'done';
                $item['percentage'] = 100;
            }
            unset($item);
            AuditRunStorage::upsert($auditKey, $this->state);
            $this->returnJson('done');
        }

        // Create observers
        $observers = [];
        foreach ($this->audit->getObserverClasses() as $observerClass) {
            $observers[] = new $observerClass();
        }

        $requiresIndexable = $this->audit->requiresIndexablePage();

        // Crawl batch
        foreach ($batch as $entry) {
            $url = $entry['url'];
            $type = $entry['type'];

            foreach ($observers as $observer) {
                if (method_exists($observer, 'observeBeforeRequest')) {
                    $observer->observeBeforeRequest($url);
                }
            }

            $content = $this->fetchUrl($url);

            if ($content !== false) {
                // Skip non-indexable pages for SEO content audits
                if ($requiresIndexable && !self::isPageIndexable($content)) {
                    // Still count as crawled but don't analyze
                    if (isset($this->state['items'][$type])) {
                        $this->state['items'][$type]['crawled']++;
                        $total = $this->state['items'][$type]['total'];
                        $crawled = $this->state['items'][$type]['crawled'];
                        $this->state['items'][$type]['percentage'] = $total > 0 ? round(($crawled / $total) * 100) : 0;
                        $this->state['items'][$type]['status'] = $crawled >= $total ? 'done' : 'processing';
                    }
                    continue;
                }

                $extractor = new HTMLExtractor($content);
                foreach ($observers as $observer) {
                    if (method_exists($observer, 'observeAfterRequest')) {
                        $observer->observeAfterRequest($url, $content, $extractor);
                    }
                }
            }

            if (isset($this->state['items'][$type])) {
                $this->state['items'][$type]['crawled']++;
                $total = $this->state['items'][$type]['total'];
                $crawled = $this->state['items'][$type]['crawled'];
                $this->state['items'][$type]['percentage'] = $total > 0 ? round(($crawled / $total) * 100) : 0;
                $this->state['items'][$type]['status'] = $crawled >= $total ? 'done' : 'processing';
            }
        }

        // Collect results and KPIs
        $observerResults = [];
        foreach ($observers as $observer) {
            $observerResults[$observer->getKey()] = $observer->getResults();
            $this->collectCustomKpis($observer);
        }

        $newResults = $this->audit->formatResults($observerResults);

        // Count issues per type (O(n) via hash map instead of O(n²))
        $urlToType = [];
        foreach ($batch as $entry) {
            $urlToType[$entry['url']] = $entry['type'];
        }
        foreach ($newResults as $row) {
            $rowUrl = isset($row['url']) ? $row['url'] : '';
            if (isset($urlToType[$rowUrl]) && isset($this->state['items'][$urlToType[$rowUrl]])) {
                $this->state['items'][$urlToType[$rowUrl]]['issues_count']++;
            }
        }

        // Store results in DB
        AuditResultStorage::insertBatch($auditKey, $newResults);

        $this->state['crawled'] += count($batch);

        if ($this->state['crawled'] >= $this->state['total_urls']) {
            $this->state['status'] = 'complete';
            foreach ($this->state['items'] as &$item) {
                $item['status'] = 'done';
                $item['percentage'] = 100;
            }
            unset($item);
        }

        AuditRunStorage::upsert($auditKey, $this->state);

        $this->returnJson($this->state['status'] === 'complete' ? 'done' : 'success');
    }

    /**
     * @param object $observer
     */
    private function collectCustomKpis($observer)
    {
        KpiMapper::collect($observer, $this->state['custom_kpis']);
    }

    /**
     * @return array<string, array<string>>
     */
    /**
     * Check if a page is indexable by search engines.
     * Detects meta robots noindex/none.
     *
     * @param string $content
     * @return bool
     */
    public static function isPageIndexable(string $content): bool
    {
        // Check <meta name="robots" content="noindex...">
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/is', $content, $m)) {
            $directives = strtolower($m[1]);
            if (strpos($directives, 'noindex') !== false || strpos($directives, 'none') !== false) {
                return false;
            }
        }
        // Reversed attribute order
        if (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']robots["\'][^>]*>/is', $content, $m)) {
            $directives = strtolower($m[1]);
            if (strpos($directives, 'noindex') !== false || strpos($directives, 'none') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Collect all URLs grouped by type.
     * Each entry has 'url' and optionally 'id_entity'.
     *
     * @return array<string, array<array{url: string, id_entity: int}>>
     */
    public static function collectAllUrls(): array
    {
        $urlsByType = [];
        $types = SitemapIndexer::getAllPagesTypes();

        foreach ($types as $type) {
            $count = SitemapIndexer::getPagesCountByType($type);
            if ($count === 0) {
                continue;
            }

            $urlsByType[$type] = [];
            $seen = [];
            $perPage = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
            $pages = $perPage > 0 ? (int) ceil($count / $perPage) : 1;

            for ($page = 1; $page <= $pages; $page++) {
                $pageData = SitemapIndexer::getPagesByType($type, $page);
                foreach ($pageData as $item) {
                    if (!empty($item['url']) && !isset($seen[$item['url']])) {
                        $seen[$item['url']] = true;
                        $urlsByType[$type][] = [
                            'url' => $item['url'],
                            'id_entity' => isset($item['id_entity']) ? (int) $item['id_entity'] : 0,
                        ];
                    }
                }
            }
        }

        return $urlsByType;
    }

    /**
     * @param string $url
     * @return string|false
     */
    private function fetchUrl(string $url)
    {
        return CurlBatch::fetchPage($url);
    }

    /**
     * @param array $kpiDefinitions
     * @param array $results
     * @param int $crawled
     * @param int $totalUrls
     * @param array $customKpis
     * @return array
     */
    public function computeKpis(array $kpiDefinitions, array $results, int $crawled, int $totalUrls, array $customKpis = []): array
    {
        $kpis = [];

        foreach ($kpiDefinitions as $def) {
            $kpi = [
                'key' => $def['key'],
                'label' => $def['label'],
                'value' => 0,
                'danger' => false,
                'warning' => false,
            ];

            switch ($def['type']) {
                case 'crawled':
                    $kpi['value'] = $crawled . ' / ' . $totalUrls;
                    break;
                case 'total_issues':
                    $kpi['value'] = count($results);
                    if (!empty($def['danger_if_positive']) && count($results) > 0) {
                        $kpi['danger'] = true;
                    }
                    break;
                case 'count_severity':
                    $count = 0;
                    $severity = $def['value'];
                    foreach ($results as $row) {
                        if (isset($row['severity']) && $row['severity'] === $severity) {
                            $count++;
                        }
                    }
                    $kpi['value'] = $count;
                    if (!empty($def['danger_if_positive']) && $count > 0) {
                        $kpi['danger'] = true;
                    }
                    if (!empty($def['warning_if_positive']) && $count > 0) {
                        $kpi['warning'] = true;
                    }
                    break;
                case 'custom':
                    $kpi['value'] = isset($customKpis[$def['key']]) ? $customKpis[$def['key']] : 0;
                    if (!empty($def['danger_if_positive']) && $kpi['value'] > 0) {
                        $kpi['danger'] = true;
                    }
                    if (!empty($def['warning_if_positive']) && $kpi['value'] > 0) {
                        $kpi['warning'] = true;
                    }
                    break;
            }

            $kpis[] = $kpi;
        }

        return $kpis;
    }

    /**
     * @param string $status
     */
    private function returnJson(string $status)
    {
        $results = AuditResultStorage::getByAuditKey($this->audit->getKey());
        $kpis = $this->computeKpis(
            $this->audit->getKpiDefinitions(),
            $results,
            $this->state['crawled'] ?? 0,
            $this->state['total_urls'] ?? 0,
            $this->state['custom_kpis'] ?? []
        );

        $auditScore = ['score' => 0, 'grade' => '-', 'grade_color' => 'gray'];
        if ($status === 'done') {
            $scoreCalculator = new SeoScoreCalculator();
            $auditScore = $scoreCalculator->computeForAudit($this->audit);
        }

        echo json_encode([
            'status' => $status,
            'audit' => [
                'key' => $this->audit->getKey(),
                'total_urls' => $this->state['total_urls'] ?? 0,
                'crawled' => $this->state['crawled'] ?? 0,
                'percentage' => ($this->state['total_urls'] ?? 0) > 0
                    ? round((($this->state['crawled'] ?? 0) / $this->state['total_urls']) * 100)
                    : 0,
                'kpis' => $kpis,
                'items' => $this->state['items'] ?? [],
                'score' => $auditScore,
            ],
        ]);
        exit;
    }
}
