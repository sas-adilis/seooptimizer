<?php

namespace Adilis\SeoOptimizer\Pages;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\Audit\AuditRunner;
use Adilis\SeoOptimizer\Audit\KpiMapper;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

class FullAuditRunner
{
    const CACHE_KEY = 'full_audit';
    const URLS_PER_BATCH = 5;

    /** @var AuditInterface[] */
    private $audits;

    /** @var array */
    private $state;

    public function __construct()
    {
        $this->audits = AuditRegistry::getAll();
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

        $run = AuditRunStorage::get(self::CACHE_KEY);
        if (!$run || $run['status'] === 'complete') {
            $this->state = $run ?: ['total_urls' => 0, 'crawled' => 0, 'items' => []];
            $this->returnJson('done');
        }

        $this->state = $run;
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
            AuditRunStorage::upsert(self::CACHE_KEY, $this->state);
            $this->returnJson('done');
        }

        AuditRunStorage::upsert(self::CACHE_KEY, $this->state);
        $this->returnJson('success');
    }

    private function initState()
    {
        $urlsByType = AuditRunner::collectAllUrls();

        $urls = [];
        $items = [];
        foreach ($urlsByType as $type => $typeUrls) {
            $count = count($typeUrls);
            $items[$type] = [
                'label' => isset(AuditRunner::$typeLabels[$type]) ? AuditRunner::$typeLabels[$type] : ucfirst($type),
                'icon' => isset(AuditRunner::$typeIcons[$type]) ? AuditRunner::$typeIcons[$type] : 'icon-file-text',
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

        // Seed pages table
        \SeoOptimizerPage::seedFromUrls($urls);

        // Clear all audit results and initialize runs
        foreach ($this->audits as $audit) {
            AuditResultStorage::deleteByAuditKey($audit->getKey());
            AuditRunStorage::upsert($audit->getKey(), [
                'status' => 'running',
                'urls' => $urls,
                'total_urls' => count($urls),
                'crawled' => 0,
                'items' => $items,
                'custom_kpis' => [],
            ]);
        }

        $this->state = [
            'status' => 'running',
            'urls' => $urls,
            'total_urls' => count($urls),
            'crawled' => 0,
            'items' => $items,
            'audit_custom_kpis' => [],
        ];

        foreach ($this->audits as $audit) {
            $this->state['audit_custom_kpis'][$audit->getKey()] = [];
        }

        AuditRunStorage::upsert(self::CACHE_KEY, $this->state);
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

            // observeBeforeRequest for all
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
                $isIndexable = AuditRunner::isPageIndexable($content);

                // Only run observers for audits that apply to this page
                $extractor = new HTMLExtractor($content);

                foreach ($auditObservers as $data) {
                    /** @var AuditInterface $audit */
                    $audit = $data['audit'];

                    // Skip SEO content audits on non-indexable pages
                    if ($audit->requiresIndexablePage() && !$isIndexable) {
                        continue;
                    }

                    foreach ($data['observers'] as $observer) {
                        if (method_exists($observer, 'observeAfterRequest')) {
                            $observer->observeAfterRequest($url, $content, $extractor);
                        }
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

        // Collect results per audit
        $totalIssuesByType = [];

        foreach ($auditObservers as $auditKey => $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];
            $observers = $data['observers'];

            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();
                $this->collectCustomKpis($observer, $auditKey);
            }

            $newResults = $audit->formatResults($observerResults);

            // Store in DB
            AuditResultStorage::insertBatch($auditKey, $newResults);

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

        foreach ($totalIssuesByType as $type => $count) {
            if (isset($this->state['items'][$type])) {
                $this->state['items'][$type]['issues_count'] += $count;
            }
        }
    }

    /**
     * @param object $observer
     * @param string $auditKey
     */
    private function collectCustomKpis($observer, string $auditKey)
    {
        KpiMapper::collect($observer, $this->state['audit_custom_kpis'][$auditKey]);
    }

    private function finalize()
    {
        $this->state['status'] = 'complete';
        foreach ($this->state['items'] as &$item) {
            $item['status'] = 'done';
            $item['percentage'] = 100;
        }
        unset($item);

        // Update each audit's run with final state
        foreach ($this->audits as $audit) {
            $auditKey = $audit->getKey();

            // Build per-audit items with issues_count from DB
            $auditIssueCount = AuditResultStorage::countByAuditKey($auditKey);
            $items = $this->state['items'];

            AuditRunStorage::upsert($auditKey, [
                'status' => 'complete',
                'urls' => $this->state['urls'],
                'total_urls' => $this->state['total_urls'],
                'crawled' => $this->state['crawled'] ?? $this->state['total_urls'],
                'items' => $items,
                'custom_kpis' => $this->state['audit_custom_kpis'][$auditKey] ?? [],
            ]);
        }

        // Rebuild page overview counters from results
        \SeoOptimizerPage::rebuildAllCounters();
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
     * @param string $status
     */
    private function returnJson(string $status)
    {
        echo json_encode([
            'status' => $status,
            'audit' => [
                'total_urls' => $this->state['total_urls'] ?? 0,
                'crawled' => $this->state['crawled'] ?? 0,
                'percentage' => ($this->state['total_urls'] ?? 0) > 0
                    ? round((($this->state['crawled'] ?? 0) / ($this->state['total_urls'] ?? 1)) * 100)
                    : 0,
                'items' => $this->state['items'] ?? [],
            ],
        ]);
        exit;
    }
}
