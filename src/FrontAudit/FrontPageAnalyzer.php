<?php

namespace Adilis\SeoOptimizer\FrontAudit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditKeywordCheck;
use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\Audit\ScoredAuditInterface;
use Adilis\SeoOptimizer\CrawlerObserver\BrokenLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\HeadingHierarchyObserver;
use Adilis\SeoOptimizer\CrawlerObserver\InternalLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\KeywordCheckObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MetaTagsObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageLoadTimeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageWeightObserver;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedScore;
use Adilis\SeoOptimizer\Utils\Message;
use Adilis\SeoOptimizer\CrawlerObserver\RedirectedLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\StructuredDataObserver;
use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;
use Adilis\SeoOptimizer\CrawlerObserver\UnsecuredLinksAuditObserver;
use Adilis\SeoOptimizer\Score\ScoreGradeMapping;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;
use Adilis\SeoOptimizer\Utils\RobotsTxt;

/**
 * Real-time SEO analyzer for the front-office panel.
 *
 * Delegates to the SAME observers used by BO audits to guarantee
 * identical checks, messages, and severity levels.
 */
class FrontPageAnalyzer
{
    /** @var string */
    private $url;

    /** @var string */
    private $html;

    /** @var HTMLExtractor */
    private $extractor;

    /**
     * @param string $url
     * @return array
     */
    /**
     * Analyze a page by fetching it via cURL (used by AJAX controller).
     *
     * @param string $url
     * @return array
     */
    public function analyze(string $url): array
    {
        $html = CurlBatch::fetchPage(trim($url));
        if (!$html) {
            return ['error' => 'Could not fetch page'];
        }
        return $this->analyzeFromHTML(trim($url), $html);
    }

    /**
     * Analyze from pre-rendered HTML.
     *
     * @param string $url
     * @param string $html
     * @return array
     */
    public function analyzeFromHTML(string $url, string $html): array
    {
        $this->url = $url;
        $this->html = $html;
        $this->extractor = new HTMLExtractor($this->html);

        // Run all observers on this single page
        $observerResults = $this->runObservers();

        // The audits interpret the observer results (severity, message): the panel reuses them
        $auditRows = $this->collectAuditRows($observerResults);

        // Build sections from observer results + direct HTML analysis
        $performance = $this->buildPerformanceSection(
            $observerResults['page_load_time'] ?? [],
            $observerResults['page_weight'] ?? []
        );
        $meta = $this->buildMetaSection($observerResults['meta_tags'] ?? []);
        $headings = $this->buildHeadingsSection($observerResults['heading_hierarchy'] ?? []);
        $content = $this->buildContentSection($observerResults['text_ratio'] ?? []);
        $keywords = $this->buildKeywordsSection($observerResults['keyword_check'] ?? []);
        $images = $this->buildImagesSection($auditRows['missing_alt'] ?? []);
        $links = $this->buildLinksSection($observerResults, $auditRows);
        $structuredData = $this->buildStructuredDataSection($observerResults['structured_data'] ?? []);
        $indexation = $this->buildIndexationSection();
        $social = $this->buildSocialSection();

        // Same score as the full audit
        $score = $this->computeScore($auditRows);

        return [
            'url' => $url,
            'score' => $score,
            'performance' => $performance,
            'google_preview' => $this->buildGooglePreview($meta),
            'meta' => $meta,
            'headings' => $headings,
            'content' => $content,
            'keywords' => $keywords,
            'structured_data' => $structuredData,
            'images' => $images,
            'links' => $links,
            'indexation' => $indexation,
            'social' => $social,
        ];
    }

    // ──────────────────────────────────────────────
    // Observer execution
    // ──────────────────────────────────────────────

    /**
     * Instantiate observers, feed them the page content, collect results.
     *
     * @return array<string, array>
     */
    private function runObservers(): array
    {
        // Performance observer — must be first to measure load time accurately
        $observers = [
            new PageLoadTimeObserver(),
        ];

        // Light observers (HTML parsing only, no HTTP requests)
        $observers[] = new MetaTagsObserver();
        $observers[] = new HeadingHierarchyObserver();
        $observers[] = new TextRatioObserver();
        $observers[] = new KeywordCheckObserver();
        $observers[] = new MissingAltAttributeObserver();
        $observers[] = new UnsecuredLinksAuditObserver();
        $observers[] = new InternalLinksObserver();
        $observers[] = new StructuredDataObserver();

        // Heavy observers (make HTTP HEAD requests on each link/asset found)
        if (PageSpeedScore::forUrl($this->url) === null) {
            // Lighthouse replaces the weight of the pages it analyzed
            $observers[] = new PageWeightObserver();
        }
        $observers[] = new BrokenLinksObserver();
        $observers[] = new RedirectedLinksObserver();

        foreach ($observers as $observer) {
            if (method_exists($observer, 'observeBeforeRequest')) {
                $observer->observeBeforeRequest($this->url);
            }
        }

        foreach ($observers as $observer) {
            if (method_exists($observer, 'observeAfterRequest')) {
                $observer->observeAfterRequest($this->url, $this->html, $this->extractor);
            }
        }

        $results = [];
        foreach ($observers as $observer) {
            $results[$observer->getKey()] = $observer->getResults();
        }

        return $results;
    }

    // ──────────────────────────────────────────────
    // Section builders — transform observer results into panel format
    // ──────────────────────────────────────────────

    private function buildGooglePreview(array $meta): array
    {
        $parsed = parse_url($this->url);
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '/';
        $parts = array_filter(explode('/', trim($path, '/')));

        return [
            'title' => $meta['title']['value'],
            'description' => $meta['description']['value'],
            'url' => $this->url,
            'breadcrumb' => $host . (!empty($parts) ? ' › ' . implode(' › ', $parts) : ''),
        ];
    }

    /**
     * @param array $loadTimeData From PageLoadTimeObserver
     * @param array $weightData From PageWeightObserver
     * @return array
     */
    private function buildPerformanceSection(array $loadTimeData, array $weightData): array
    {
        $checks = [];

        $loadTimeMs = null;
        $loadTimeSeverity = 'good';
        foreach ($loadTimeData as $entry) {
            if ($entry['url'] === $this->url) {
                $loadTimeMs = (int) $entry['load_time_ms'];
                $loadTimeSeverity = $entry['severity'];
                break;
            }
        }

        $totalKb = null;
        $weightSeverity = 'good';
        $weightDetails = null;
        foreach ($weightData as $entry) {
            if ($entry['url'] === $this->url) {
                $totalKb = (int) $entry['total_kb'];
                $weightSeverity = $entry['severity'];
                $weightDetails = $entry;
                break;
            }
        }

        // Load time check
        if ($loadTimeMs !== null) {
            if ($loadTimeMs < 1000) {
                $formatted = $loadTimeMs . ' ms';
            } else {
                $formatted = round($loadTimeMs / 1000, 2) . ' s';
            }

            $checks[] = [
                'status' => $loadTimeSeverity,
                'title' => Message::tr('Server response: %s', $formatted),
                'message' => '',
            ];
        }

        // Lighthouse, when the page has been analyzed, replaces the page weight check
        $lighthouse = PageSpeedScore::forUrl($this->url);
        if ($lighthouse !== null) {
            $checks[] = [
                'status' => PageSpeedScore::severity($lighthouse),
                'title' => Message::tr('Lighthouse performance: %d/100', $lighthouse),
                'message' => '',
            ];
        }

        // Page weight check
        if ($totalKb !== null && $lighthouse === null) {
            if ($totalKb < 1024) {
                $formatted = $totalKb . ' KB';
            } else {
                $formatted = round($totalKb / 1024, 1) . ' MB';
            }

            $breakdown = [];
            if ($weightDetails) {
                if ($weightDetails['html_kb'] > 0) {
                    $breakdown[] = 'HTML ' . $weightDetails['html_kb'] . ' KB';
                }
                if ($weightDetails['images_kb'] > 0) {
                    $breakdown[] = 'Images ' . $weightDetails['images_kb'] . ' KB';
                }
                if ($weightDetails['css_kb'] > 0) {
                    $breakdown[] = 'CSS ' . $weightDetails['css_kb'] . ' KB';
                }
                if ($weightDetails['js_kb'] > 0) {
                    $breakdown[] = 'JS ' . $weightDetails['js_kb'] . ' KB';
                }
            }

            $checks[] = [
                'status' => $weightSeverity,
                'title' => Message::tr('Page weight: %s', $formatted),
                'message' => !empty($breakdown) ? implode(' | ', $breakdown) : '',
            ];
        }

        return [
            'load_time_ms' => $loadTimeMs,
            'load_time_severity' => $loadTimeSeverity,
            'total_kb' => $totalKb,
            'weight_severity' => $weightSeverity,
            'weight_details' => $weightDetails,
            'lighthouse' => $lighthouse,
            'lighthouse_severity' => $lighthouse === null ? 'good' : PageSpeedScore::severity($lighthouse),
            'lighthouse_metrics' => $lighthouse === null ? [] : $this->buildLighthouseMetrics(),
            'checks' => $checks,
        ];
    }

    /**
     * @return array<int, array{label: string, display: string, severity: string}>
     */
    private function buildLighthouseMetrics(): array
    {
        $labels = [
            'lcp' => Message::tr('LCP'),
            'tbt' => Message::tr('TBT'),
            'cls' => Message::tr('CLS'),
        ];

        $metrics = [];
        foreach (PageSpeedScore::metricsForUrl($this->url) as $key => $metric) {
            $metrics[] = ['label' => $labels[$key], 'display' => $metric['display'], 'severity' => $metric['severity']];
        }

        return $metrics;
    }

    /**
     * @param array $observerData From MetaTagsObserver
     * @return array
     */
    private function buildMetaSection(array $observerData): array
    {
        $pageData = $observerData[$this->url];

        $checks = [];
        foreach ($pageData['issues'] as $issue) {
            $checks[] = [
                'status' => $issue['severity'],
                'title' => Message::translate($issue['message']),
                'message' => '',
            ];
        }

        // A field without issue is good, even when the other one has a problem
        $fieldsWithIssue = [];
        foreach ($pageData['issues'] as $issue) {
            $fieldsWithIssue[isset($issue['field']) ? $issue['field'] : ''] = true;
        }
        if (!isset($fieldsWithIssue['title'])) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Meta title — %d chars', mb_strlen($pageData['title'] ?: '')), 'message' => ''];
        }
        if (!isset($fieldsWithIssue['description'])) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Meta description — %d chars', mb_strlen($pageData['description'] ?: '')), 'message' => ''];
        }

        return [
            'title' => ['value' => $pageData['title'] ?: '', 'length' => mb_strlen($pageData['title'] ?: ''), 'status' => $pageData['page_severity'], 'message' => ''],
            'description' => ['value' => $pageData['description'] ?: '', 'length' => mb_strlen($pageData['description'] ?: ''), 'status' => $pageData['page_severity'], 'message' => ''],
            'checks' => $checks,
        ];
    }

    /**
     * @param array $observerData From HeadingHierarchyObserver
     * @return array
     */
    private function buildHeadingsSection(array $observerData): array
    {
        $headings = $this->extractor->extractHeadings();
        $tree = [];
        foreach ($headings as $h) {
            $tree[] = ['level' => $h['level'], 'text' => mb_substr($h['text'], 0, 120)];
        }

        // HeadingHierarchyObserver: keyed by URL, only present if issues or H1 found
        $checks = [];
        if (isset($observerData[$this->url])) {
            foreach ($observerData[$this->url]['issues'] as $issue) {
                $checks[] = [
                    'status' => $issue['severity'],
                    'title' => Message::translate($issue['message']),
                    'message' => '',
                ];
            }
        }

        if (empty($checks)) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Heading hierarchy OK'), 'message' => ''];
        }

        return ['tree' => $tree, 'checks' => $checks];
    }

    /**
     * @param array $observerData From TextRatioObserver
     * @return array
     */
    private function buildContentSection(array $observerData): array
    {
        // TextRatioObserver: flat array [{url, word_count, text_ratio, severity, ...}]
        $pageData = null;
        foreach ($observerData as $entry) {
            if ($entry['url'] === $this->url) {
                $pageData = $entry;
                break;
            }
        }

        $wordCount = $pageData ? (int) $pageData['word_count'] : 0;
        $severity = $pageData ? $pageData['severity'] : 'good';
        $ratio = $pageData ? $pageData['text_ratio'] : 0;
        $message = Message::tr('%d words (%s%% text)', $wordCount, $ratio);

        $thresholdLow = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_LOW') ?: 100;
        $thresholdGood = (int) \Configuration::get('SEOO_TEXT_THRESHOLD_GOOD') ?: 300;

        $checks = [];
        $checks[] = ['status' => $severity, 'title' => $message, 'message' => ''];

        return [
            'word_count' => $wordCount,
            'status' => $severity,
            'label' => $message,
            'message' => '',
            'threshold_low' => $thresholdLow,
            'threshold_good' => $thresholdGood,
            'bar_max' => max(500, $thresholdGood * 2),
            'checks' => $checks,
        ];
    }

    /**
     * @param array $observerData From KeywordCheckObserver
     * @return array
     */
    private function buildKeywordsSection(array $observerData): array
    {
        $pageData = $observerData[$this->url];

        if (!$pageData['has_keywords']) {
            return ['available' => false, 'keywords' => []];
        }

        $result = [];
        foreach ($pageData['checks'] as $kwCheck) {
            $zones = [];
            $details = [];

            // Severity comes from AuditKeywordCheck: zones without severity (URL, image alt)
            // are displayed but do not count as issues.
            $zoneLabels = [
                'meta_title' => ['key' => 'title', 'label' => Message::tr('Missing in title')],
                'h1' => ['key' => 'h1', 'label' => Message::tr('Missing in H1')],
                'url' => ['key' => 'url', 'label' => Message::tr('Missing in URL')],
                'meta_description' => ['key' => 'meta_description', 'label' => Message::tr('Missing in meta description')],
                'content_start' => ['key' => 'content', 'label' => Message::tr('Missing in first 100 words')],
                'image_alt' => ['key' => 'alt_image', 'label' => Message::tr('Missing in image alt')],
            ];

            foreach ($zoneLabels as $observerZone => $config) {
                $found = isset($kwCheck['zones'][$observerZone]) && $kwCheck['zones'][$observerZone]['found'];
                $zones[$config['key']] = $found;

                if (!$found && isset(AuditKeywordCheck::ZONE_SEVERITY[$observerZone])) {
                    $details[] = [
                        'status' => AuditKeywordCheck::ZONE_SEVERITY[$observerZone],
                        'title' => $config['label'],
                        'message' => Message::tr('Target keyword not found in this zone.'),
                    ];
                }
            }

            $result[] = [
                'keyword' => $kwCheck['keyword'],
                'zones' => $zones,
                'details' => $details,
            ];
        }

        return ['available' => true, 'keywords' => $result];
    }

    /**
     * @param array $rows Rows of AuditMissingAlt for this page
     * @return array
     */
    private function buildImagesSection(array $rows): array
    {
        $images = $this->extractor->extractImages();
        $total = count($images);
        $checks = [];

        foreach ($rows as $row) {
            if ($row['type'] === 'missing_alt' && !isset($checks['missing_alt'])) {
                $checks['missing_alt'] = ['status' => $row['severity'], 'title' => Message::tr('%d image(s) missing alt attribute', $row['missing_alt']), 'message' => ''];
            } elseif ($row['type'] === 'empty_alt' && !isset($checks['empty_alt'])) {
                $checks['empty_alt'] = ['status' => $row['severity'], 'title' => Message::tr('%d image(s) with empty alt', $row['empty_alt']), 'message' => Message::tr('Fine if the images are decorative.')];
            }
        }
        $checks = array_values($checks);

        if (empty($checks) && $total > 0) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('All images have alt attributes'), 'message' => ''];
        }

        // Dimensions and lazy loading (layout shift, loading time): informative only
        $noDimensions = 0;
        $notLazy = 0;
        foreach ($images as $position => $img) {
            if ($img['width'] === '' || $img['height'] === '') {
                ++$noDimensions;
            }
            // The first images are in the first screen: they must not be lazy-loaded
            if ($position >= 2 && $img['loading'] !== 'lazy') {
                ++$notLazy;
            }
        }
        if ($noDimensions > 0) {
            $checks[] = ['status' => 'info', 'title' => Message::tr('%d image(s) without width/height', $noDimensions), 'message' => Message::tr('Declaring the dimensions avoids layout shifts (CLS).')];
        }
        if ($notLazy > 0) {
            $checks[] = ['status' => 'info', 'title' => Message::tr('%d image(s) without lazy loading', $notLazy), 'message' => Message::tr('Add loading="lazy" to the images below the fold.')];
        }

        // Image format check (not covered by observer — FO-only bonus check)
        $formats = [];
        foreach ($images as $img) {
            $ext = strtolower(pathinfo(parse_url($img['src'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if (!empty($ext)) {
                $formats[$ext] = true;
            }
        }
        $hasWebp = isset($formats['webp']) || isset($formats['avif']);
        $hasOld = isset($formats['jpg']) || isset($formats['jpeg']) || isset($formats['png']) || isset($formats['gif']);

        if ($hasWebp && !$hasOld) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Optimized formats'), 'message' => Message::tr('All images use next-gen formats.')];
        } elseif ($hasOld) {
            $checks[] = ['status' => 'info', 'title' => $hasWebp ? Message::tr('Mixed formats') : Message::tr('Classic formats'), 'message' => Message::tr('Some images still use JPG/PNG.')];
        }

        return ['total' => $total, 'checks' => $checks];
    }

    /**
     * @param array $allObserverResults All observer results
     * @param array $auditRows Audit rows for this page, by audit key
     * @return array
     */
    private function buildLinksSection(array $allObserverResults, array $auditRows): array
    {
        $internalData = $allObserverResults['internal_links'] ?? [];

        // InternalLinksObserver: {outgoing: {url => {count, issues[], ...}}, incoming: {url => count}}
        $outgoing = isset($internalData['outgoing']) ? $internalData['outgoing'] : [];
        $pageInternalData = isset($outgoing[$this->url]) ? $outgoing[$this->url] : null;
        $internal = $pageInternalData ? (int) $pageInternalData['count'] : 0;
        $anchors = $this->extractor->extractAnchors();
        $total = count($anchors);
        $external = $total - $internal;

        $checks = [];

        // Internal links: issues found by AuditInternalLinks
        $internalRows = $auditRows['internal_links'] ?? [];
        foreach ($internalRows as $row) {
            $checks[] = ['status' => $row['severity'], 'title' => Message::translate($row['message']), 'message' => ''];
        }
        if (empty($internalRows)) {
            $checks[] = [
                'status' => 'good',
                'title' => Message::tr('Internal linking'),
                'message' => $pageInternalData ? Message::tr('%d unique internal links.', (int) $pageInternalData['unique_count']) : '',
            ];
        }

        // External links
        if ($external > 0) {
            $checks[] = ['status' => 'info', 'title' => Message::tr('External links'), 'message' => Message::tr('%d external links detected.', $external)];
        }

        // Broken links
        $broken = $auditRows['broken_links'] ?? [];
        if (!empty($broken)) {
            $checks[] = ['status' => $this->worstSeverity($broken), 'title' => Message::tr('%d broken link(s)', count($broken)), 'message' => Message::tr('HTTP 404 or unreachable.')];
        } else {
            $checks[] = ['status' => 'good', 'title' => Message::tr('No broken links'), 'message' => ''];
        }

        // Redirected links
        $redirected = $auditRows['redirected_links'] ?? [];
        if (!empty($redirected)) {
            $checks[] = ['status' => $this->worstSeverity($redirected), 'title' => Message::tr('%d redirected link(s)', count($redirected)), 'message' => Message::tr('Update to final URL.')];
        }

        // Resources and links in HTTP
        $unsecured = $auditRows['unsecured_links'] ?? [];
        if (!empty($unsecured)) {
            $checks[] = ['status' => $this->worstSeverity($unsecured), 'title' => Message::tr('%d link(s) or resource(s) in HTTP', count($unsecured)), 'message' => Message::tr('Switch to HTTPS to avoid mixed content.')];
        }

        return ['total' => $total, 'internal' => $internal, 'external' => $external, 'checks' => $checks];
    }

    /**
     * Structured data analysis — delegates to StructuredDataObserver.
     *
     * @param array $observerData From StructuredDataObserver
     * @return array
     */
    private function buildStructuredDataSection(array $observerData): array
    {
        if (!isset($observerData[$this->url])) {
            return ['schemas' => [], 'checks' => [], 'found_types' => []];
        }

        $pageData = $observerData[$this->url];
        $schemas = $pageData['schemas'];
        $checks = [];

        // Convert observer issues to panel check format
        foreach ($pageData['issues'] as $issue) {
            $checks[] = [
                'status' => $issue['severity'],
                'title' => Message::translate($issue['message']),
                'message' => '',
            ];
        }

        // Add positive checks when no issues for key schemas
        $typeCounts = $pageData['type_counts'];
        $isProduct = $pageData['is_product'];

        $productIssue = false;
        foreach ($pageData['issues'] as $issue) {
            if (in_array($issue['schema_type'], ['Product', 'Offer'], true)) {
                $productIssue = true;
            }
        }
        if ($isProduct && !$productIssue && isset($typeCounts['Product']) && isset($typeCounts['Offer'])) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Product schema complete'), 'message' => Message::tr('Product + Offer present.')];
        }
        if (isset($typeCounts['AggregateRating'])) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Reviews structured'), 'message' => Message::tr('AggregateRating detected.')];
        }
        if (isset($typeCounts['BreadcrumbList']) && empty($pageData['duplicates']['BreadcrumbList'])) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('BreadcrumbList present'), 'message' => ''];
        }

        if (empty($checks)) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Structured data OK'), 'message' => Message::tr('%d schema type(s) found.', $pageData['total_types'])];
        }

        return [
            'schemas' => $schemas,
            'checks' => $checks,
            'found_types' => $pageData['found_types'],
        ];
    }

    /**
     * Indexation analysis — HTTP status, canonical, robots (meta, header, robots.txt), hreflang, URL, llms.txt.
     * No observer for this — direct HTML and header analysis.
     *
     * @return array
     */
    private function buildIndexationSection(): array
    {
        $checks = [];
        $headers = CurlBatch::fetchHeaders($this->url);
        $pageNoindex = $this->extractor->isNoindex();

        // The page itself redirects
        if (!empty($headers) && $headers['redirect_count'] > 0) {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Page URL redirects'), 'message' => $headers['effective_url']];
        }

        // Canonical
        $canonical = $this->extractor->extractCanonical();
        $canonicalIsSelf = true;
        if (!empty($canonical)) {
            $canonicalIsSelf = $this->normalizeUrl($this->resolveUrl($canonical)) === $this->normalizeUrl($this->url);
            if ($canonicalIsSelf) {
                $checks[] = ['status' => 'good', 'title' => Message::tr('Canonical URL defined'), 'message' => $canonical];
            } else {
                $checks[] = ['status' => 'warning', 'title' => Message::tr('Canonical URL points to another page'), 'message' => $canonical];
            }
        } else {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Canonical URL missing'), 'message' => ''];
        }

        // Robots: meta tag, X-Robots-Tag header, robots.txt
        $headerNoindex = !empty($headers['x_robots_tag'])
            && (strpos($headers['x_robots_tag'], 'noindex') !== false || strpos($headers['x_robots_tag'], 'none') !== false);

        if ($pageNoindex) {
            $checks[] = ['status' => 'critical', 'title' => Message::tr('Page not indexable'), 'message' => Message::tr('Meta robots noindex detected.')];
        } elseif ($headerNoindex) {
            $checks[] = ['status' => 'critical', 'title' => Message::tr('Page not indexable'), 'message' => Message::tr('X-Robots-Tag noindex detected in the HTTP headers.')];
        } elseif ($this->isBlockedByRobotsTxt()) {
            $checks[] = ['status' => 'critical', 'title' => Message::tr('Blocked by robots.txt'), 'message' => ''];
        } else {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Page indexable'), 'message' => Message::tr('No noindex, no robots.txt blocking.')];
        }

        // Conflicting signals: noindex with a canonical to another page
        if (($pageNoindex || $headerNoindex) && !empty($canonical) && !$canonicalIsSelf) {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Noindex combined with a canonical to another page'), 'message' => Message::tr('Search engines get conflicting signals.')];
        }

        // Hreflang
        $hreflangs = $this->extractor->extractHreflangs();
        if (!empty($hreflangs)) {
            $langs = implode(' ', array_map(function ($h) { return $h['lang'] . ' ✓'; }, $hreflangs));
            $checks[] = ['status' => 'good', 'title' => Message::tr('Hreflang'), 'message' => $langs];
            foreach ($this->getHreflangIssues($hreflangs) as $issue) {
                $checks[] = $issue;
            }
        }

        // Clean URL
        $parsed = parse_url($this->url);
        $issues = [];
        if (!empty($parsed['query'])) {
            $issues[] = Message::tr('dynamic parameters');
        }
        if (mb_strlen(isset($parsed['path']) ? $parsed['path'] : '') > 115) {
            $issues[] = Message::tr('URL too long');
        }
        if (empty($issues)) {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Clean URL'), 'message' => Message::tr('Length OK (%d chars).', mb_strlen($this->url))];
        } else {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('URL needs improvement'), 'message' => ucfirst(implode(', ', $issues)) . '.'];
        }

        // LLMS.txt
        $llmsPath = _PS_ROOT_DIR_ . '/llms.txt';
        if (file_exists($llmsPath)) {
            $llmsContent = file_get_contents($llmsPath);
            $urlPath = parse_url($this->url, PHP_URL_PATH) ?: '';
            // The home page path ("/") is in any link: only its full URL counts
            $inLlms = strpos($llmsContent, $this->url) !== false
                || (trim($urlPath, '/') !== '' && strpos($llmsContent, $urlPath) !== false);
            if ($inLlms) {
                $checks[] = ['status' => 'good', 'title' => Message::tr('Page included in llms.txt'), 'message' => ''];
            } else {
                $checks[] = ['status' => 'info', 'title' => Message::tr('Page absent from llms.txt'), 'message' => ''];
            }
        }

        return ['checks' => $checks, 'hreflangs' => $hreflangs];
    }

    /**
     * Consistency of the hreflang annotations of the page itself (the return links
     * from the other pages are not checked).
     *
     * @param array<int, array{lang: string, href: string}> $hreflangs
     * @return array<int, array<string, string>>
     */
    private function getHreflangIssues(array $hreflangs): array
    {
        $issues = [];
        $langs = [];
        $hasSelf = false;
        $hasDefault = false;

        foreach ($hreflangs as $hreflang) {
            $lang = strtolower($hreflang['lang']);
            if ($lang === 'x-default') {
                $hasDefault = true;
            } elseif (!preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})*$/', $lang)) {
                $issues[] = ['status' => 'warning', 'title' => Message::tr('Invalid hreflang code'), 'message' => $hreflang['lang']];
            }
            if (isset($langs[$lang])) {
                $issues[] = ['status' => 'warning', 'title' => Message::tr('Hreflang declared several times'), 'message' => $hreflang['lang']];
            }
            $langs[$lang] = true;
            if ($this->normalizeUrl($this->resolveUrl($hreflang['href'])) === $this->normalizeUrl($this->url)) {
                $hasSelf = true;
            }
        }

        if (!$hasSelf) {
            $issues[] = ['status' => 'warning', 'title' => Message::tr('Hreflang without self-reference'), 'message' => Message::tr('Each page must list itself among its alternate versions.')];
        }
        if (!$hasDefault) {
            $issues[] = ['status' => 'info', 'title' => Message::tr('Hreflang x-default missing'), 'message' => Message::tr('Recommended to define the fallback version.')];
        }

        return $issues;
    }

    /**
     * Social and technical tags: Open Graph, Twitter card, language, viewport.
     *
     * @return array
     */
    private function buildSocialSection(): array
    {
        $checks = [];

        $og = $this->extractor->extractPrefixedMetas('property', 'og:');
        if (empty($og)) {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Open Graph tags missing'), 'message' => Message::tr('The page is shared without title or image preview.')];
        } else {
            if (!isset($og['og:image'])) {
                $checks[] = ['status' => 'warning', 'title' => Message::tr('og:image missing'), 'message' => Message::tr('The shared link has no image preview.')];
            }
            foreach (['og:title', 'og:description'] as $tag) {
                if (!isset($og[$tag])) {
                    $checks[] = ['status' => 'info', 'title' => Message::tr('%s missing', $tag), 'message' => ''];
                }
            }
            if (isset($og['og:image'], $og['og:title'], $og['og:description'])) {
                $checks[] = ['status' => 'good', 'title' => Message::tr('Open Graph tags complete'), 'message' => ''];
            }
        }

        if (empty($this->extractor->extractPrefixedMetas('name', 'twitter:'))) {
            $checks[] = ['status' => 'info', 'title' => Message::tr('Twitter card missing'), 'message' => Message::tr('X falls back on Open Graph tags when they exist.')];
        } else {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Twitter card defined'), 'message' => ''];
        }

        $lang = $this->extractor->extractHtmlLang();
        if ($lang === '') {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Language of the page missing'), 'message' => Message::tr('Add a lang attribute to the html tag.')];
        } else {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Language of the page defined'), 'message' => $lang];
        }

        if ($this->extractor->extractViewport() === '') {
            $checks[] = ['status' => 'warning', 'title' => Message::tr('Viewport tag missing'), 'message' => Message::tr('Required for mobile-first indexing.')];
        } else {
            $checks[] = ['status' => 'good', 'title' => Message::tr('Viewport tag defined'), 'message' => ''];
        }

        return ['checks' => $checks];
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function isBlockedByRobotsTxt(): bool
    {
        $robotsPath = _PS_ROOT_DIR_ . '/robots.txt';
        if (!file_exists($robotsPath)) {
            return false;
        }

        $path = parse_url($this->url, PHP_URL_PATH) ?: '/';
        $query = parse_url($this->url, PHP_URL_QUERY);

        return RobotsTxt::isBlocked((string) file_get_contents($robotsPath), $path . ($query ? '?' . $query : ''));
    }

    /**
     * Absolute URL of a link found in the page (canonical may be relative).
     *
     * @param string $href
     * @return string
     */
    private function resolveUrl(string $href): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $parsed = parse_url($this->url);
        $base = (isset($parsed['scheme']) ? $parsed['scheme'] : 'https') . '://' . (isset($parsed['host']) ? $parsed['host'] : '');
        if (strpos($href, '//') === 0) {
            return (isset($parsed['scheme']) ? $parsed['scheme'] : 'https') . ':' . $href;
        }

        return $base . '/' . ltrim($href, '/');
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

    /**
     * Rows of every audit for this page, by audit key. Audits that only apply to
     * indexable pages are left out of a noindex page, as in the full audit.
     *
     * @param array<string, array> $observerResults
     * @return array<string, array>
     */
    private function collectAuditRows(array $observerResults): array
    {
        $indexable = !$this->extractor->isNoindex();
        $rows = [];

        foreach (AuditRegistry::getAll() as $audit) {
            if ($audit->requiresIndexablePage() && !$indexable) {
                continue;
            }

            $rows[$audit->getKey()] = array_values(array_filter(
                $audit->formatResults($observerResults),
                function ($row) {
                    // The orphan pages need the whole site: not computable on a single page
                    return ($row['url'] ?? '') === $this->url && ($row['type'] ?? '') !== 'orphan_page';
                }
            ));
        }

        return $rows;
    }

    /**
     * @param array $rows
     * @return string
     */
    private function worstSeverity(array $rows): string
    {
        $order = ['info' => 1, 'warning' => 2, 'critical' => 3];
        $worst = 'info';
        foreach ($rows as $row) {
            if (isset($order[$row['severity']]) && $order[$row['severity']] > $order[$worst]) {
                $worst = $row['severity'];
            }
        }

        return $worst;
    }

    /**
     * Same calculation as SeoScoreCalculator: per audit, 100 minus the penalty of each
     * issue of the page, then the average weighted by the audit weights.
     *
     * @param array<string, array> $auditRows
     * @return array
     */
    private function computeScore(array $auditRows): array
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach (AuditRegistry::getAll() as $audit) {
            $key = $audit->getKey();
            if (!isset($auditRows[$key])) {
                continue;
            }

            $impact = $audit->getScoreImpact();
            $penalty = 0;
            foreach ($auditRows[$key] as $row) {
                $penalty += isset($impact[$row['severity']]) ? $impact[$row['severity']] : 0;
            }

            $auditScore = [$this->url => max(0, 100 - $penalty)];
            if ($audit instanceof ScoredAuditInterface) {
                $auditScore = $audit->overridePageScores($auditScore);
            }

            $weightedSum += $auditScore[$this->url] * $audit->getScoreWeight();
            $totalWeight += $audit->getScoreWeight();
        }

        $score = $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 100;
        $gradeData = ScoreGradeMapping::fromScore($score);

        return [
            'score' => $score,
            'grade' => $gradeData['grade'],
            'color' => $gradeData['color'],
        ];
    }
}
