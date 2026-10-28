<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\Message;
use Adilis\SeoOptimizer\Utils\RobotsTxt;

/**
 * Indexation signals of a page: HTTP redirection, canonical, robots (meta, X-Robots-Tag,
 * robots.txt), hreflang, URL and llms.txt.
 *
 * Every signal gives a check (good, info, warning or critical): the audit keeps the problems,
 * the front-office panel displays them all.
 */
class IndexationObserver extends AbstractCrawlerObserver implements CrawlerObserverInterface
{
    /** @var array<string, array{checks: array, hreflangs: array}> */
    private $results = [];

    public function getKey(): string
    {
        return 'indexation';
    }

    /**
     * @param string $url
     * @param string $content
     * @param HTMLExtractor|null $extractor
     */
    public function observeAfterRequest(string $url, string $content, ?HTMLExtractor $extractor = null)
    {
        $extractor = $extractor ?: new HTMLExtractor($content);
        $info = CurlBatch::getPageInfo($url);
        $checks = [];

        // The page itself redirects
        if (!empty($info) && $info['redirect_count'] > 0) {
            $checks[] = $this->check('page_redirected', 'warning', Message::t('Page URL redirects'), $info['effective_url']);
        }

        // Canonical
        $canonical = $extractor->extractCanonical();
        $canonicalIsSelf = true;
        if ($canonical === '') {
            $checks[] = $this->check('canonical_missing', 'warning', Message::t('Canonical URL missing'));
        } else {
            $canonicalIsSelf = $this->normalizeUrl($this->resolveUrl($canonical, $url)) === $this->normalizeUrl($url);
            $checks[] = $canonicalIsSelf
                ? $this->check('canonical_ok', 'good', Message::t('Canonical URL defined'), $canonical)
                : $this->check('canonical_other', 'warning', Message::t('Canonical URL points to another page'), $canonical);
        }

        // Robots: meta tag, X-Robots-Tag header, robots.txt
        $metaNoindex = $extractor->isNoindex();
        $headerNoindex = !empty($info['x_robots_tag'])
            && (strpos($info['x_robots_tag'], 'noindex') !== false || strpos($info['x_robots_tag'], 'none') !== false);

        if ($metaNoindex) {
            $checks[] = $this->check('noindex_meta', 'critical', Message::t('Page not indexable'), Message::t('Meta robots noindex detected.'));
        } elseif ($headerNoindex) {
            $checks[] = $this->check('noindex_header', 'critical', Message::t('Page not indexable'), Message::t('X-Robots-Tag noindex detected in the HTTP headers.'));
        } elseif ($this->isBlockedByRobotsTxt($url)) {
            $checks[] = $this->check('blocked_robots_txt', 'critical', Message::t('Blocked by robots.txt'));
        } else {
            $checks[] = $this->check('indexable', 'good', Message::t('Page indexable'), Message::t('No noindex, no robots.txt blocking.'));
        }

        // Conflicting signals: noindex with a canonical to another page
        if (($metaNoindex || $headerNoindex) && $canonical !== '' && !$canonicalIsSelf) {
            $checks[] = $this->check('noindex_canonical', 'warning', Message::t('Noindex combined with a canonical to another page'), Message::t('Search engines get conflicting signals.'));
        }

        // Hreflang
        $hreflangs = $extractor->extractHreflangs();
        if (!empty($hreflangs)) {
            $langs = implode(' ', array_map(function ($h) {
                return $h['lang'] . ' ✓';
            }, $hreflangs));
            $checks[] = $this->check('hreflang_ok', 'good', Message::t('Hreflang'), $langs);
            foreach ($this->getHreflangChecks($hreflangs, $url) as $check) {
                $checks[] = $check;
            }
        }

        // Clean URL
        $parsed = parse_url($url);
        $hasQuery = !empty($parsed['query']);
        $isLong = mb_strlen(isset($parsed['path']) ? $parsed['path'] : '') > 115;
        if (!$hasQuery && !$isLong) {
            $checks[] = $this->check('url_clean', 'good', Message::t('Clean URL'), Message::t('Length OK (%d chars).', mb_strlen($url)));
        } else {
            $detail = $hasQuery && $isLong
                ? Message::t('Dynamic parameters, URL too long.')
                : ($hasQuery ? Message::t('Dynamic parameters.') : Message::t('URL too long.'));
            $checks[] = $this->check('url_needs_improvement', 'warning', Message::t('URL needs improvement'), $detail);
        }

        // llms.txt
        $llmsPath = _PS_ROOT_DIR_ . '/llms.txt';
        if (file_exists($llmsPath)) {
            $llms = (string) file_get_contents($llmsPath);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
            // The home page path ("/") is in any link: only its full URL counts
            $inLlms = strpos($llms, $url) !== false
                || (trim($urlPath, '/') !== '' && strpos($llms, $urlPath) !== false);
            $checks[] = $inLlms
                ? $this->check('llms_included', 'good', Message::t('Page included in llms.txt'))
                : $this->check('llms_absent', 'info', Message::t('Page absent from llms.txt'));
        }

        $this->results[$url] = ['checks' => $checks, 'hreflangs' => $hreflangs];
    }

    /**
     * @return array<string, array{checks: array, hreflangs: array}>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param string $type
     * @param string $severity
     * @param string $message
     * @param string $detail
     * @return array<string, string>
     */
    private function check(string $type, string $severity, string $message, string $detail = ''): array
    {
        return ['type' => $type, 'severity' => $severity, 'message' => $message, 'detail' => $detail];
    }

    /**
     * Consistency of the hreflang annotations of the page itself (the return links
     * from the other pages are not checked).
     *
     * @param array<int, array{lang: string, href: string}> $hreflangs
     * @param string $url
     * @return array<int, array<string, string>>
     */
    private function getHreflangChecks(array $hreflangs, string $url): array
    {
        $checks = [];
        $langs = [];
        $hasSelf = false;
        $hasDefault = false;

        foreach ($hreflangs as $hreflang) {
            $lang = strtolower($hreflang['lang']);
            if ($lang === 'x-default') {
                $hasDefault = true;
            } elseif (!preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})*$/', $lang)) {
                $checks[] = $this->check('hreflang_invalid', 'warning', Message::t('Invalid hreflang code'), $hreflang['lang']);
            }
            if (isset($langs[$lang])) {
                $checks[] = $this->check('hreflang_duplicate', 'warning', Message::t('Hreflang declared several times'), $hreflang['lang']);
            }
            $langs[$lang] = true;
            if ($this->normalizeUrl($this->resolveUrl($hreflang['href'], $url)) === $this->normalizeUrl($url)) {
                $hasSelf = true;
            }
        }

        if (!$hasSelf) {
            $checks[] = $this->check('hreflang_no_self', 'warning', Message::t('Hreflang without self-reference'), Message::t('Each page must list itself among its alternate versions.'));
        }
        if (!$hasDefault) {
            $checks[] = $this->check('hreflang_no_default', 'info', Message::t('Hreflang x-default missing'), Message::t('Recommended to define the fallback version.'));
        }

        return $checks;
    }

    private function isBlockedByRobotsTxt(string $url): bool
    {
        $robotsPath = _PS_ROOT_DIR_ . '/robots.txt';
        if (!file_exists($robotsPath)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return RobotsTxt::isBlocked((string) file_get_contents($robotsPath), $path . ($query ? '?' . $query : ''));
    }

    /**
     * Absolute URL of a link found in a page (canonical and hreflang may be relative).
     *
     * @param string $href
     * @param string $pageUrl
     * @return string
     */
    private function resolveUrl(string $href, string $pageUrl): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $parsed = parse_url($pageUrl);
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        if (strpos($href, '//') === 0) {
            return $scheme . ':' . $href;
        }

        return $scheme . '://' . (isset($parsed['host']) ? $parsed['host'] : '') . '/' . ltrim($href, '/');
    }

    /**
     * Comparable form of a URL: no fragment, no trailing slash, lowercase host.
     *
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = preg_replace('/#.*$/', '', trim($url));
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return rtrim($url, '/');
        }

        return strtolower($parsed['scheme']) . '://' . strtolower($parsed['host'])
            . (isset($parsed['port']) ? ':' . $parsed['port'] : '')
            . rtrim(isset($parsed['path']) ? $parsed['path'] : '', '/')
            . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
    }
}
