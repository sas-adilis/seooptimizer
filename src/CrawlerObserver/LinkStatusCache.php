<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Storage\LinkStatusStorage;
use Adilis\SeoOptimizer\Utils\CurlBatch;

/**
 * Shared cache for HTTP HEAD check results.
 * Prevents BrokenLinksObserver and RedirectedLinksObserver from
 * checking the same URLs twice: in memory during a request, then in the database
 * (LinkStatusStorage) for a few minutes, between requests.
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
            $stored = self::fromStorage($toFetch);
            foreach ($stored as $url => $info) {
                self::$cache[$url] = $info;
                $results[$url] = $info;
            }

            $toFetch = array_values(array_diff($toFetch, array_keys($stored)));
        }

        if (!empty($toFetch)) {
            $fetched = CurlBatch::headCheck($toFetch);
            foreach ($fetched as $url => $info) {
                self::$cache[$url] = $info;
                $results[$url] = $info;
            }
            self::toStorage($fetched);
        }

        return $results;
    }

    /**
     * The persistent cache is an optimization: when it fails (table missing before the
     * upgrade, database error), the links are simply checked again.
     *
     * @param string[] $urls
     * @return array<string, array{http_code: int, redirect_url: string}>
     */
    private static function fromStorage(array $urls): array
    {
        try {
            return LinkStatusStorage::getFresh($urls);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string, array{http_code: int, redirect_url: string}> $results
     */
    private static function toStorage(array $results)
    {
        try {
            LinkStatusStorage::save($results);
        } catch (\Throwable $e) {
            // Not cached
        }
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
     * Reset the cache, in memory and in the database (start of an audit).
     */
    public static function reset(): void
    {
        self::$cache = [];
        try {
            LinkStatusStorage::purge();
        } catch (\Throwable $e) {
            // Nothing to purge
        }
    }
}
