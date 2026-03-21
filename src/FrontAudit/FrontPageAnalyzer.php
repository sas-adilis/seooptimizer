<?php

namespace Adilis\SeoOptimizer\FrontAudit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CrawlerObserver\BrokenLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\HeadingHierarchyObserver;
use Adilis\SeoOptimizer\CrawlerObserver\InternalLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\KeywordCheckObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MetaTagsObserver;
use Adilis\SeoOptimizer\CrawlerObserver\MissingAltAttributeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageLoadTimeObserver;
use Adilis\SeoOptimizer\CrawlerObserver\PageWeightObserver;
use Adilis\SeoOptimizer\CrawlerObserver\RedirectedLinksObserver;
use Adilis\SeoOptimizer\CrawlerObserver\StructuredDataObserver;
use Adilis\SeoOptimizer\CrawlerObserver\TextRatioObserver;
use Adilis\SeoOptimizer\CrawlerObserver\UnsecuredLinksAuditObserver;
use Adilis\SeoOptimizer\Score\ScoreGradeMapping;
use Adilis\SeoOptimizer\Utils\CurlBatch;
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

        // Build sections from observer results + direct HTML analysis
        $performance = $this->buildPerformanceSection(
            $observerResults['page_load_time'] ?? [],
            $observerResults['page_weight'] ?? []
        );
        $meta = $this->buildMetaSection($observerResults['meta_tags'] ?? []);
        $headings = $this->buildHeadingsSection($observerResults['heading_hierarchy'] ?? []);
        $content = $this->buildContentSection($observerResults['text_ratio'] ?? []);
        $keywords = $this->buildKeywordsSection($observerResults['keyword_check'] ?? []);
        $images = $this->buildImagesSection($observerResults['missing_alt'] ?? []);
        $links = $this->buildLinksSection($observerResults);
        $structuredData = $this->buildStructuredDataSection($observerResults['structured_data'] ?? []);
        $indexation = $this->buildIndexationSection();

        // Build keyword checks for scoring
        $keywordChecks = [];
        if (isset($keywords['keywords'])) {
            foreach ($keywords['keywords'] as $kw) {
                foreach ($kw['details'] as $d) {
                    $keywordChecks[] = $d;
                }
            }
        }

        // Score = weighted average of per-category scores
        $score = $this->computeScore([
            ['weight' => 5,  'checks' => $performance['checks'] ?? []],
            ['weight' => 20, 'checks' => $meta['checks'] ?? []],
            ['weight' => 10, 'checks' => $headings['checks'] ?? []],
            ['weight' => 10, 'checks' => $content['checks'] ?? []],
            ['weight' => 15, 'checks' => $keywordChecks],
            ['weight' => 8,  'checks' => $images['checks'] ?? []],
            ['weight' => 10, 'checks' => $links['checks'] ?? []],
            ['weight' => 8,  'checks' => $structuredData['checks'] ?? []],
            ['weight' => 14, 'checks' => $indexation['checks'] ?? []],
        ]);

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
        $observers[] = new PageWeightObserver();
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
                'title' => sprintf('Load time: %s', $formatted),
                'message' => '',
            ];
        }

        // Page weight check
        if ($totalKb !== null) {
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
                'title' => sprintf('Page weight: %s', $formatted),
                'message' => !empty($breakdown) ? implode(' | ', $breakdown) : '',
            ];
        }

        return [
            'load_time_ms' => $loadTimeMs,
            'load_time_severity' => $loadTimeSeverity,
            'total_kb' => $totalKb,
            'weight_severity' => $weightSeverity,
            'weight_details' => $weightDetails,
            'checks' => $checks,
        ];
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
                'title' => $issue['message'],
                'message' => '',
            ];
        }

        // If no issues, both title and description are good
        if (empty($checks)) {
            $titleLen = mb_strlen($pageData['title'] ?: '');
            $descLen = mb_strlen($pageData['description'] ?: '');
            $checks[] = ['status' => 'good', 'title' => sprintf('Meta title — %d chars', $titleLen), 'message' => ''];
            $checks[] = ['status' => 'good', 'title' => sprintf('Meta description — %d chars', $descLen), 'message' => ''];
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
                    'title' => $issue['message'],
                    'message' => '',
                ];
            }
        }

        if (empty($checks)) {
            $checks[] = ['status' => 'good', 'title' => 'Heading hierarchy OK', 'message' => ''];
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
        $message = sprintf('%d words (%s%% text)', $wordCount, $ratio);

        $checks = [];
        $checks[] = ['status' => $severity, 'title' => $message, 'message' => ''];

        return [
            'word_count' => $wordCount,
            'status' => $severity,
            'label' => $message,
            'message' => '',
            'threshold_low' => (int) \Configuration::get('SEOO_TEXT_THRESHOLD_LOW') ?: 100,
            'threshold_good' => (int) \Configuration::get('SEOO_TEXT_THRESHOLD_GOOD') ?: 300,
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

            $zoneLabels = [
                'meta_title' => ['key' => 'title', 'label' => 'Missing in title', 'severity' => 'warning'],
                'h1' => ['key' => 'h1', 'label' => 'Missing in H1', 'severity' => 'warning'],
                'url' => ['key' => 'url', 'label' => 'Missing in URL', 'severity' => 'info'],
                'meta_description' => ['key' => 'meta_description', 'label' => 'Missing in meta description', 'severity' => 'info'],
                'content_start' => ['key' => 'content', 'label' => 'Missing in content', 'severity' => 'warning'],
                'image_alt' => ['key' => 'alt_image', 'label' => 'Missing in image alt', 'severity' => 'info'],
            ];

            foreach ($zoneLabels as $observerZone => $config) {
                $found = isset($kwCheck['zones'][$observerZone]) && $kwCheck['zones'][$observerZone]['found'];
                $zones[$config['key']] = $found;

                if (!$found) {
                    $details[] = [
                        'status' => $config['severity'],
                        'title' => $config['label'],
                        'message' => 'Target keyword not found in this zone.',
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
     * @param array $observerData From MissingAltAttributeObserver
     * @return array
     */
    private function buildImagesSection(array $observerData): array
    {
        $images = $this->extractor->extractImages();
        $total = count($images);
        $checks = [];

        // MissingAltAttributeObserver: keyed by URL, only present if issues found
        if (isset($observerData[$this->url])) {
            $imgData = $observerData[$this->url];
            $missingCount = (int) $imgData['missing_alt'] + (int) $imgData['empty_alt'];
            $checks[] = ['status' => 'warning', 'title' => $missingCount . ' image(s) missing alt attribute', 'message' => ''];
        } elseif ($total > 0) {
            $checks[] = ['status' => 'good', 'title' => 'All images have alt attributes', 'message' => ''];
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
            $checks[] = ['status' => 'good', 'title' => 'Optimized formats', 'message' => 'All images use next-gen formats.'];
        } elseif ($hasOld) {
            $checks[] = ['status' => 'info', 'title' => $hasWebp ? 'Mixed formats' : 'Classic formats', 'message' => 'Some images still use JPG/PNG.'];
        }

        return ['total' => $total, 'checks' => $checks];
    }

    /**
     * @param array $allObserverResults All observer results
     * @return array
     */
    private function buildLinksSection(array $allObserverResults): array
    {
        $brokenData = $allObserverResults['broken_links'] ?? [];
        $redirectedData = $allObserverResults['redirected_links'] ?? [];
        $internalData = $allObserverResults['internal_links'] ?? [];

        // InternalLinksObserver: {outgoing: {url => {count, issues[], ...}}, incoming: {url => count}}
        $outgoing = isset($internalData['outgoing']) ? $internalData['outgoing'] : [];
        $pageInternalData = isset($outgoing[$this->url]) ? $outgoing[$this->url] : null;
        $internal = $pageInternalData ? (int) $pageInternalData['count'] : 0;
        $anchors = $this->extractor->extractAnchors();
        $total = count($anchors);
        $external = $total - $internal;

        $checks = [];

        // Internal links — interpret observer raw data (same logic as AuditInternalLinks.formatResults)
        if ($pageInternalData) {
            $uniqueCount = (int) $pageInternalData['unique_count'];
            if ($internal === 0) {
                $checks[] = ['status' => 'critical', 'title' => 'No outgoing internal links', 'message' => ''];
            } elseif ($uniqueCount < 3) {
                $checks[] = ['status' => 'warning', 'title' => 'Only ' . $uniqueCount . ' unique outgoing internal link(s)', 'message' => ''];
            } else {
                $checks[] = ['status' => 'good', 'title' => 'Internal linking', 'message' => $uniqueCount . ' unique internal links.'];
            }
            if ((int) $pageInternalData['empty_anchors'] > 0) {
                $checks[] = ['status' => 'warning', 'title' => $pageInternalData['empty_anchors'] . ' link(s) with empty anchor text', 'message' => ''];
            }
        } else {
            $checks[] = ['status' => 'good', 'title' => 'Internal linking', 'message' => ''];
        }

        // External links
        if ($external > 0) {
            $checks[] = ['status' => 'info', 'title' => 'External links', 'message' => $external . ' external links detected.'];
        }

        // Broken links from observer
        $brokenCount = count(array_filter($brokenData, function ($e) {
            return $e['page_url'] === $this->url;
        }));
        if ($brokenCount > 0) {
            $checks[] = ['status' => 'critical', 'title' => $brokenCount . ' broken link(s)', 'message' => 'HTTP 404 or unreachable.'];
        } else {
            $checks[] = ['status' => 'good', 'title' => 'No broken links', 'message' => ''];
        }

        // Redirected links from observer
        $redirectedCount = count(array_filter($redirectedData, function ($e) {
            return $e['page_url'] === $this->url;
        }));
        if ($redirectedCount > 0) {
            $checks[] = ['status' => 'warning', 'title' => $redirectedCount . ' redirected link(s)', 'message' => 'Update to final URL.'];
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
                'title' => $issue['message'],
                'message' => '',
            ];
        }

        // Add positive checks when no issues for key schemas
        $typeCounts = $pageData['type_counts'];
        $isProduct = $pageData['is_product'];

        if ($isProduct && isset($typeCounts['Product']) && isset($typeCounts['Offer'])) {
            $checks[] = ['status' => 'good', 'title' => 'Product schema complete', 'message' => 'Product + Offer present.'];
        }
        if (isset($typeCounts['AggregateRating'])) {
            $checks[] = ['status' => 'good', 'title' => 'Reviews structured', 'message' => 'AggregateRating detected.'];
        }
        if (isset($typeCounts['BreadcrumbList']) && empty($pageData['duplicates']['BreadcrumbList'])) {
            $checks[] = ['status' => 'good', 'title' => 'BreadcrumbList present', 'message' => ''];
        }

        if (empty($checks)) {
            $checks[] = ['status' => 'good', 'title' => 'Structured data OK', 'message' => $pageData['total_types'] . ' schema type(s) found.'];
        }

        return [
            'schemas' => $schemas,
            'checks' => $checks,
            'found_types' => $pageData['found_types'],
        ];
    }

    /**
     * Indexation analysis — canonical, robots, hreflang, URL, llms.txt.
     * No observer for this — direct HTML analysis.
     *
     * @return array
     */
    private function buildIndexationSection(): array
    {
        $checks = [];

        // Canonical
        $canonical = $this->extractor->extractCanonical();
        if (!empty($canonical)) {
            $checks[] = ['status' => 'good', 'title' => 'Canonical URL defined', 'message' => $canonical];
        } else {
            $checks[] = ['status' => 'warning', 'title' => 'Canonical URL missing', 'message' => ''];
        }

        // Meta robots
        if ($this->extractor->isNoindex()) {
            $checks[] = ['status' => 'critical', 'title' => 'Page not indexable', 'message' => 'Meta robots noindex detected.'];
        } elseif ($this->isBlockedByRobotsTxt()) {
            $checks[] = ['status' => 'critical', 'title' => 'Blocked by robots.txt', 'message' => ''];
        } else {
            $checks[] = ['status' => 'good', 'title' => 'Page indexable', 'message' => 'No noindex, no robots.txt blocking.'];
        }

        // Hreflang
        $hreflangs = $this->extractor->extractHreflangs();
        if (!empty($hreflangs)) {
            $langs = implode(' ', array_map(function ($h) { return $h['lang'] . ' ✓'; }, $hreflangs));
            $checks[] = ['status' => 'good', 'title' => 'Hreflang', 'message' => $langs];
        }

        // Clean URL
        $parsed = parse_url($this->url);
        $issues = [];
        if (!empty($parsed['query'])) {
            $issues[] = 'dynamic parameters';
        }
        if (mb_strlen(isset($parsed['path']) ? $parsed['path'] : '') > 115) {
            $issues[] = 'URL too long';
        }
        if (empty($issues)) {
            $checks[] = ['status' => 'good', 'title' => 'Clean URL', 'message' => 'Length OK (' . mb_strlen($this->url) . ' chars).'];
        } else {
            $checks[] = ['status' => 'warning', 'title' => 'URL needs improvement', 'message' => ucfirst(implode(', ', $issues)) . '.'];
        }

        // LLMS.txt
        $llmsPath = _PS_ROOT_DIR_ . '/llms.txt';
        if (file_exists($llmsPath)) {
            $llmsContent = file_get_contents($llmsPath);
            $urlPath = parse_url($this->url, PHP_URL_PATH) ?: '';
            if (strpos($llmsContent, $urlPath) !== false || strpos($llmsContent, $this->url) !== false) {
                $checks[] = ['status' => 'good', 'title' => 'Page included in llms.txt', 'message' => ''];
            } else {
                $checks[] = ['status' => 'info', 'title' => 'Page absent from llms.txt', 'message' => ''];
            }
        }

        return ['checks' => $checks, 'hreflangs' => $hreflangs];
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

        $urlPath = parse_url($this->url, PHP_URL_PATH) ?: '/';
        $inUserAgentAll = false;

        foreach (explode("\n", file_get_contents($robotsPath)) as $line) {
            $line = trim($line);
            if (stripos($line, 'user-agent:') === 0) {
                $inUserAgentAll = (trim(substr($line, 11)) === '*');
            } elseif ($inUserAgentAll && stripos($line, 'disallow:') === 0) {
                $rule = trim(substr($line, 9));
                if (!empty($rule) && strpos($urlPath, $rule) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compute a weighted score from per-category checks.
     *
     * Each category has a weight (0-100) and its own list of checks.
     * Within a category, the score starts at 100 and is penalised per issue,
     * but capped at 0 — so one bad category cannot overflow into others.
     * The final score is the weighted average of all categories.
     *
     * @param array $categories [['weight' => int, 'checks' => array], ...]
     * @return array
     */
    private function computeScore(array $categories): array
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($categories as $cat) {
            $weight = (int) $cat['weight'];
            $checks = $cat['checks'];

            if ($weight === 0 || empty($checks)) {
                // No checks = perfect score for this category
                $weightedSum += 100 * $weight;
                $totalWeight += $weight;
                continue;
            }

            $penalty = 0;
            $hasCritical = false;
            foreach ($checks as $check) {
                $status = isset($check['status']) ? $check['status'] : 'good';
                switch ($status) {
                    case 'critical':
                        $penalty += 30;
                        $hasCritical = true;
                        break;
                    case 'warning':
                        $penalty += 15;
                        break;
                    case 'info':
                        $penalty += 3;
                        break;
                }
            }

            // If all checks are good (no penalties), score is 100
            $catScore = max(0, 100 - $penalty);

            $weightedSum += $catScore * $weight;
            $totalWeight += $weight;
        }

        $score = $totalWeight > 0
            ? round($weightedSum / $totalWeight)
            : 100;

        $gradeData = ScoreGradeMapping::fromScore($score);

        return [
            'score' => $score,
            'grade' => $gradeData['grade'],
            'color' => $gradeData['color'],
        ];
    }
}
