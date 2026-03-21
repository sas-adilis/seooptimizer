<?php

namespace Adilis\SeoOptimizer\Utils;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CurlBatch
{
    /**
     * Perform HEAD requests on multiple URLs in parallel (no redirect following).
     * Returns HTTP code and redirect URL (if any) per URL.
     *
     * @param array $urls
     * @param int $timeout
     * @return array<string, array{http_code: int, redirect_url: string}>
     */
    public static function headCheck(array $urls, int $timeout = 5): array
    {
        if (empty($urls)) {
            return [];
        }

        $results = [];
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($urls as $i => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SeoOptimizerAudit/1.0)');
            curl_multi_add_handle($multiHandle, $ch);
            $handles[$i] = $ch;
        }

        $running = 0;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($handles as $i => $ch) {
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $redirectUrl = '';

            if (in_array($httpCode, [301, 302, 303, 307, 308], true)) {
                $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                if (empty($redirectUrl)) {
                    $headers = curl_multi_getcontent($ch);
                    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
                        $redirectUrl = trim($m[1]);
                    }
                }
            }

            $results[$urls[$i]] = [
                'http_code' => $httpCode,
                'redirect_url' => $redirectUrl,
            ];

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }

    /**
     * Fetch a single page content via cURL.
     *
     * @param string $url
     * @param int $timeout
     * @return string|false
     */
    public static function fetchPage(string $url, int $timeout = 15)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SeoOptimizerAudit/1.0)');

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode >= 400) {
            return false;
        }

        return $content;
    }

    /**
     * Get file sizes for multiple URLs in parallel via HEAD requests.
     *
     * @param array $urls
     * @param int $timeout
     * @return array<string, int> URL => size in bytes
     */
    public static function getContentLengths(array $urls, int $timeout = 5): array
    {
        if (empty($urls)) {
            return [];
        }

        $results = [];
        $multiHandle = curl_multi_init();
        $handles = [];

        foreach ($urls as $i => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SeoOptimizerAudit/1.0)');
            curl_multi_add_handle($multiHandle, $ch);
            $handles[$i] = $ch;
        }

        $running = 0;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        foreach ($handles as $i => $ch) {
            $size = (int) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            if ($size <= 0) {
                $size = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            }
            $results[$urls[$i]] = $size;
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }
}
