<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\CrawlerObserver\CrawlerObserverInterface;
use Adilis\SeoOptimizer\Score\SeoScoreCalculator;
use Adilis\SeoOptimizer\SitemapIndexer\SitemapIndexer;

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

    public function __construct(AuditInterface $audit)
    {
        $this->audit = $audit;
    }

    public function getCacheKey(): string
    {
        return 'audit_' . $this->audit->getKey();
    }

    /**
     * @throws \PrestaShopException
     */
    public function process()
    {
        if ((int) \Tools::getValue('ajax')) {
            $action = \Tools::getValue('action');

            if ($action === 'runAudit' . ucfirst($this->audit->getKey())) {
                $firstProcess = \Tools::getValue('first_process') === 'true';
                $this->ajaxProcessRun($firstProcess);
            }

            if ($action === 'exportCsvAudit' . ucfirst($this->audit->getKey())) {
                $this->ajaxProcessExportCsv();
            }
        }

        $content = $this->getContent();
        \Context::getContext()->smarty->assign('audit_' . $this->audit->getKey(), $content);
    }

    public function getContent(): string
    {
        $state = CacheManager::get($this->getCacheKey());

        $totalPages = 0;
        $crawledPages = 0;
        $results = [];
        $items = [];
        $customKpis = $state['custom_kpis'] ?? [];

        if ($state && isset($state['status'])) {
            $totalPages = $state['total_urls'];
            $crawledPages = $state['crawled'];
            $results = $state['results'] ?? [];
            $items = $state['items'] ?? [];
        }

        // Build KPI values
        $kpiDefinitions = $this->audit->getKpiDefinitions();
        $kpis = $this->computeKpis($kpiDefinitions, $results, $crawledPages, $totalPages, $customKpis);

        // Compute score for this audit
        $scoreCalculator = new SeoScoreCalculator();
        $auditScore = $scoreCalculator->computeForAudit($this->audit);

        // Generate HelperList for results
        $resultList = new AuditResultList($this->audit);
        $resultListHtml = $resultList->render();

        $context = \Context::getContext();
        $context->smarty->assign([
            'audit_key' => $this->audit->getKey(),
            'audit_title' => $this->audit->getTitle(),
            'audit_description' => $this->audit->getDescription(),
            'audit_icon' => $this->audit->getIcon(),
            'audit_visual' => $this->audit->getVisual(),
            'audit_module_path' => __PS_BASE_URI__ . 'modules/seooptimizer/',
            'audit_total_pages' => $totalPages,
            'audit_crawled_pages' => $crawledPages,
            'audit_results_count' => count($results),
            'audit_result_list_html' => $resultListHtml,
            'audit_items' => $items,
            'audit_kpis' => $kpis,
            'audit_score' => $auditScore,
            'audit_is_complete' => isset($state['status']) && $state['status'] === 'complete',
            'audit_percentage' => $totalPages > 0 ? round(($crawledPages / $totalPages) * 100) : 0,
        ]);

        return $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/audit.tpl'
        );
    }

    private function ajaxProcessExportCsv()
    {
        $state = CacheManager::get($this->getCacheKey());

        if (!$state || empty($state['results'])) {
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

        // Header row
        $headerRow = ['Severity', 'URL'];
        foreach ($columns as $colLabel) {
            $headerRow[] = $colLabel;
        }
        fputcsv($output, $headerRow, ';');

        // Data rows
        foreach ($state['results'] as $row) {
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
        if ($firstProcess) {
            $urlsByType = $this->collectAllUrls();

            // Build flat url list with type info
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

            $this->state = [
                'status' => 'running',
                'urls' => $urls,
                'total_urls' => count($urls),
                'crawled' => 0,
                'results' => [],
                'items' => $items,
                'custom_kpis' => [],
                'date' => date('Y-m-d H:i:s'),
            ];
            CacheManager::write($this->getCacheKey(), $this->state);
            $this->returnJson('success');
        }

        $this->state = CacheManager::get($this->getCacheKey());

        if (!$this->state || $this->state['status'] === 'complete') {
            $this->returnJson('done');
        }

        $offset = $this->state['crawled'];
        $batch = array_slice($this->state['urls'], $offset, self::URLS_PER_BATCH);

        if (empty($batch)) {
            $this->state['status'] = 'complete';
            // Mark all items as done
            foreach ($this->state['items'] as &$item) {
                $item['status'] = 'done';
                $item['percentage'] = 100;
            }
            unset($item);
            CacheManager::write($this->getCacheKey(), $this->state);
            $this->returnJson('done');
        }

        // Create observers
        $observers = [];
        foreach ($this->audit->getObserverClasses() as $observerClass) {
            /** @var CrawlerObserverInterface $observer */
            $observer = new $observerClass();
            $observers[] = $observer;
        }

        // Track which types are in this batch
        $typesInBatch = [];
        foreach ($batch as $entry) {
            $typesInBatch[$entry['type']] = true;
        }

        // Crawl batch using cURL
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
                foreach ($observers as $observer) {
                    if (method_exists($observer, 'observeAfterRequest')) {
                        $observer->observeAfterRequest($url, $content);
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

        // Collect observer results and custom KPI data
        $observerResults = [];
        foreach ($observers as $observer) {
            $observerResults[$observer->getKey()] = $observer->getResults();

            // Collect custom KPIs from observers that expose them
            if (method_exists($observer, 'getLinksChecked')) {
                if (!isset($this->state['custom_kpis']['links_checked'])) {
                    $this->state['custom_kpis']['links_checked'] = 0;
                }
                $this->state['custom_kpis']['links_checked'] += $observer->getLinksChecked();
            }

            // Page load time KPIs
            if (method_exists($observer, 'getGoodCount')) {
                if (!isset($this->state['custom_kpis']['good_count'])) {
                    $this->state['custom_kpis']['good_count'] = 0;
                }
                $this->state['custom_kpis']['good_count'] += $observer->getGoodCount();
            }
            if (method_exists($observer, 'getMediumCount')) {
                if (!isset($this->state['custom_kpis']['medium_count'])) {
                    $this->state['custom_kpis']['medium_count'] = 0;
                }
                $this->state['custom_kpis']['medium_count'] += $observer->getMediumCount();
            }
            if (method_exists($observer, 'getSlowCount')) {
                if (!isset($this->state['custom_kpis']['slow_count'])) {
                    $this->state['custom_kpis']['slow_count'] = 0;
                }
                $this->state['custom_kpis']['slow_count'] += $observer->getSlowCount();
            }

            // Page weight KPIs
            if (method_exists($observer, 'getLightCount')) {
                if (!isset($this->state['custom_kpis']['light_count'])) {
                    $this->state['custom_kpis']['light_count'] = 0;
                }
                $this->state['custom_kpis']['light_count'] += $observer->getLightCount();
            }
            if (method_exists($observer, 'getModerateCount')) {
                if (!isset($this->state['custom_kpis']['moderate_count'])) {
                    $this->state['custom_kpis']['moderate_count'] = 0;
                }
                $this->state['custom_kpis']['moderate_count'] += $observer->getModerateCount();
            }
            if (method_exists($observer, 'getHeavyCount')) {
                if (!isset($this->state['custom_kpis']['heavy_count'])) {
                    $this->state['custom_kpis']['heavy_count'] = 0;
                }
                $this->state['custom_kpis']['heavy_count'] += $observer->getHeavyCount();
            }

            // Text ratio KPIs
            if (method_exists($observer, 'getLowCount')) {
                if (!isset($this->state['custom_kpis']['low_count'])) {
                    $this->state['custom_kpis']['low_count'] = 0;
                }
                $this->state['custom_kpis']['low_count'] += $observer->getLowCount();
            }

            // Internal links KPIs
            if (method_exists($observer, 'getNoOutgoingCount')) {
                if (!isset($this->state['custom_kpis']['no_outgoing_count'])) {
                    $this->state['custom_kpis']['no_outgoing_count'] = 0;
                }
                $this->state['custom_kpis']['no_outgoing_count'] += $observer->getNoOutgoingCount();
            }
            if (method_exists($observer, 'getFewOutgoingCount')) {
                if (!isset($this->state['custom_kpis']['few_outgoing_count'])) {
                    $this->state['custom_kpis']['few_outgoing_count'] = 0;
                }
                $this->state['custom_kpis']['few_outgoing_count'] += $observer->getFewOutgoingCount();
            }

            // Meta tags KPIs
            if (method_exists($observer, 'getWarningCount')) {
                if (!isset($this->state['custom_kpis']['warning_count'])) {
                    $this->state['custom_kpis']['warning_count'] = 0;
                }
                $this->state['custom_kpis']['warning_count'] += $observer->getWarningCount();
            }
            if (method_exists($observer, 'getCriticalCount')) {
                if (!isset($this->state['custom_kpis']['critical_count'])) {
                    $this->state['custom_kpis']['critical_count'] = 0;
                }
                $this->state['custom_kpis']['critical_count'] += $observer->getCriticalCount();
            }
        }

        $newResults = $this->audit->formatResults($observerResults);

        // Count issues per type from new results
        foreach ($newResults as $row) {
            // Match URL back to type
            foreach ($batch as $entry) {
                if ($entry['url'] === $row['url'] && isset($this->state['items'][$entry['type']])) {
                    $this->state['items'][$entry['type']]['issues_count']++;
                    break;
                }
            }
        }

        $this->state['results'] = array_merge($this->state['results'], $newResults);
        $this->state['crawled'] += count($batch);

        if ($this->state['crawled'] >= $this->state['total_urls']) {
            $this->state['status'] = 'complete';
            foreach ($this->state['items'] as &$item) {
                $item['status'] = 'done';
                $item['percentage'] = 100;
            }
            unset($item);
        }

        CacheManager::write($this->getCacheKey(), $this->state);
        $this->returnJson($this->state['status'] === 'complete' ? 'done' : 'success');
    }

    /**
     * Collect all URLs grouped by type.
     *
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
     * @param array $kpiDefinitions
     * @param array $results
     * @param int $crawled
     * @param int $totalUrls
     * @param array $customKpis
     * @return array
     */
    private function computeKpis(array $kpiDefinitions, array $results, int $crawled, int $totalUrls, array $customKpis = []): array
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
        $kpiDefinitions = $this->audit->getKpiDefinitions();
        $kpis = $this->computeKpis(
            $kpiDefinitions,
            $this->state['results'],
            $this->state['crawled'],
            $this->state['total_urls'],
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
                'total_urls' => $this->state['total_urls'],
                'crawled' => $this->state['crawled'],
                'percentage' => $this->state['total_urls'] > 0
                    ? round(($this->state['crawled'] / $this->state['total_urls']) * 100)
                    : 0,
                'kpis' => $kpis,
                'items' => $this->state['items'],
                'score' => $auditScore,
            ],
        ]);
        exit;
    }
}
