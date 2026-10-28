<?php

namespace Adilis\SeoOptimizer\FrontAudit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditKeywordCheck;
use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\CrawlerObserver\BrokenLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\HeadingHierarchyObserver;
use Adilis\SeoOptimizer\CrawlerObserver\IndexationObserver;
use Adilis\SeoOptimizer\CrawlerObserver\InternalLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\KeywordCheckObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MetaTagsObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageLoadTimeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageWeightObserver;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedScore;
use Adilis\SeoOptimizer\Utils\Message;
use Adilis\SeoOptimizer\CrawlerObserver\RedirectedLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\SocialTagsObserver;
use Adilis\SeoOptimizer\CrawlerObserver\StructuredDataObserver;
use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;
use Adilis\SeoOptimizer\CrawlerObserver\UnsecuredLinksAuditObserver;
use Adilis\SeoOptimizer\Score\ScoreGradeMapping;
use Adilis\SeoOptimizer\Score\SeoScoreCalculator;
use Adilis\SeoOptimizer\Storage\AuditHistoryStorage;
use Adilis\SeoOptimizer\Utils\CurlBatch;
use Adilis\SeoOptimizer\Utils\HistoryChart;
use Adilis\SeoOptimizer\Utils\HTMLExtractor;

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

    /** @var array Score summary of the page (overall and per audit), see SeoScoreCalculator::summarizePage() */
    private $summary = [];

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
        $indexation = $this->buildChecksSection($observerResults['indexation'] ?? []);
        $indexation['hreflangs'] = isset($observerResults['indexation'][$url]['hreflangs']) ? $observerResults['indexation'][$url]['hreflangs'] : [];
        $social = $this->buildChecksSection($observerResults['social_tags'] ?? []);

        // Same score as the full audit
        $score = $this->computeScore($auditRows);

        return [
            'url' => $url,
            'score' => $score,
            'summary' => $this->summary,
            'history' => $this->buildHistory(),
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
        $observers[] = new IndexationObserver();
        $observers[] = new SocialTagsObserver();

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
     * Evolution of the overall score of the page (chart and change since the previous audit).
     * Without history (table missing, no audit yet) the section is not displayed.
     *
     * @return array{chart: string, delta: string}
     */
    private function buildHistory(): array
    {
        try {
            $series = AuditHistoryStorage::getSeries(AuditHistoryStorage::ALL, $this->url);
        } catch (\Throwable $e) {
            return ['chart' => '', 'delta' => ''];
        }

        $delta = HistoryChart::delta($series);

        return [
            'chart' => HistoryChart::render($series),
            'delta' => $delta === null ? '' : Message::tr('%s since %s', $delta['label'], $delta['since']),
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
     * Section made of the checks of an observer (indexation, social tags): the problems are
     * the ones of the audit, the checks that pass are displayed too.
     *
     * @param array $observerData Results of the observer, by URL
     * @return array
     */
    private function buildChecksSection(array $observerData): array
    {
        $checks = [];
        if (isset($observerData[$this->url])) {
            foreach ($observerData[$this->url]['checks'] as $check) {
                $checks[] = [
                    'status' => $check['severity'],
                    'title' => Message::translate($check['message']),
                    'message' => Message::translate($check['detail']),
                ];
            }
        }

        return ['checks' => $checks];
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

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
     * Same calculation as SeoScoreCalculator (see summarizePage()).
     *
     * @param array<string, array> $auditRows
     * @return array
     */
    private function computeScore(array $auditRows): array
    {
        $this->summary = (new SeoScoreCalculator())->summarizePage($this->url, $auditRows);
        $gradeData = ScoreGradeMapping::fromScore($this->summary['score']);

        return [
            'score' => $this->summary['score'],
            'grade' => $gradeData['grade'],
            'color' => $gradeData['color'],
        ];
    }
}
