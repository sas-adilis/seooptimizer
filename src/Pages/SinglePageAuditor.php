<?php

namespace Adilis\SeoOptimizer\Pages;

use Adilis\SeoOptimizer\Audit\AuditInterface;
use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\CrawlerObserver\CrawlerObserverInterface;

class SinglePageAuditor
{
    /** @var PagesAggregator */
    private $aggregator;

    public function __construct()
    {
        $this->aggregator = new PagesAggregator();
    }

    /**
     * Re-audit a single URL across all audits.
     * Fetches the page once, runs all observers, updates each audit cache.
     *
     * @param string $url
     * @return array Page data after re-audit
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
        $auditStates = $this->aggregator->getAuditStates();

        // Create all observers for all audits, grouped by audit
        $auditObservers = [];
        foreach ($audits as $audit) {
            $observers = [];
            foreach ($audit->getObserverClasses() as $observerClass) {
                /** @var CrawlerObserverInterface $observer */
                $observer = new $observerClass();
                $observers[] = $observer;
            }
            $auditObservers[$audit->getKey()] = [
                'audit' => $audit,
                'observers' => $observers,
            ];
        }

        // Run observeBeforeRequest on all observers
        foreach ($auditObservers as $data) {
            foreach ($data['observers'] as $observer) {
                if (method_exists($observer, 'observeBeforeRequest')) {
                    $observer->observeBeforeRequest($url);
                }
            }
        }

        // Run observeAfterRequest on all observers (single fetch)
        foreach ($auditObservers as $data) {
            foreach ($data['observers'] as $observer) {
                if (method_exists($observer, 'observeAfterRequest')) {
                    $observer->observeAfterRequest($url, $content);
                }
            }
        }

        // Format results and update each audit's cache
        foreach ($auditObservers as $auditKey => $data) {
            /** @var AuditInterface $audit */
            $audit = $data['audit'];
            $observers = $data['observers'];

            $observerResults = [];
            foreach ($observers as $observer) {
                $observerResults[$observer->getKey()] = $observer->getResults();
            }

            $newResults = $audit->formatResults($observerResults);

            // Update the audit cache
            $cacheKey = 'audit_' . $auditKey;
            $state = $auditStates[$auditKey];

            if (!$state || !isset($state['status'])) {
                continue;
            }

            // Remove old results for this URL
            $state['results'] = array_values(array_filter(
                $state['results'] ?? [],
                function ($row) use ($url) {
                    return ($row['url'] ?? '') !== $url;
                }
            ));

            // Add new results
            $state['results'] = array_merge($state['results'], $newResults);

            // Update items issues_count by recounting from results
            if (isset($state['items']) && isset($state['urls'])) {
                // Find the type for this URL
                $urlType = null;
                foreach ($state['urls'] as $entry) {
                    $entryUrl = is_array($entry) ? ($entry['url'] ?? '') : (string) $entry;
                    if ($entryUrl === $url) {
                        $urlType = is_array($entry) ? ($entry['type'] ?? null) : null;
                        break;
                    }
                }

                if ($urlType && isset($state['items'][$urlType])) {
                    // Recount issues for this type
                    $issueCount = 0;
                    $typeUrls = [];
                    foreach ($state['urls'] as $entry) {
                        if (is_array($entry) && ($entry['type'] ?? '') === $urlType) {
                            $typeUrls[] = $entry['url'];
                        }
                    }

                    foreach ($state['results'] as $row) {
                        if (in_array($row['url'] ?? '', $typeUrls, true)) {
                            $issueCount++;
                        }
                    }

                    $state['items'][$urlType]['issues_count'] = $issueCount;
                }
            }

            CacheManager::write($cacheKey, $state);
        }

        // Re-read aggregated data for this URL
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
