<?php

namespace Adilis\SeoOptimizer\Storage;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * History of the scores: one snapshot per audit, per page and per day.
 *
 * The audit key "*" is the overall score of a page (all the audits), and an empty URL is
 * the whole site. A new audit the same day replaces the snapshot of that day.
 */
class AuditHistoryStorage
{
    /** Audit key of the overall score */
    const ALL = '*';

    /** Snapshots older than this are deleted, in days */
    const RETENTION_DAYS = 400;

    /** Snapshots per INSERT */
    const CHUNK = 400;

    /**
     * @param array<int, array{audit_key: string, url: string, score: float, critical: int, warning: int, info: int}> $snapshots
     * @param int|null $idShop
     */
    public static function save(array $snapshots, ?int $idShop = null)
    {
        $idShop = self::shopId($idShop);
        $today = date('Y-m-d');

        foreach (array_chunk($snapshots, self::CHUNK) as $chunk) {
            $values = [];
            foreach ($chunk as $snapshot) {
                // A URL too long for the column is not tracked
                if (strlen($snapshot['url']) > 2083) {
                    continue;
                }
                $values[] = '(' . $idShop . ', "' . pSQL($snapshot['audit_key']) . '", "' . pSQL(md5($snapshot['url']))
                    . '", "' . pSQL($snapshot['url'], true) . '", ' . round((float) $snapshot['score'], 1)
                    . ', ' . (int) $snapshot['critical'] . ', ' . (int) $snapshot['warning'] . ', ' . (int) $snapshot['info']
                    . ', "' . pSQL($today) . '")';
            }

            if (!empty($values)) {
                \Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . 'seooptimizer_history
                    (id_shop, audit_key, url_hash, url, score, count_critical, count_warning, count_info, date_audit)
                    VALUES ' . implode(',', $values) . '
                    ON DUPLICATE KEY UPDATE url = VALUES(url), score = VALUES(score), count_critical = VALUES(count_critical),
                    count_warning = VALUES(count_warning), count_info = VALUES(count_info)'
                );
            }
        }

        \Db::getInstance()->execute(
            'DELETE FROM ' . _DB_PREFIX_ . 'seooptimizer_history
            WHERE date_audit < "' . pSQL(date('Y-m-d', strtotime('-' . self::RETENTION_DAYS . ' days'))) . '"'
        );
    }

    /**
     * Scores of an audit for a page (or the whole site with an empty URL), oldest first.
     *
     * @param string $auditKey
     * @param string $url
     * @param int $days
     * @param int|null $idShop
     * @return array<int, array{date: string, score: float}>
     */
    public static function getSeries(string $auditKey, string $url = '', int $days = 180, ?int $idShop = null): array
    {
        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT date_audit, score FROM ' . _DB_PREFIX_ . 'seooptimizer_history
            WHERE id_shop = ' . self::shopId($idShop) . '
            AND audit_key = "' . pSQL($auditKey) . '"
            AND url_hash = "' . pSQL(md5($url)) . '"
            AND date_audit >= "' . pSQL(date('Y-m-d', strtotime('-' . (int) $days . ' days'))) . '"
            ORDER BY date_audit ASC'
        );

        $series = [];
        foreach ($rows ?: [] as $row) {
            $series[] = ['date' => $row['date_audit'], 'score' => (float) $row['score']];
        }

        return $series;
    }

    /**
     * @param int|null $idShop
     * @return int
     */
    private static function shopId(?int $idShop): int
    {
        return $idShop === null ? (int) \Context::getContext()->shop->id : $idShop;
    }
}
