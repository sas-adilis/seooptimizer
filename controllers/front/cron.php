<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\Audit\AuditRunner;
use Adilis\SeoOptimizer\Audit\KpiMapper;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Pages\FullAuditRunner;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

class SeoOptimizerCronModuleFrontController extends ModuleFrontController
{
    /** @var int Max execution time in seconds (leave margin for PHP timeout) */
    const MAX_EXECUTION_TIME = 120;

    /** @var int URLs per batch */
    const URLS_PER_BATCH = 10;

    /**
     * @var array<string, AuditInterface>
     */
    private static $availableAudits = [];

    public function initContent()
    {
        // Security: verify token
        if (Tools::getValue('token') !== $this->module->secure_key) {
            $this->returnJson('error', 'Invalid token');
        }

        self::$availableAudits = $this->getAvailableAudits();

        $auditParam = Tools::getValue('audit', 'all');

        if ($auditParam === 'all') {
            $this->runFullAudit();
        } elseif ($auditParam === 'list') {
            $this->listAudits();
        } elseif (isset(self::$availableAudits[$auditParam])) {
            $this->runSingleAudit(self::$availableAudits[$auditParam]);
        } else {
            $this->returnJson('error', 'Unknown audit: ' . $auditParam . '. Use ?audit=list to see available audits.');
        }
    }

    /**
     * @return array<string, AuditInterface>
     */
    private function getAvailableAudits(): array
    {
        $map = [];
        foreach (AuditRegistry::getAll() as $audit) {
            $map[$audit->getKey()] = $audit;
        }

        return $map;
    }

    private function listAudits()
    {
        $list = [];
        foreach (self::$availableAudits as $key => $audit) {
            $run = AuditRunStorage::get($key);
            $list[] = [
                'key' => $key,
                'title' => $audit->getTitle(),
                'status' => $run ? $run['status'] : 'never',
                'crawled' => $run ? $run['crawled'] : 0,
                'total' => $run ? $run['total_urls'] : 0,
            ];
        }

        $fullRun = AuditRunStorage::get(FullAuditRunner::CACHE_KEY);
        $list[] = [
            'key' => 'all',
            'title' => 'Full audit (all audits)',
            'status' => $fullRun ? $fullRun['status'] : 'never',
            'crawled' => $fullRun ? $fullRun['crawled'] : 0,
            'total' => $fullRun ? $fullRun['total_urls'] : 0,
        ];

        $this->returnJson('success', 'Available audits', $list);
    }

    /**
     * @param AuditInterface $audit
     */
    private function runSingleAudit(AuditInterface $audit)
    {
        $auditKey = $audit->getKey();
        $startTime = time();
        $resume = (bool) Tools::getValue('resume', false);

        $run = AuditRunStorage::get($auditKey);

        // If no run or completed (and not resuming), start fresh
        if (!$run || ($run['status'] === 'complete' && !$resume)) {
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

            SeoOptimizerPage::seedFromUrls($urls);
            AuditResultStorage::deleteByAuditKey($auditKey);

            $state = [
                'status' => 'running',
                'urls' => $urls,
                'total_urls' => count($urls),
                'crawled' => 0,
                'items' => $items,
                'custom_kpis' => [],
            ];

            AuditRunStorage::upsert($auditKey, $state);
        } else {
            $state = $run;
        }

        if ($state['status'] === 'complete') {
            $this->returnJson('done', 'Audit already complete', [
                'crawled' => $state['crawled'],
                'total' => $state['total_urls'],
            ]);
        }

        // Process batches within time limit
        $processed = 0;
        while ((time() - $startTime) < self::MAX_EXECUTION_TIME) {
            $offset = $state['crawled'];
            $batch = array_slice($state['urls'], $offset, self::URLS_PER_BATCH);

            if (empty($batch)) {
                break;
            }

            $observers = [];
            foreach ($audit->getObserverClasses() as $observerClass) {
                $observers[] = new $observerClass();
            }

            $requiresIndexable = $audit->requiresIndexablePage();

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
                    if ($requiresIndexable && !AuditRunner::isPageIndexable($content)) {
                        if (isset($state['items'][$type])) {
                            $state['items'][$type]['crawled']++;
                            $total = $state['items'][$type]['total'];
                            $crawled = $state['items'][$type]['crawled'];
                            $state['items'][$type]['percentage'] = $total > 0 ? round(($crawled / $total) * 100) : 0;
                            $state['items'][$type]['status'] = $crawled >= $total ? 'done' : 'processing';
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

                if (isset($state['items'][$type])) {
                    $state['items'][$type]['crawled']++;
                    $total = $state['items'][$type]['total'];
                    $crawled = $state['items'][$type]['crawled'];
                    $state['items'][$type]['percentage'] = $total > 0 ? round(($crawled / $total) * 100) : 0;
                    $state['items'][$type]['status'] = $crawled >= $total ? 'done' : 'processing';
                }
            }

            // Collect and store results
            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();
                $this->collectCustomKpis($observer, $state);
            }

            $newResults = $audit->formatResults($observerResults);
            AuditResultStorage::insertBatch($auditKey, $newResults);

            $state['crawled'] += count($batch);
            $processed += count($batch);

            AuditRunStorage::upsert($auditKey, $state);
        }

        // Check completion
        if ($state['crawled'] >= $state['total_urls']) {
            $state['status'] = 'complete';
            foreach ($state['items'] as &$item) {
                $item['status'] = 'done';
                $item['percentage'] = 100;
            }
            unset($item);
            AuditRunStorage::upsert($auditKey, $state);
        }

        $this->returnJson(
            $state['status'] === 'complete' ? 'done' : 'partial',
            $state['status'] === 'complete'
                ? 'Audit complete'
                : 'Processed ' . $processed . ' URLs (total: ' . $state['crawled'] . '/' . $state['total_urls'] . '). Run cron again to continue.',
            [
                'audit' => $auditKey,
                'crawled' => $state['crawled'],
                'total' => $state['total_urls'],
                'status' => $state['status'],
            ]
        );
    }

    private function runFullAudit()
    {
        $startTime = time();
        $fullRunner = new FullAuditRunner();

        $run = AuditRunStorage::get(FullAuditRunner::CACHE_KEY);
        $resume = (bool) Tools::getValue('resume', false);

        if (!$run || ($run['status'] === 'complete' && !$resume)) {
            // Initialize via FullAuditRunner's logic (captured via output buffer since it exits)
            ob_start();
            $fullRunner->run(true);
            ob_end_clean();

            $run = AuditRunStorage::get(FullAuditRunner::CACHE_KEY);
        }

        if (!$run || $run['status'] === 'complete') {
            $this->returnJson('done', 'Full audit already complete', [
                'crawled' => $run ? $run['crawled'] : 0,
                'total' => $run ? $run['total_urls'] : 0,
            ]);
        }

        // Process batches within time limit
        $processed = 0;
        while ((time() - $startTime) < self::MAX_EXECUTION_TIME) {
            ob_start();
            $fullRunner->run(false);
            $output = ob_get_clean();

            $result = json_decode($output, true);
            if (!$result) {
                break;
            }

            $processed += self::URLS_PER_BATCH;

            if ($result['status'] === 'done') {
                $this->returnJson('done', 'Full audit complete', [
                    'crawled' => $result['audit']['crawled'] ?? 0,
                    'total' => $result['audit']['total_urls'] ?? 0,
                ]);
            }
        }

        $run = AuditRunStorage::get(FullAuditRunner::CACHE_KEY);

        $this->returnJson(
            'partial',
            'Processed URLs (total: ' . ($run ? $run['crawled'] : 0) . '/' . ($run ? $run['total_urls'] : 0) . '). Run cron again to continue.',
            [
                'crawled' => $run ? $run['crawled'] : 0,
                'total' => $run ? $run['total_urls'] : 0,
                'status' => $run ? $run['status'] : 'unknown',
            ]
        );
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
     * @param object $observer
     * @param array $state
     */
    private function collectCustomKpis($observer, array &$state)
    {
        KpiMapper::collect($observer, $state['custom_kpis']);
    }

    /**
     * @param string $status
     * @param string $message
     * @param array $data
     */
    private function returnJson(string $status, string $message = '', array $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ]);
        exit;
    }
}
