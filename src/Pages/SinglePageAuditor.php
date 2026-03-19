<?php

namespace Adilis\SeoOptimizer\Pages;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\Audit\AuditRunner;
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

        foreach ($auditObservers as $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];

            if ($audit->requiresIndexablePage() && !$isIndexable) {
                continue;
            }

            foreach ($data['observers'] as $observer) {
                if (method_exists($observer, 'observeAfterRequest')) {
                    $observer->observeAfterRequest($url, $content);
                }
            }
        }

        // Format results and update DB for each audit
        foreach ($auditObservers as $auditKey => $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];
            $observers = $data['observers'];

            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();
            }

            $newResults = $audit->formatResults($observerResults);

            // Remove old results for this URL in this audit
            AuditResultStorage::deleteByUrl($auditKey, $url);

            // Insert new results
            AuditResultStorage::insertBatch($auditKey, $newResults);
        }

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
}
