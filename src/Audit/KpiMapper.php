<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Centralized KPI metric collection from observers.
 * Single source of truth for method-to-KPI mapping.
 */
class KpiMapper
{
    /** @var array<string, string> */
    private static $methodMap = [
        'getLinksChecked' => 'links_checked',
        'getGoodCount' => 'good_count',
        'getMediumCount' => 'medium_count',
        'getSlowCount' => 'slow_count',
        'getLightCount' => 'light_count',
        'getModerateCount' => 'moderate_count',
        'getHeavyCount' => 'heavy_count',
        'getWarningCount' => 'warning_count',
        'getCriticalCount' => 'critical_count',
        'getRedirectedCount' => 'redirected_count',
        'getNoOutgoingCount' => 'no_outgoing_count',
        'getFewOutgoingCount' => 'few_outgoing_count',
        'getLowCount' => 'low_count',
        'getPagesWithKeywords' => 'pages_with_keywords',
        'getPagesWithoutKeywords' => 'pages_without_keywords',
        'getTotalKeywordsChecked' => 'total_keywords_checked',
    ];

    /**
     * Collect custom KPI values from an observer into the state array.
     *
     * @param object $observer
     * @param array $customKpis Reference to the custom_kpis array in state
     */
    public static function collect($observer, array &$customKpis): void
    {
        foreach (self::$methodMap as $method => $kpiKey) {
            if (method_exists($observer, $method)) {
                if (!isset($customKpis[$kpiKey])) {
                    $customKpis[$kpiKey] = 0;
                }
                $customKpis[$kpiKey] += $observer->$method();
            }
        }
    }
}
