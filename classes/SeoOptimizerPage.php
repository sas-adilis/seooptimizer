<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class SeoOptimizerPage extends ObjectModel
{
    /** @var string */
    public $entity_type;

    /** @var int */
    public $id_entity;

    /** @var int */
    public $id_lang;

    /** @var int */
    public $id_shop;

    /** @var string */
    public $url;

    /** @var string */
    public $keywords;

    /** @var int */
    public $count_critical;

    /** @var int */
    public $count_warning;

    /** @var int */
    public $count_info;

    /** @var int */
    public $count_total;

    /** @var float */
    public $score;

    /** @var string */
    public $grade;

    /** @var string */
    public $date_audit;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /**
     * @var array<string, mixed>
     */
    public static $definition = [
        'table' => 'seooptimizer_page',
        'primary' => 'id_seooptimizer_page',
        'fields' => [
            'entity_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32, 'required' => true],
            'id_entity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 2083],
            'keywords' => ['type' => self::TYPE_STRING, 'size' => 512],
            'count_critical' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'count_warning' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'count_info' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'count_total' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'score' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'grade' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 4],
            'date_audit' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    const ENTITY_TYPES = [
        'product' => 'Product',
        'category' => 'Category',
        'cms' => 'CMS',
        'manufacturer' => 'Manufacturer',
        'supplier' => 'Supplier',
        'cms_category' => 'CMSCategory',
        'meta' => 'Meta',
    ];

    /**
     * Get keywords for a specific entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     * @return string
     */
    public static function getKeywords(string $entityType, int $idEntity, ?int $idLang = null, ?int $idShop = null): string
    {
        $row = self::getByEntity($entityType, $idEntity, $idLang, $idShop);

        return $row ? (string) $row['keywords'] : '';
    }

    /**
     * Set keywords for a specific entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param string $keywords
     * @param int|null $idLang
     * @param int|null $idShop
     * @return bool
     */
    public static function setKeywords(string $entityType, int $idEntity, string $keywords, ?int $idLang = null, ?int $idShop = null): bool
    {
        return self::upsertField($entityType, $idEntity, 'keywords', $keywords, $idLang, $idShop);
    }

    /**
     * Get the full page row for an entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     * @return array|null
     */
    public static function getByEntity(string $entityType, int $idEntity, ?int $idLang = null, ?int $idShop = null)
    {
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE entity_type = "' . pSQL($entityType) . '"
            AND id_entity = ' . (int) $idEntity . '
            AND id_lang = ' . (int) $idLang . '
            AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Get a page row by URL.
     *
     * @param string $url
     * @param int|null $idShop
     * @return array|null
     */
    public static function getByUrl(string $url, ?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE url = "' . pSQL($url, true) . '"
            AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Get keywords by URL (for the KeywordCheckObserver).
     *
     * @param string $url
     * @param int|null $idShop
     * @return string
     */
    public static function getKeywordsByUrl(string $url, ?int $idShop = null): string
    {
        $row = self::getByUrl($url, $idShop);

        return $row ? (string) $row['keywords'] : '';
    }

    /**
     * Update the audit overview counters for a page.
     *
     * @param string $url
     * @param int $critical
     * @param int $warning
     * @param int $info
     * @param int|null $idShop
     */
    public static function updateAuditCounters(string $url, int $critical, int $warning, int $info, ?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'seooptimizer_page SET
            count_critical = ' . (int) $critical . ',
            count_warning = ' . (int) $warning . ',
            count_info = ' . (int) $info . ',
            count_total = ' . (int) ($critical + $warning + $info) . ',
            date_audit = "' . pSQL($now) . '",
            date_upd = "' . pSQL($now) . '"
            WHERE url = "' . pSQL($url, true) . '"
            AND id_shop = ' . (int) $idShop
        );
    }

    /**
     * Rebuild audit counters for all pages from audit_result table.
     *
     * @param int|null $idShop
     */
    public static function rebuildAllCounters(?int $idShop = null)
    {
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        // Reset all counters and scores
        Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'seooptimizer_page SET
            count_critical = 0, count_warning = 0, count_info = 0, count_total = 0,
            score = 0, grade = "",
            date_audit = "' . pSQL($now) . '", date_upd = "' . pSQL($now) . '"
            WHERE id_shop = ' . (int) $idShop
        );

        // Aggregate from audit_result
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT url,
                SUM(IF(severity = "critical", 1, 0)) as cnt_critical,
                SUM(IF(severity = "warning", 1, 0)) as cnt_warning,
                SUM(IF(severity NOT IN ("critical", "warning", "good"), 1, 0)) as cnt_info,
                COUNT(*) as cnt_total
            FROM ' . _DB_PREFIX_ . 'seooptimizer_audit_result
            WHERE id_shop = ' . (int) $idShop . '
            AND severity != "good"
            GROUP BY url'
        );

        // Build URL -> counts map
        $countsMap = [];
        if ($rows) {
            foreach ($rows as $row) {
                $countsMap[$row['url']] = $row;
            }
        }

        // Get all pages
        $pages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_seooptimizer_page, url FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE id_shop = ' . (int) $idShop
        );

        if (!$pages) {
            return;
        }

        foreach ($pages as $page) {
            $url = $page['url'];
            $critical = 0;
            $warning = 0;
            $info = 0;
            $total = 0;

            if (isset($countsMap[$url])) {
                $critical = (int) $countsMap[$url]['cnt_critical'];
                $warning = (int) $countsMap[$url]['cnt_warning'];
                $info = (int) $countsMap[$url]['cnt_info'];
                $total = (int) $countsMap[$url]['cnt_total'];
            }

            // Calculate score: start at 100, deduct per issue
            $score = 100.0;
            $score -= $critical * 10;
            $score -= $warning * 3;
            $score -= $info * 1;
            $score = max(0.0, min(100.0, $score));

            $grade = self::scoreToGrade($score);

            Db::getInstance()->execute(
                'UPDATE ' . _DB_PREFIX_ . 'seooptimizer_page SET
                count_critical = ' . $critical . ',
                count_warning = ' . $warning . ',
                count_info = ' . $info . ',
                count_total = ' . $total . ',
                score = ' . round($score, 1) . ',
                grade = "' . pSQL($grade) . '",
                date_audit = "' . pSQL($now) . '",
                date_upd = "' . pSQL($now) . '"
                WHERE id_seooptimizer_page = ' . (int) $page['id_seooptimizer_page']
            );
        }
    }

    /**
     * @param float $score
     * @return string
     */
    public static function scoreToGrade(float $score): string
    {
        if ($score >= 95) {
            return 'A+';
        }
        if ($score >= 85) {
            return 'A';
        }
        if ($score >= 70) {
            return 'B';
        }
        if ($score >= 50) {
            return 'C';
        }
        if ($score >= 30) {
            return 'D';
        }

        return 'F';
    }

    /**
     * @param string $grade
     * @return string
     */
    public static function gradeToColor(string $grade): string
    {
        $map = [
            'A+' => 'excellent',
            'A' => 'good',
            'B' => 'fair',
            'C' => 'warning',
            'D' => 'poor',
            'F' => 'critical',
        ];

        return isset($map[$grade]) ? $map[$grade] : 'gray';
    }

    /**
     * Ensure a page row exists for each URL in the sitemap.
     * Called before a full audit to seed the pages table.
     *
     * @param array $urls Array of ['url' => ..., 'type' => ...]
     * @param int|null $idLang
     * @param int|null $idShop
     */
    public static function seedFromUrls(array $urls, ?int $idLang = null, ?int $idShop = null)
    {
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($urls as $entry) {
            $url = $entry['url'];
            $type = $entry['type'] ?? '';
            $idEntity = (int) ($entry['id_entity'] ?? 0);

            // Check if already exists
            $exists = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT id_seooptimizer_page FROM ' . _DB_PREFIX_ . 'seooptimizer_page
                WHERE url = "' . pSQL($url, true) . '"
                AND id_lang = ' . (int) $idLang . '
                AND id_shop = ' . (int) $idShop
            );

            if ($exists) {
                // Update entity info if it was 0 and we now have it
                if ($idEntity > 0) {
                    Db::getInstance()->execute(
                        'UPDATE ' . _DB_PREFIX_ . 'seooptimizer_page SET
                        entity_type = "' . pSQL($type) . '",
                        id_entity = ' . (int) $idEntity . '
                        WHERE id_seooptimizer_page = ' . (int) $exists . '
                        AND id_entity = 0'
                    );
                }
                continue;
            }

            Db::getInstance()->insert('seooptimizer_page', [
                'entity_type' => pSQL($type),
                'id_entity' => (int) $idEntity,
                'id_lang' => (int) $idLang,
                'id_shop' => (int) $idShop,
                'url' => pSQL($url, true),
                'keywords' => '',
                'count_critical' => 0,
                'count_warning' => 0,
                'count_info' => 0,
                'count_total' => 0,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
        }
    }

    /**
     * Upsert a single field for an entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param string $field
     * @param mixed $value
     * @param int|null $idLang
     * @param int|null $idShop
     * @return bool
     */
    private static function upsertField(string $entityType, int $idEntity, string $field, $value, ?int $idLang = null, ?int $idShop = null): bool
    {
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $now = date('Y-m-d H:i:s');

        $existing = Db::getInstance()->getValue(
            'SELECT id_seooptimizer_page FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE entity_type = "' . pSQL($entityType) . '"
            AND id_entity = ' . (int) $idEntity . '
            AND id_lang = ' . (int) $idLang . '
            AND id_shop = ' . (int) $idShop
        );

        if ($existing) {
            return (bool) Db::getInstance()->update('seooptimizer_page', [
                $field => pSQL((string) $value),
                'date_upd' => $now,
            ], 'id_seooptimizer_page = ' . (int) $existing);
        }

        return (bool) Db::getInstance()->insert('seooptimizer_page', [
            'entity_type' => pSQL($entityType),
            'id_entity' => (int) $idEntity,
            'id_lang' => (int) $idLang,
            'id_shop' => (int) $idShop,
            $field => pSQL((string) $value),
            'date_add' => $now,
            'date_upd' => $now,
        ]);
    }

    /**
     * Delete all page data for a specific entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @return bool
     */
    public static function deleteByEntity(string $entityType, int $idEntity): bool
    {
        return (bool) Db::getInstance()->delete(
            'seooptimizer_page',
            'entity_type = "' . pSQL($entityType) . '" AND id_entity = ' . (int) $idEntity
        );
    }

    /**
     * Bulk-fetch scores for a given entity type.
     *
     * @param string $entityType
     * @param int|null $idLang
     * @param int|null $idShop
     * @return array<int, array{score: float, grade: string}>  Keyed by id_entity
     */
    public static function getScoresByEntityType(string $entityType, ?int $idLang = null, ?int $idShop = null): array
    {
        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }
        if ($idShop === null) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_entity, score, grade
            FROM ' . _DB_PREFIX_ . 'seooptimizer_page
            WHERE entity_type = "' . pSQL($entityType) . '"
            AND id_lang = ' . (int) $idLang . '
            AND id_shop = ' . (int) $idShop
        );

        $result = [];
        if ($rows) {
            foreach ($rows as $row) {
                $result[(int) $row['id_entity']] = [
                    'score' => (float) $row['score'],
                    'grade' => $row['grade'],
                ];
            }
        }

        return $result;
    }
}
