<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditRunner;
use Adilis\SeoOptimizer\Storage\PageSpeedStorage;
use Adilis\SeoOptimizer\Utils;

/**
 * "PageSpeed" tab: results of the Lighthouse analysis run through the PageSpeed Insights API.
 * The analysis itself is driven page by page from the browser (AjaxHandler::ajaxProcessPageSpeedRun).
 */
class FormPageSpeed extends FormAbstract implements FormInterface
{
    /** Lighthouse categories, in display order */
    const CATEGORIES = ['performance', 'accessibility', 'best-practices', 'seo'];

    public function getContent(): string
    {
        $context = \Context::getContext();
        $rows = [];
        $lastDate = '';

        foreach (PageSpeedStorage::getAll() as $row) {
            $rows[] = $this->formatRow($row);
            if ($row['date_upd'] > $lastDate) {
                $lastDate = $row['date_upd'];
            }
        }

        $progress = PageSpeedStorage::getProgress();

        $context->smarty->assign([
            'seoo_module_path' => __PS_BASE_URI__ . 'modules/' . Utils::MODULE_NAME . '/',
            'seoo_psi_has_key' => trim((string) \Configuration::get('SEOO_PSI_API_KEY')) !== '',
            'seoo_psi_rows' => $rows,
            'seoo_psi_pending' => $progress['total'] > $progress['processed'],
            'seoo_psi_last_date' => $lastDate !== '' ? \Tools::displayDate($lastDate, true) : '',
        ]);

        return $context->smarty->fetch(_PS_MODULE_DIR_ . Utils::MODULE_NAME . '/views/templates/admin/pagespeed.tpl');
    }

    /**
     * @param array<string, mixed> $row Database row
     *
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        $type = (string) $row['page_type'];
        $formatted = [
            'url' => $row['url'],
            'type' => $type === 'home' ? 'Home' : (isset(AuditRunner::$typeLabels[$type]) ? AuditRunner::$typeLabels[$type] : ucfirst($type)),
            'status' => $row['status'],
            'error' => (string) $row['error'],
            'scores' => [],
            'metrics' => [],
            'audits' => [],
            'field' => null,
        ];

        if ($row['status'] !== PageSpeedStorage::STATUS_DONE) {
            return $formatted;
        }

        foreach ([
            'performance' => 'score_performance',
            'accessibility' => 'score_accessibility',
            'best-practices' => 'score_best_practices',
            'seo' => 'score_seo',
        ] as $category => $column) {
            $score = $row[$column] === null ? null : (int) $row[$column];
            $formatted['scores'][$category] = ['value' => $score, 'class' => $this->scoreClass($score)];
        }

        $metrics = $row['metrics_json'] ? json_decode($row['metrics_json'], true) : [];
        foreach (['lcp', 'cls', 'tbt', 'fcp', 'si', 'ttfb', 'weight'] as $key) {
            if (isset($metrics[$key])) {
                $formatted['metrics'][$key] = [
                    'display' => $this->formatMetric($key, $metrics[$key]),
                    'class' => $this->metricClass($metrics[$key]['score']),
                ];
            }
        }

        $audits = $row['audits_json'] ? json_decode($row['audits_json'], true) : [];
        foreach (self::CATEGORIES as $category) {
            if (!empty($audits[$category])) {
                $formatted['audits'][$category] = $audits[$category];
            }
        }

        $field = $row['field_json'] ? json_decode($row['field_json'], true) : null;
        if (!empty($field['metrics'])) {
            $formatted['field'] = [];
            foreach ($field['metrics'] as $key => $metric) {
                $formatted['field'][$key] = [
                    'display' => $this->formatFieldMetric($key, $metric['value']),
                    'class' => $this->fieldClass($metric['category']),
                ];
            }
        }

        return $formatted;
    }

    /**
     * Lighthouse score colors: 90+ good, 50-89 average, under 50 poor.
     *
     * @param int|null $score
     *
     * @return string
     */
    private function scoreClass($score): string
    {
        if ($score === null) {
            return 'na';
        }

        return $score >= 90 ? 'good' : ($score >= 50 ? 'average' : 'poor');
    }

    /**
     * @param float|int|null $lighthouseScore Score of the audit, between 0 and 1
     *
     * @return string
     */
    private function metricClass($lighthouseScore): string
    {
        return $lighthouseScore === null ? 'na' : $this->scoreClass((int) round($lighthouseScore * 100));
    }

    private function fieldClass(string $category): string
    {
        switch ($category) {
            case 'FAST':
                return 'good';
            case 'AVERAGE':
                return 'average';
            case 'SLOW':
                return 'poor';
        }

        return 'na';
    }

    /**
     * @param string $key
     * @param array<string, mixed> $metric
     *
     * @return string
     */
    private function formatMetric(string $key, array $metric): string
    {
        if ($metric['value'] === null) {
            return '-';
        }

        if ($key === 'ttfb') {
            return $this->formatMs((float) $metric['value']);
        }
        if ($key === 'weight') {
            return $metric['value'] >= 1048576
                ? round($metric['value'] / 1048576, 1) . ' MB'
                : round($metric['value'] / 1024) . ' KB';
        }

        return $metric['display'] !== '' ? $metric['display'] : (string) round($metric['value'], 3);
    }

    /**
     * @param string $key
     * @param float|int $value Percentile (ms, or CLS x 100)
     *
     * @return string
     */
    private function formatFieldMetric(string $key, $value): string
    {
        return $key === 'cls' ? (string) round($value / 100, 2) : $this->formatMs((float) $value);
    }

    private function formatMs(float $ms): string
    {
        return $ms >= 1000 ? round($ms / 1000, 2) . ' s' : round($ms) . ' ms';
    }
}
