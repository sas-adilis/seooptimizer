<?php

namespace Adilis\SeoOptimizer\Storage;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AuditRunStorage
{
    /**
     * Get run data for a given audit key.
     *
     * @param string $auditKey
     * @param int|null $idShop
     * @return array|null
     */
    public static function get(string $auditKey, ?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $row = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_run
            WHERE audit_key = "' . pSQL($auditKey) . '"
            AND id_shop = ' . (int) $idShop
        );

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id_seooptimizer_audit_run'],
            'audit_key' => $row['audit_key'],
            'status' => $row['status'],
            'total_urls' => (int) $row['total_urls'],
            'crawled' => (int) $row['crawled'],
            'items' => $row['items_json'] ? json_decode($row['items_json'], true) : [],
            'custom_kpis' => $row['custom_kpis_json'] ? json_decode($row['custom_kpis_json'], true) : [],
            'urls' => $row['urls_json'] ? json_decode($row['urls_json'], true) : [],
            'date' => $row['date_add'],
        ];
    }

    /**
     * Create or replace a run for an audit key.
     *
     * @param string $auditKey
     * @param array $data
     * @param int|null $idShop
     * @return int The run ID
     */
    public static function upsert(string $auditKey, array $data, ?int $idShop = null): int
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        $row = [
            'status' => $data['status'] ?? 'running',
            'total_urls' => (int) ($data['total_urls'] ?? 0),
            'crawled' => (int) ($data['crawled'] ?? 0),
            'items_json' => isset($data['items']) ? json_encode($data['items']) : null,
            'custom_kpis_json' => isset($data['custom_kpis']) ? json_encode($data['custom_kpis']) : null,
            'urls_json' => isset($data['urls']) ? json_encode($data['urls']) : null,
            'date_upd' => $now,
        ];

        $existing = \Db::getInstance()->getValue(
            'SELECT id_seooptimizer_audit_run FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_run
            WHERE audit_key = "' . pSQL($auditKey) . '"
            AND id_shop = ' . (int) $idShop
        );

        if ($existing) {
            \Db::getInstance()->update('seooptimizer_audit_run', $row, 'id_seooptimizer_audit_run = ' . (int) $existing);
            return (int) $existing;
        }

        $row['audit_key'] = pSQL($auditKey);
        $row['id_shop'] = (int) $idShop;
        $row['date_add'] = $now;
        \Db::getInstance()->insert('seooptimizer_audit_run', $row);

        return (int) \Db::getInstance()->Insert_ID();
    }

    /**
     * Check if a completed run exists for an audit key.
     *
     * @param string $auditKey
     * @param int|null $idShop
     * @return bool
     */
    public static function isComplete(string $auditKey, ?int $idShop = null): bool
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $status = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT status FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_run
            WHERE audit_key = "' . pSQL($auditKey) . '"
            AND id_shop = ' . (int) $idShop
        );

        return $status === 'complete';
    }

    /**
     * Check if any audit run is complete.
     *
     * @param int|null $idShop
     * @return bool
     */
    public static function hasAnyComplete(?int $idShop = null): bool
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        return (bool) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT 1 FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_run
            WHERE status = "complete" AND id_shop = ' . (int) $idShop
        );
    }
}
