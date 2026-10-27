<?php

namespace Adilis\SeoOptimizer\Storage;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * PageSpeed Insights results. Only the latest analysis of a shop is kept:
 * starting a new one replaces the previous rows with pending ones.
 */
class PageSpeedStorage
{
    const STATUS_PENDING = 'pending';
    const STATUS_DONE = 'done';
    const STATUS_ERROR = 'error';

    /**
     * Replace the previous analysis by one pending row per page.
     *
     * @param array<int, array{url: string, type: string}> $sample
     * @param string $strategy
     * @param int|null $idShop
     */
    public static function reset(array $sample, string $strategy, ?int $idShop = null)
    {
        $idShop = self::shopId($idShop);
        $db = \Db::getInstance();
        $now = date('Y-m-d H:i:s');

        $db->delete('seooptimizer_pagespeed', 'id_shop = ' . $idShop);

        foreach ($sample as $page) {
            $db->insert('seooptimizer_pagespeed', [
                'id_shop' => $idShop,
                'url' => pSQL($page['url']),
                'page_type' => pSQL($page['type']),
                'strategy' => pSQL($strategy),
                'status' => self::STATUS_PENDING,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
        }
    }

    /**
     * @param int|null $idShop
     *
     * @return array<string, mixed>|null
     */
    public static function getNextPending(?int $idShop = null)
    {
        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'seooptimizer_pagespeed`
            WHERE id_shop = ' . self::shopId($idShop) . '
            AND status = "' . self::STATUS_PENDING . '"
            ORDER BY id_seooptimizer_pagespeed ASC'
        );

        return $row ?: null;
    }

    /**
     * @param int $id
     * @param array<string, mixed> $result Normalized PageSpeedClient result
     */
    public static function saveResult(int $id, array $result)
    {
        \Db::getInstance()->update('seooptimizer_pagespeed', [
            'status' => self::STATUS_DONE,
            'score_performance' => self::score($result, 'performance'),
            'score_accessibility' => self::score($result, 'accessibility'),
            'score_best_practices' => self::score($result, 'best-practices'),
            'score_seo' => self::score($result, 'seo'),
            'metrics_json' => pSQL(json_encode($result['metrics']), true),
            'audits_json' => pSQL(json_encode($result['audits']), true),
            'field_json' => $result['field'] === null ? null : pSQL(json_encode($result['field']), true),
            'error' => null,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_seooptimizer_pagespeed = ' . (int) $id, 1, true);
    }

    /**
     * @param int $id
     * @param string $error
     */
    public static function saveError(int $id, string $error)
    {
        \Db::getInstance()->update('seooptimizer_pagespeed', [
            'status' => self::STATUS_ERROR,
            'error' => pSQL($error),
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_seooptimizer_pagespeed = ' . (int) $id);
    }

    /**
     * @param int|null $idShop
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(?int $idShop = null): array
    {
        $rows = \Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'seooptimizer_pagespeed`
            WHERE id_shop = ' . self::shopId($idShop) . '
            ORDER BY id_seooptimizer_pagespeed ASC'
        );

        return $rows ?: [];
    }

    /**
     * @param int|null $idShop
     *
     * @return array{total: int, processed: int}
     */
    public static function getProgress(?int $idShop = null): array
    {
        $row = \Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total, SUM(status != "' . self::STATUS_PENDING . '") AS processed
            FROM `' . _DB_PREFIX_ . 'seooptimizer_pagespeed`
            WHERE id_shop = ' . self::shopId($idShop)
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'processed' => (int) ($row['processed'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param string $category
     *
     * @return int|null
     */
    private static function score(array $result, string $category)
    {
        return isset($result['scores'][$category]) ? (int) $result['scores'][$category] : null;
    }

    private static function shopId(?int $idShop): int
    {
        return $idShop === null ? (int) \Context::getContext()->shop->id : $idShop;
    }
}
