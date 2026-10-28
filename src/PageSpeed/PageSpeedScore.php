<?php

namespace Adilis\SeoOptimizer\PageSpeed;

use Adilis\SeoOptimizer\Storage\PageSpeedStorage;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Lighthouse performance score of the pages analyzed by PageSpeed Insights.
 * Only a sample of the shop is analyzed: other pages have no score and keep the crawl-based one.
 */
class PageSpeedScore
{
    /** Lighthouse thresholds: >= 90 good, >= 50 needs improvement, below poor */
    const GOOD_FROM = 90;
    const AVERAGE_FROM = 50;

    /** @var array<int, array<string, int>> */
    private static $cache = [];

    /**
     * @param string $url
     * @param int|null $idShop
     *
     * @return int|null Score (0-100), null when the page has not been analyzed
     */
    public static function forUrl(string $url, ?int $idShop = null)
    {
        $scores = self::load($idShop === null ? (int) \Context::getContext()->shop->id : $idShop);

        return isset($scores[$url]) ? $scores[$url] : null;
    }

    /**
     * @param int|null $idShop
     *
     * @return array<string, int> URL => score
     */
    public static function getAll(?int $idShop = null): array
    {
        return self::load($idShop === null ? (int) \Context::getContext()->shop->id : $idShop);
    }

    public static function severity(int $score): string
    {
        if ($score >= self::GOOD_FROM) {
            return 'good';
        }

        return $score >= self::AVERAGE_FROM ? 'warning' : 'critical';
    }

    public static function reset()
    {
        self::$cache = [];
    }

    /**
     * @param int $idShop
     *
     * @return array<string, int>
     */
    private static function load(int $idShop): array
    {
        if (!isset(self::$cache[$idShop])) {
            self::$cache[$idShop] = [];
            foreach (PageSpeedStorage::getAll($idShop) as $row) {
                if ($row['status'] === PageSpeedStorage::STATUS_DONE && $row['score_performance'] !== null) {
                    self::$cache[$idShop][$row['url']] = (int) $row['score_performance'];
                }
            }
        }

        return self::$cache[$idShop];
    }
}
