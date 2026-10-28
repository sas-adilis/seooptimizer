<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * An audit whose page score can be replaced by a better source when available
 * (e.g. the Lighthouse score of a page measured by PageSpeed Insights).
 */
interface ScoredAuditInterface
{
    /**
     * @param array<string, int|float> $scores URL => score computed from the crawl issues
     *
     * @return array<string, int|float> URL => score, with the better source applied where available
     */
    public function overridePageScores(array $scores): array;
}
