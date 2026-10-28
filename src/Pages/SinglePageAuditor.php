<?php

namespace Adilis\SeoOptimizer\Pages;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditRunner;
use Adilis\SeoOptimizer\Score\HistoryRecorder;
use Adilis\SeoOptimizer\Score\SeoScoreCalculator;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;

class SinglePageAuditor
{
    /** @var PagesAggregator */
    private $aggregator;

    public function __construct()
    {
        $this->aggregator = new PagesAggregator();
    }

    /**
     * @param string $url
     * @return array
     */
    public function auditUrl(string $url): array
    {
        $content = $this->fetchUrl($url);

        if ($content === false) {
            return [
                'status' => 'error',
                'message' => 'Unable to fetch URL: ' . $url,
            ];
        }

        $audits = $this->aggregator->getAudits();

        // Create all observers for all audits
        $auditObservers = [];
        foreach ($audits as $audit) {
            $observers = [];
            foreach ($audit->getObserverClasses() as $observerClass) {
                $observers[] = new $observerClass();
            }
            $auditObservers[$audit->getKey()] = [
                'audit' => $audit,
                'observers' => $observers,
            ];
        }

        // Run observers
        foreach ($auditObservers as $data) {
            foreach ($data['observers'] as $observer) {
                if (method_exists($observer, 'observeBeforeRequest')) {
                    $observer->observeBeforeRequest($url);
                }
            }
        }

        $isIndexable = AuditRunner::isPageIndexable($content);
        $extractor = new HTMLExtractor($content);

        foreach ($auditObservers as $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];

            if ($audit->requiresIndexablePage() && !$isIndexable) {
                continue;
            }

            foreach ($data['observers'] as $observer) {
                if (method_exists($observer, 'observeAfterRequest')) {
                    $observer->observeAfterRequest($url, $content, $extractor);
                }
            }
        }

        // Format results and update DB for each audit
        $rowsByAudit = [];
        foreach ($auditObservers as $auditKey => $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];
            $observers = $data['observers'];

            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();
            }

            $newResults = $audit->formatResults($observerResults);

            // The audits that skipped the page (not indexable) have no score for it
            if (!$audit->requiresIndexablePage() || $isIndexable) {
                $rowsByAudit[$auditKey] = array_values(array_filter($newResults, function ($row) use ($url) {
                    return ($row['url'] ?? '') === $url;
                }));
            }

            // Remove old results for this URL in this audit
            AuditResultStorage::deleteByUrl($auditKey, $url);

            // Insert new results
            AuditResultStorage::insertBatch($auditKey, $newResults);
        }

        // The page counters, score and grade are read from the pages table
        \SeoOptimizerPage::rebuildCountersForUrl($url);
        HistoryRecorder::recordPage($url, (new SeoScoreCalculator())->summarizePage($url, $rowsByAudit));

        // Re-read aggregated data
        $freshAggregator = new PagesAggregator();
        $pageData = $freshAggregator->getPageData($url);

        return [
            'status' => 'success',
            'page' => $pageData,
        ];
    }

    /**
     * @param string $url
     * @return string|false
     */
    private function fetchUrl(string $url)
    {
        return CurlBatch::fetchPage($url);
    }
}
