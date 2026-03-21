<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

class URLResolver
{
    /**
     * Resolve a potentially relative URL to an absolute URL.
     *
     * @param string $href
     * @param string $baseUrl
     * @return string|null
     */
    public static function resolve(string $href, string $baseUrl)
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        if (strpos($href, '//') === 0) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $href;
        }

        $parsed = parse_url($baseUrl);
        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        $base = $parsed['scheme'] . '://' . $parsed['host'];

        if (strpos($href, '/') === 0) {
            return $base . $href;
        }

        $dir = isset($parsed['path']) ? rtrim(dirname($parsed['path']), '/') : '';
        return $base . $dir . '/' . $href;
    }

    /**
     * @return string
     */
    public static function getShopDomain(): string
    {
        $shopUrl = \Context::getContext()->shop->getBaseURL();
        return parse_url($shopUrl, PHP_URL_HOST) ?: '';
    }

    /**
     * @param string $href
     * @return bool
     */
    public static function isSkippable(string $href): bool
    {
        return empty($href)
            || strpos($href, '#') === 0
            || strpos($href, 'javascript:') === 0
            || strpos($href, 'mailto:') === 0
            || strpos($href, 'tel:') === 0
            || strpos($href, 'data:') === 0;
    }

    /**
     * @param string $href
     * @param string $shopDomain
     * @return bool
     */
    public static function isInternal(string $href, string $shopDomain): bool
    {
        $linkDomain = parse_url($href, PHP_URL_HOST);
        return !$linkDomain || $linkDomain === $shopDomain;
    }
}
