<?php

namespace Adilis\SeoOptimizer\Storage;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Short-lived cache of the HTTP status of the links checked by the audits and by the
 * front-office panel, shared between requests: a link of the menu or of the footer is
 * checked once, not once per page and per opening of the panel.
 */
class LinkStatusStorage
{
    /** Lifetime of a result, in seconds */
    const TTL = 900;

    /** Results older than this are deleted, in seconds */
    const PURGE_AFTER = 86400;

    /**
     * Results still valid for these URLs.
     *
     * @param string[] $urls
     * @return array<string, array{http_code: int, redirect_url: string}>
     */
    public static function getFresh(array $urls): array
    {
        if (empty($urls)) {
            return [];
        }

        $hashes = [];
        foreach ($urls as $url) {
            $hashes[] = '"' . pSQL(md5($url)) . '"';
        }

        $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT url, http_code, redirect_url FROM ' . _DB_PREFIX_ . 'seooptimizer_link_status
            WHERE url_hash IN (' . implode(',', $hashes) . ')
            AND date_upd >= "' . pSQL(date('Y-m-d H:i:s', time() - self::TTL)) . '"'
        );

        $results = [];
        foreach ($rows ?: [] as $row) {
            $results[$row['url']] = [
                'http_code' => (int) $row['http_code'],
                'redirect_url' => (string) $row['redirect_url'],
            ];
        }

        return $results;
    }

    /**
     * @param array<string, array{http_code: int, redirect_url: string}> $results URL => result
     */
    public static function save(array $results)
    {
        if (empty($results)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $values = [];
        foreach ($results as $url => $info) {
            // Longer URLs cannot be stored: they are simply checked again next time
            if (strlen($url) > 2083 || strlen($info['redirect_url']) > 2083) {
                continue;
            }
            $values[] = '("' . pSQL(md5($url)) . '", "' . pSQL($url, true) . '", ' . (int) $info['http_code']
                . ', "' . pSQL($info['redirect_url'], true) . '", "' . pSQL($now) . '")';
        }

        if (!empty($values)) {
            \Db::getInstance()->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'seooptimizer_link_status (url_hash, url, http_code, redirect_url, date_upd)
                VALUES ' . implode(',', $values) . '
                ON DUPLICATE KEY UPDATE url = VALUES(url), http_code = VALUES(http_code),
                redirect_url = VALUES(redirect_url), date_upd = VALUES(date_upd)'
            );
        }

        \Db::getInstance()->execute(
            'DELETE FROM ' . _DB_PREFIX_ . 'seooptimizer_link_status
            WHERE date_upd < "' . pSQL(date('Y-m-d H:i:s', time() - self::PURGE_AFTER)) . '"'
        );
    }

    /**
     * Forget every result: a new audit must check the links again.
     */
    public static function purge()
    {
        \Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'seooptimizer_link_status');
    }
}
