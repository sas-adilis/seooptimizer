<?php

namespace Adilis\SeoOptimizer\PageSpeed;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Client of the Google PageSpeed Insights API v5 (runs Lighthouse on a URL).
 */
class PageSpeedClient
{
    const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /** Lighthouse category => API category parameter */
    const CATEGORIES = [
        'performance' => 'PERFORMANCE',
        'accessibility' => 'ACCESSIBILITY',
        'best-practices' => 'BEST_PRACTICES',
        'seo' => 'SEO',
    ];

    /** Lighthouse audit id => metric key */
    const METRICS = [
        'first-contentful-paint' => 'fcp',
        'largest-contentful-paint' => 'lcp',
        'total-blocking-time' => 'tbt',
        'cumulative-layout-shift' => 'cls',
        'speed-index' => 'si',
        'server-response-time' => 'ttfb',
        'total-byte-weight' => 'weight',
    ];

    /** Field data (CrUX) metric id => metric key */
    const FIELD_METRICS = [
        'LARGEST_CONTENTFUL_PAINT_MS' => 'lcp',
        'INTERACTION_TO_NEXT_PAINT' => 'inp',
        'CUMULATIVE_LAYOUT_SHIFT_SCORE' => 'cls',
        'FIRST_CONTENTFUL_PAINT_MS' => 'fcp',
        'EXPERIMENTAL_TIME_TO_FIRST_BYTE' => 'ttfb',
    ];

    /** Max number of failed audits kept per category */
    const MAX_AUDITS_PER_CATEGORY = 15;

    /** Audits scoring under this value are reported as failed (Lighthouse "good" threshold) */
    const FAILED_BELOW = 0.9;

    /**
     * Run Lighthouse on a URL.
     *
     * @param string $url
     * @param string $apiKey
     * @param string $strategy 'mobile' or 'desktop'
     * @param string $locale Language of the audit titles (e.g. "fr")
     * @param string $referer Sent as Referer, for API keys restricted to websites (HTTP referrers)
     *
     * @return array<string, mixed> Normalized result, or ['error' => string]
     */
    public function analyze(string $url, string $apiKey, string $strategy = 'mobile', string $locale = 'en', string $referer = ''): array
    {
        $query = 'url=' . rawurlencode($url)
            . '&strategy=' . rawurlencode($strategy)
            . '&locale=' . rawurlencode($locale)
            . '&key=' . rawurlencode($apiKey);
        foreach (self::CATEGORIES as $category) {
            $query .= '&category=' . $category;
        }

        // A Lighthouse run takes 10 to 30 seconds
        @set_time_limit(150);

        $ch = curl_init(self::ENDPOINT . '?' . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        if ($referer !== '') {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['error' => 'Unable to reach the PageSpeed Insights API: ' . $curlError];
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            return ['error' => 'Invalid response from the PageSpeed Insights API (HTTP ' . $httpCode . ').'];
        }

        if ($httpCode !== 200 || isset($json['error'])) {
            return ['error' => $this->getErrorMessage($json, $httpCode)];
        }

        if (!isset($json['lighthouseResult']) || !is_array($json['lighthouseResult'])) {
            return ['error' => 'The PageSpeed Insights API returned no Lighthouse result.'];
        }

        return $this->normalize($json);
    }

    /**
     * @param array<string, mixed> $json
     * @param int $httpCode
     *
     * @return string
     */
    private function getErrorMessage(array $json, int $httpCode): string
    {
        $message = isset($json['error']['message']) ? (string) $json['error']['message'] : '';

        if ($httpCode === 429) {
            return 'PageSpeed Insights quota exceeded. Try again later.';
        }
        if ($message === '') {
            return 'PageSpeed Insights API error (HTTP ' . $httpCode . ').';
        }

        return mb_substr($message, 0, 300);
    }

    /**
     * @param array<string, mixed> $json API response
     *
     * @return array<string, mixed>
     */
    private function normalize(array $json): array
    {
        $lighthouse = $json['lighthouseResult'];
        $audits = isset($lighthouse['audits']) && is_array($lighthouse['audits']) ? $lighthouse['audits'] : [];
        $categories = isset($lighthouse['categories']) && is_array($lighthouse['categories']) ? $lighthouse['categories'] : [];

        $scores = [];
        $failed = [];
        foreach (array_keys(self::CATEGORIES) as $category) {
            $scores[$category] = isset($categories[$category]['score']) && $categories[$category]['score'] !== null
                ? (int) round($categories[$category]['score'] * 100)
                : null;
            $failed[$category] = $this->getFailedAudits($categories[$category] ?? [], $audits);
        }

        $metrics = [];
        foreach (self::METRICS as $auditId => $key) {
            if (!isset($audits[$auditId])) {
                continue;
            }
            $metrics[$key] = [
                'value' => isset($audits[$auditId]['numericValue']) ? $audits[$auditId]['numericValue'] : null,
                'display' => isset($audits[$auditId]['displayValue']) ? (string) $audits[$auditId]['displayValue'] : '',
                'score' => isset($audits[$auditId]['score']) ? $audits[$auditId]['score'] : null,
            ];
        }

        return [
            'scores' => $scores,
            'metrics' => $metrics,
            'audits' => $failed,
            'field' => $this->getFieldData($json),
        ];
    }

    /**
     * Audits of a category that do not reach the "good" threshold, heaviest first.
     *
     * @param array<string, mixed> $category
     * @param array<string, mixed> $audits
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFailedAudits(array $category, array $audits): array
    {
        $failed = [];
        $refs = isset($category['auditRefs']) && is_array($category['auditRefs']) ? $category['auditRefs'] : [];

        foreach ($refs as $ref) {
            $id = isset($ref['id']) ? $ref['id'] : '';
            if ($id === '' || !isset($audits[$id])) {
                continue;
            }
            $audit = $audits[$id];
            $mode = isset($audit['scoreDisplayMode']) ? $audit['scoreDisplayMode'] : '';
            if (in_array($mode, ['notApplicable', 'informative', 'manual', 'error'], true)) {
                continue;
            }
            if (!isset($audit['score']) || $audit['score'] === null || $audit['score'] >= self::FAILED_BELOW) {
                continue;
            }

            $failed[] = [
                'id' => $id,
                'title' => isset($audit['title']) ? (string) $audit['title'] : $id,
                'display' => isset($audit['displayValue']) ? (string) $audit['displayValue'] : '',
                'score' => $audit['score'],
                'weight' => isset($ref['weight']) ? (float) $ref['weight'] : 0.0,
            ];
        }

        usort($failed, function ($a, $b) {
            if ($a['weight'] === $b['weight']) {
                return $a['score'] <=> $b['score'];
            }

            return $b['weight'] <=> $a['weight'];
        });

        return array_slice($failed, 0, self::MAX_AUDITS_PER_CATEGORY);
    }

    /**
     * Real-user data (Chrome UX Report), only available for pages with enough traffic.
     *
     * @param array<string, mixed> $json
     *
     * @return array<string, mixed>|null
     */
    private function getFieldData(array $json)
    {
        if (empty($json['loadingExperience']['metrics']) || !is_array($json['loadingExperience']['metrics'])) {
            return null;
        }

        $field = [
            'overall' => isset($json['loadingExperience']['overall_category']) ? (string) $json['loadingExperience']['overall_category'] : '',
            'metrics' => [],
        ];

        foreach (self::FIELD_METRICS as $id => $key) {
            if (!isset($json['loadingExperience']['metrics'][$id]['percentile'])) {
                continue;
            }
            $field['metrics'][$key] = [
                'value' => $json['loadingExperience']['metrics'][$id]['percentile'],
                'category' => isset($json['loadingExperience']['metrics'][$id]['category'])
                    ? (string) $json['loadingExperience']['metrics'][$id]['category']
                    : '',
            ];
        }

        return empty($field['metrics']) ? null : $field;
    }
}
