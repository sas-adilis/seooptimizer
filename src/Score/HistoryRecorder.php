<?php

namespace Adilis\SeoOptimizer\Score;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Storage\AuditHistoryStorage;

/**
 * Records the score snapshots of the audits in the history.
 *
 * The history is a convenience: a failure to record it never interrupts an audit.
 */
class HistoryRecorder
{
    /**
     * Snapshot of the pages and of the site after audits completed.
     *
     * @param string[]|null $auditKeys Audits that just completed, null for all of them.
     *                                 The overall score is always recorded.
     */
    public static function recordAudits(?array $auditKeys = null)
    {
        try {
            $result = (new SeoScoreCalculator())->compute();
            if (!$result['has_data']) {
                return;
            }

            $counts = self::getCounts();
            $snapshots = [];
            $siteCounts = [];

            foreach ($result['pages'] as $url => $page) {
                $total = ['critical' => 0, 'warning' => 0, 'info' => 0];
                foreach ($page['audits'] as $key => $score) {
                    $count = isset($counts[$key][$url]) ? $counts[$key][$url] : ['critical' => 0, 'warning' => 0, 'info' => 0];
                    foreach ($count as $severity => $number) {
                        $total[$severity] += $number;
                        $siteCounts[$key][$severity] = (isset($siteCounts[$key][$severity]) ? $siteCounts[$key][$severity] : 0) + $number;
                    }
                    if ($auditKeys === null || in_array($key, $auditKeys, true)) {
                        $snapshots[] = self::snapshot($key, $url, $score, $count);
                    }
                }
                $snapshots[] = self::snapshot(AuditHistoryStorage::ALL, $url, $page['score'], $total);
            }

            // The whole site: average of each audit, then global score
            $siteTotal = ['critical' => 0, 'warning' => 0, 'info' => 0];
            foreach ($result['audits'] as $key => $audit) {
                $count = isset($siteCounts[$key]) ? $siteCounts[$key] : [];
                $count += ['critical' => 0, 'warning' => 0, 'info' => 0];
                foreach ($count as $severity => $number) {
                    $siteTotal[$severity] += $number;
                }
                if ($auditKeys === null || in_array($key, $auditKeys, true)) {
                    $snapshots[] = self::snapshot($key, '', $audit['score'], $count);
                }
            }
            $snapshots[] = self::snapshot(AuditHistoryStorage::ALL, '', $result['global']['score'], $siteTotal);

            AuditHistoryStorage::save($snapshots);
        } catch (\Throwable $e) {
            self::log($e);
        }
    }

    /**
     * Snapshot of one page (re-audit of the page).
     *
     * @param string $url
     * @param array{score: float, audits: array<string, array>} $summary See SeoScoreCalculator::summarizePage()
     */
    public static function recordPage(string $url, array $summary)
    {
        try {
            $total = ['critical' => 0, 'warning' => 0, 'info' => 0];
            $snapshots = [];
            foreach ($summary['audits'] as $key => $audit) {
                $count = ['critical' => $audit['critical'], 'warning' => $audit['warning'], 'info' => $audit['info']];
                foreach ($count as $severity => $number) {
                    $total[$severity] += $number;
                }
                $snapshots[] = self::snapshot($key, $url, $audit['score'], $count);
            }
            $snapshots[] = self::snapshot(AuditHistoryStorage::ALL, $url, $summary['score'], $total);

            AuditHistoryStorage::save($snapshots);
        } catch (\Throwable $e) {
            self::log($e);
        }
    }

    /**
     * Number of issues by audit and page.
     *
     * @return array<string, array<string, array{critical: int, warning: int, info: int}>>
     */
    private static function getCounts(): array
    {
        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT audit_key, url,
                SUM(IF(severity = "critical", 1, 0)) AS cnt_critical,
                SUM(IF(severity = "warning", 1, 0)) AS cnt_warning,
                SUM(IF(severity NOT IN ("critical", "warning", "good"), 1, 0)) AS cnt_info
            FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result
            WHERE id_shop = ' . (int) \Context::getContext()->shop->id . '
            GROUP BY audit_key, url'
        );

        $counts = [];
        foreach ($rows ?: [] as $row) {
            $counts[$row['audit_key']][$row['url']] = [
                'critical' => (int) $row['cnt_critical'],
                'warning' => (int) $row['cnt_warning'],
                'info' => (int) $row['cnt_info'],
            ];
        }

        return $counts;
    }

    /**
     * @param string $auditKey
     * @param string $url
     * @param float|array $score Score, or an audit summary holding it
     * @param array{critical: int, warning: int, info: int} $count
     * @return array
     */
    private static function snapshot(string $auditKey, string $url, $score, array $count): array
    {
        return [
            'audit_key' => $auditKey,
            'url' => $url,
            'score' => (float) $score,
            'critical' => $count['critical'],
            'warning' => $count['warning'],
            'info' => $count['info'],
        ];
    }

    private static function log(\Throwable $e)
    {
        \PrestaShopLogger::addLog('SeoOptimizer: history not recorded: ' . $e->getMessage(), 2, null, 'SeoOptimizer');
    }
}
