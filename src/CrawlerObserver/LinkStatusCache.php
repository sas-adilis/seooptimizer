<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\CurlBatch;

/**
 * Shared cache for HTTP HEAD check results.
 * Prevents BrokenLinksObserver and RedirectedLinksObserver from
 * checking the same URLs twice.
 */
class LinkStatusCache
{
    /** @var array<string, array{http_code: int, redirect_url: string}> */
    private static $cache = [];

    /**
     * Check URLs, returning cached results + fetching uncached ones.
     *
     * @param array $urls
     * @return array<string, array{http_code: int, redirect_url: string}>
     */
    public static function check(array $urls): array
    {
        $results = [];
        $toFetch = [];

        foreach ($urls as $url) {
            if (isset(self::$cache[$url])) {
                $results[$url] = self::$cache[$url];
            } else {
                $toFetch[] = $url;
            }
        }

        if (!empty($toFetch)) {
            $fetched = CurlBatch::headCheck($toFetch);
            foreach ($fetched as $url => $info) {
                self::$cache[$url] = $info;
                $results[$url] = $info;
            }
        }

        return $results;
    }

    /**
     * Get cached result for a single URL.
     *
     * @param string $url
     * @return array|null
     */
    public static function get(string $url)
    {
        return isset(self::$cache[$url]) ? self::$cache[$url] : null;
    }

    /**
     * Reset cache (between audit runs if needed).
     */
    public static function reset(): void
    {
        self::$cache = [];
    }
}
