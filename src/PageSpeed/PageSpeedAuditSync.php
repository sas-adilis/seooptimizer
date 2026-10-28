<?php

namespace Adilis\SeoOptimizer\PageSpeed;

use Adilis\SeoOptimizer\Audit\AuditPageWeight;
use Adilis\SeoOptimizer\Storage\AuditResultStorage;
use Adilis\SeoOptimizer\Storage\AuditRunStorage;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Applies the latest Lighthouse scores to the results of the audits they replace
 * (without crawling the shop again), then rebuilds the pages scores.
 */
class PageSpeedAuditSync
{
    /** Columns of a stored result that are not part of the result itself */
    const TECHNICAL_FIELDS = ['id_seooptimizer_audit_result', 'audit_key', 'id_shop', 'date_add'];

    public static function sync()
    {
        PageSpeedScore::reset();

        $audit = new AuditPageWeight();
        $key = $audit->getKey();
        if (!AuditRunStorage::isComplete($key)) {
            return;
        }

        $rows = [];
        foreach (AuditResultStorage::getByAuditKey($key) as $row) {
            $rows[] = $audit->resolveStatus(array_diff_key($row, array_flip(self::TECHNICAL_FIELDS)));
        }

        AuditResultStorage::deleteByAuditKey($key);
        AuditResultStorage::insertBatch($key, $rows);

        \SeoOptimizerPage::rebuildAllCounters();
    }
}
