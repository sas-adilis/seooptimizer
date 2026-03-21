<?php

namespace Adilis\SeoOptimizer\Storage;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AuditResultStorage
{
    /**
     * Insert multiple results at once.
     *
     * @param string $auditKey
     * @param array $results Array of result rows
     * @param int|null $idShop
     */
    public static function insertBatch(string $auditKey, array $results, ?int $idShop = null)
    {
        if (empty($results)) {
            return;
        }

        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        $knownFields = ['url', 'severity', 'type', 'message', 'entity_type', 'id_entity'];

        // Try to resolve URL -> entity via seooptimizer_page (lazy, batch-scoped)
        $batchUrls = array_filter(array_column($results, 'url'));
        $urlEntityMap = self::buildUrlEntityMap($batchUrls, $idShop);

        foreach ($results as $row) {
            $extra = [];
            foreach ($row as $k => $v) {
                if (!in_array($k, $knownFields, true)) {
                    $extra[$k] = $v;
                }
            }

            $url = $row['url'] ?? '';
            $entityType = $row['entity_type'] ?? '';
            $idEntity = (int) ($row['id_entity'] ?? 0);

            // Auto-resolve from page table if not set
            if (empty($entityType) && isset($urlEntityMap[$url])) {
                $entityType = $urlEntityMap[$url]['entity_type'];
                $idEntity = (int) $urlEntityMap[$url]['id_entity'];
            }

            \Db::getInstance()->insert('seooptimizer_audit_result', [
                'audit_key' => pSQL($auditKey),
                'id_shop' => (int) $idShop,
                'entity_type' => pSQL($entityType),
                'id_entity' => (int) $idEntity,
                'url' => pSQL($url, true),
                'severity' => pSQL($row['severity'] ?? 'info'),
                'type' => pSQL($row['type'] ?? ''),
                'message' => pSQL($row['message'] ?? '', true),
                'data_json' => !empty($extra) ? pSQL(json_encode($extra), true) : null,
                'date_add' => $now,
            ]);
        }
    }

    /**
     * Delete all results for a given audit key.
     *
     * @param string $auditKey
     * @param int|null $idShop
     */
    public static function deleteByAuditKey(string $auditKey, ?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        \Db::getInstance()->delete(
            'seooptimizer_audit_result',
            'audit_key = "' . pSQL($auditKey) . '" AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Delete results for a specific URL in a given audit.
     *
     * @param string $auditKey
     * @param string $url
     * @param int|null $idShop
     */
    public static function deleteByUrl(string $auditKey, string $url, ?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        \Db::getInstance()->delete(
            'seooptimizer_audit_result',
            'audit_key = "' . pSQL($auditKey) . '"
            AND url = "' . pSQL($url, true) . '"
            AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Get all results for a given audit key.
     * Each row includes the decoded data_json merged into the row.
     *
     * @param string $auditKey
     * @param int|null $idShop
     * @return array
     */
    public static function getByAuditKey(string $auditKey, ?int $idShop = null): array
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result
            WHERE audit_key = "' . pSQL($auditKey) . '"
            AND id_shop = ' . (int) $idShop
        );

        if (!$rows) {
            return [];
        }

        return self::hydrateRows($rows);
    }

    /**
     * Count results for a given audit key, optionally filtered by severity.
     *
     * @param string $auditKey
     * @param string|null $severity
     * @param int|null $idShop
     * @return int
     */
    public static function countByAuditKey(string $auditKey, ?string $severity = null, ?int $idShop = null): int
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $where = 'audit_key = "' . pSQL($auditKey) . '" AND id_shop = ' . (int) $idShop;
        if ($severity !== null) {
            $where .= ' AND severity = "' . pSQL($severity) . '"';
        }

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result WHERE ' . $where
        );
    }

    /**
     * Get all results across all audits, grouped by URL.
     *
     * @param int|null $idShop
     * @return array<string, array>
     */
    public static function getAllGroupedByUrl(?int $idShop = null): array
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result
            WHERE id_shop = ' . (int) $idShop . '
            ORDER BY url, severity'
        );

        if (!$rows) {
            return [];
        }

        $grouped = [];
        foreach (self::hydrateRows($rows) as $row) {
            $url = $row['url'];
            if (!isset($grouped[$url])) {
                $grouped[$url] = [];
            }
            $grouped[$url][] = $row;
        }

        return $grouped;
    }

    /**
     * Get results with pagination and filtering (for HelperList).
     *
     * @param string $auditKey
     * @param int $start
     * @param int $limit
     * @param string $orderBy
     * @param string $orderWay
     * @param array $filters key => value pairs for WHERE LIKE
     * @param int|null $idShop
     * @return array
     */
    public static function getFiltered(
        string $auditKey,
        int $start = 0,
        int $limit = 50,
        string $orderBy = 'id_seooptimizer_audit_result',
        string $orderWay = 'DESC',
        array $filters = [],
        ?int $idShop = null
    ): array {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $where = 'audit_key = "' . pSQL($auditKey) . '" AND id_shop = ' . (int) $idShop;

        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (in_array($field, ['severity', 'type'], true)) {
                $where .= ' AND `' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            } elseif ($field === 'url') {
                $where .= ' AND `url` LIKE "%' . pSQL($value, true) . '%"';
            } elseif ($field === 'message') {
                $where .= ' AND `message` LIKE "%' . pSQL($value, true) . '%"';
            } else {
                // Custom fields stored in data_json
                $where .= ' AND `data_json` LIKE "%' . pSQL($value, true) . '%"';
            }
        }

        // Validate orderBy
        $allowedOrderBy = ['id_seooptimizer_audit_result', 'severity', 'url', 'type', 'message', 'date_add'];
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'id_seooptimizer_audit_result';
        }
        $orderWay = strtoupper($orderWay) === 'ASC' ? 'ASC' : 'DESC';

        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result
            WHERE ' . $where . '
            ORDER BY `' . pSQL($orderBy) . '` ' . $orderWay . '
            LIMIT ' . (int) $start . ', ' . (int) $limit
        );

        if (!$rows) {
            return [];
        }

        return self::hydrateRows($rows);
    }

    /**
     * Count filtered results (for HelperList pagination).
     *
     * @param string $auditKey
     * @param array $filters
     * @param int|null $idShop
     * @return int
     */
    public static function countFiltered(string $auditKey, array $filters = [], ?int $idShop = null): int
    {
        if ($idShop === null) {
            $idShop = (int) \Context::getContext()->shop->id;
        }

        $where = 'audit_key = "' . pSQL($auditKey) . '" AND id_shop = ' . (int) $idShop;

        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (in_array($field, ['severity', 'type'], true)) {
                $where .= ' AND `' . pSQL($field) . '` LIKE "%' . pSQL($value) . '%"';
            } elseif ($field === 'url') {
                $where .= ' AND `url` LIKE "%' . pSQL($value, true) . '%"';
            } elseif ($field === 'message') {
                $where .= ' AND `message` LIKE "%' . pSQL($value, true) . '%"';
            } else {
                // Custom fields stored in data_json
                $where .= ' AND `data_json` LIKE "%' . pSQL($value, true) . '%"';
            }
        }

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result WHERE ' . $where
        );
    }

    /**
     * Merge data_json into each row.
     *
     * @param array $rows
     * @return array
     */
    /**
     * Build URL -> entity mapping from seooptimizer_page table.
     *
     * @param int $idShop
     * @return array<string, array{entity_type: string, id_entity: int}>
     */
    /** @var array<string, array{entity_type: string, id_entity: int}> */
    private static $urlEntityMapCache = [];

    /**
     * Resolve URLs to entity types lazily — only fetches from DB for uncached URLs.
     *
     * @param array $urls
     * @param int $idShop
     * @return array<string, array{entity_type: string, id_entity: int}>
     */
    private static function buildUrlEntityMap(array $urls, int $idShop): array
    {
        $uncached = [];
        foreach ($urls as $url) {
            if (!isset(self::$urlEntityMapCache[$url])) {
                $uncached[] = $url;
            }
        }

        if (!empty($uncached)) {
            $inClause = implode(',', array_map(function ($u) {
                return '"' . pSQL($u) . '"';
            }, $uncached));

            $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT url, entity_type, id_entity FROM ' . _DB_PREFIX_ . 'seooptimizer_page
                WHERE id_shop = ' . (int) $idShop . ' AND url IN (' . $inClause . ')'
            );

            if ($rows) {
                foreach ($rows as $row) {
                    self::$urlEntityMapCache[$row['url']] = [
                        'entity_type' => $row['entity_type'],
                        'id_entity' => (int) $row['id_entity'],
                    ];
                }
            }
        }

        return self::$urlEntityMapCache;
    }

    private static function hydrateRows(array $rows): array
    {
        $hydrated = [];
        foreach ($rows as $row) {
            $extra = $row['data_json'] ? json_decode($row['data_json'], true) : [];
            if (!is_array($extra)) {
                $extra = [];
            }
            unset($row['data_json']);

            $hydrated[] = array_merge($row, $extra);
        }

        return $hydrated;
    }
}
