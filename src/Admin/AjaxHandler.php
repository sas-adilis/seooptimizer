<?php

namespace Adilis\SeoOptimizer\Admin;

use Adilis\SeoOptimizer\FrontAudit\FrontPageAnalyzer;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedAuditSync;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedClient;
use Adilis\SeoOptimizer\PageSpeed\PageSpeedSampler;
use Adilis\SeoOptimizer\Storage\PageSpeedStorage;
use Adilis\SeoOptimizer\Utils\JsonResponse;
use Adilis\SeoOptimizer\Utils\Message;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * AJAX actions of the module configuration controller.
 * Dispatched from SeoOptimizer::getContent() on "ajaxProcess" . ucfirst($action).
 */
class AjaxHandler
{
    /** Lighthouse strategy (Google indexes the mobile version first) */
    const PAGESPEED_STRATEGY = 'mobile';

    /** @var \Module */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function has(string $action): bool
    {
        return method_exists($this, 'ajaxProcess' . ucfirst($action));
    }

    /**
     * @param string $action
     *
     * @return mixed
     */
    public function dispatch(string $action)
    {
        $method = 'ajaxProcess' . ucfirst($action);

        return $this->$method();
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function ajaxProcessRunFullAudit()
    {
        $firstProcess = \Tools::getValue('first_process') === 'true';
        $runner = new \Adilis\SeoOptimizer\Pages\FullAuditRunner();
        $runner->run($firstProcess);
    }

    public function ajaxProcessReauditPage()
    {
        $url = \Tools::getValue('url');
        if (empty($url) || !\Validate::isUrl($url)) {
            $this->jsonExit(['status' => 'error', 'message' => Message::tr('Invalid URL')]);
        }

        $auditor = new \Adilis\SeoOptimizer\Pages\SinglePageAuditor();

        $this->jsonExit($auditor->auditUrl($url));
    }

    /**
     * Entity audit panel (BO edit pages).
     */
    public function ajaxProcessEntityAudit()
    {
        $emptyScore = ['score' => 0, 'grade' => '-', 'color' => 'gray'];

        $url = \Tools::getValue('url');
        if (empty($url) || !\Validate::isUrl($url)) {
            $this->jsonExit(['status' => 'success', 'data' => ['html' => '', 'score' => $emptyScore]]);
        }

        $result = (new FrontPageAnalyzer())->analyze($url);

        if (isset($result['error'])) {
            $this->jsonExit([
                'status' => 'success',
                'data' => [
                    'html' => '<p class="seoo-fa-empty">' . htmlspecialchars($result['error']) . '</p>',
                    'score' => $emptyScore,
                ],
            ]);
        }

        $this->jsonExit([
            'status' => 'success',
            'data' => ['html' => $this->renderAuditPanel($result), 'score' => $result['score']],
        ]);
    }

    /**
     * Return scores for a batch of entity IDs.
     */
    public function ajaxProcessGetEntityScores()
    {
        $entityType = \Tools::getValue('entity_type');
        $ids = \Tools::getValue('ids');

        if (empty($entityType) || empty($ids)) {
            $this->jsonExit(['status' => 'success', 'data' => []]);
        }

        $scores = \SeoOptimizerPage::getScoresByEntityType(pSQL($entityType));

        $result = [];
        foreach (array_map('intval', explode(',', $ids)) as $id) {
            if (isset($scores[$id])) {
                $result[$id] = $scores[$id];
                $result[$id]['color'] = \SeoOptimizerPage::gradeToColor($scores[$id]['grade']);
            }
        }

        $this->jsonExit(['status' => 'success', 'data' => $result]);
    }

    /**
     * Audit a batch of entities (max 10) and return their scores.
     * Called progressively from the BO listing grids for entities without scores.
     */
    public function ajaxProcessBatchEntityAudit()
    {
        $entityType = pSQL(\Tools::getValue('entity_type'));
        $ids = \Tools::getValue('ids');

        if (empty($entityType) || empty($ids)) {
            $this->jsonExit(['status' => 'success', 'data' => []]);
        }

        $context = \Context::getContext();
        $idLang = (int) $context->language->id;
        $idShop = (int) $context->shop->id;
        $analyzer = new FrontPageAnalyzer();
        $results = [];

        foreach (array_map('intval', explode(',', $ids)) as $idEntity) {
            $url = EntityHelper::getEntityUrl($entityType, $idEntity, $idLang);
            if (empty($url)) {
                continue;
            }

            $analysis = $analyzer->analyze($url);
            if (isset($analysis['error'])) {
                continue;
            }

            $score = isset($analysis['score']) ? $analysis['score'] : ['score' => 0, 'grade' => '-', 'color' => 'gray'];
            $scoreValue = (float) $score['score'];
            $grade = $score['grade'];

            // Upsert into seooptimizer_page
            $now = date('Y-m-d H:i:s');
            $existing = \Db::getInstance()->getValue(
                'SELECT id_seooptimizer_page FROM ' . _DB_PREFIX_ . 'seooptimizer_page
                WHERE entity_type = "' . pSQL($entityType) . '"
                AND id_entity = ' . (int) $idEntity . '
                AND id_lang = ' . (int) $idLang . '
                AND id_shop = ' . (int) $idShop
            );

            $countCritical = 0;
            $countWarning = 0;
            $countInfo = 0;
            $sections = ['meta', 'headings', 'content', 'images', 'links', 'structured_data', 'indexation', 'performance'];
            foreach ($sections as $section) {
                if (!isset($analysis[$section]['checks'])) {
                    continue;
                }
                foreach ($analysis[$section]['checks'] as $check) {
                    $status = isset($check['status']) ? $check['status'] : 'good';
                    if ($status === 'critical') {
                        ++$countCritical;
                    } elseif ($status === 'warning') {
                        ++$countWarning;
                    } elseif ($status === 'info') {
                        ++$countInfo;
                    }
                }
            }

            $data = [
                'entity_type' => pSQL($entityType),
                'id_entity' => (int) $idEntity,
                'id_lang' => (int) $idLang,
                'id_shop' => (int) $idShop,
                'url' => pSQL($url, true),
                'count_critical' => (int) $countCritical,
                'count_warning' => (int) $countWarning,
                'count_info' => (int) $countInfo,
                'count_total' => (int) ($countCritical + $countWarning + $countInfo),
                'score' => round($scoreValue, 1),
                'grade' => pSQL($grade),
                'date_audit' => $now,
                'date_upd' => $now,
            ];

            if ($existing) {
                \Db::getInstance()->update('seooptimizer_page', $data, 'id_seooptimizer_page = ' . (int) $existing);
            } else {
                $data['date_add'] = $now;
                $data['keywords'] = '';
                \Db::getInstance()->insert('seooptimizer_page', $data);
            }

            $results[$idEntity] = [
                'score' => $scoreValue,
                'grade' => $grade,
                'color' => \SeoOptimizerPage::gradeToColor($grade),
            ];
        }

        $this->jsonExit(['status' => 'success', 'data' => $results]);
    }

    /**
     * Return the entity audit panel HTML for a given entity.
     * Used when clicking a score badge in the BO listing.
     */
    public function ajaxProcessGetEntityAuditPanel()
    {
        $entityType = pSQL(\Tools::getValue('entity_type'));
        $idEntity = (int) \Tools::getValue('id_entity');
        $idLang = (int) \Context::getContext()->language->id;

        $url = EntityHelper::getEntityUrl($entityType, $idEntity, $idLang);
        if (empty($url)) {
            $this->jsonExit(['status' => 'error', 'message' => Message::tr('Unable to resolve URL')]);
        }

        $result = (new FrontPageAnalyzer())->analyze($url);

        if (isset($result['error'])) {
            $this->jsonExit([
                'status' => 'success',
                'data' => [
                    'html' => '<p class="seoo-fa-empty">' . htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8') . '</p>',
                    'score' => ['score' => 0, 'grade' => '-', 'color' => 'gray'],
                    'url' => $url,
                ],
            ]);
        }

        $this->jsonExit([
            'status' => 'success',
            'data' => ['html' => $this->renderAuditPanel($result), 'score' => $result['score'], 'url' => $url],
        ]);
    }

    /**
     * PageSpeed Insights analysis, one page per call.
     * The first call (first_process=true) builds the sample of pages; the following
     * ones analyze the next pending page until none is left.
     */
    public function ajaxProcessPageSpeedRun()
    {
        $apiKey = trim((string) \Configuration::get('SEOO_PSI_API_KEY'));
        if ($apiKey === '') {
            $this->jsonExit(['status' => 'error', 'message' => Message::tr('Missing PageSpeed Insights API key')]);
        }

        if (\Tools::getValue('first_process') === 'true') {
            $perType = (int) \Configuration::get('SEOO_PSI_PAGES_PER_TYPE') ?: 3;
            PageSpeedStorage::reset(PageSpeedSampler::getSample($perType), self::PAGESPEED_STRATEGY);
            $this->jsonExit(['status' => 'success', 'progress' => PageSpeedStorage::getProgress()]);
        }

        $page = PageSpeedStorage::getNextPending();
        if ($page === null) {
            $this->jsonExit(['status' => 'done', 'progress' => PageSpeedStorage::getProgress()]);
        }

        $result = (new PageSpeedClient())->analyze(
            $page['url'],
            $apiKey,
            $page['strategy'],
            (string) \Context::getContext()->language->iso_code,
            \Context::getContext()->shop->getBaseURL(true)
        );

        if (isset($result['error'])) {
            PageSpeedStorage::saveError((int) $page['id_seooptimizer_pagespeed'], $result['error']);
        } else {
            PageSpeedStorage::saveResult((int) $page['id_seooptimizer_pagespeed'], $result);
        }

        $finished = PageSpeedStorage::getNextPending() === null;
        if ($finished) {
            // Scores of the sample feed the "Performance (Lighthouse)" audit and the pages scores
            PageSpeedAuditSync::sync();
        }

        $this->jsonExit([
            'status' => $finished ? 'done' : 'success',
            'progress' => PageSpeedStorage::getProgress(),
            'url' => $page['url'],
            'error' => isset($result['error']) ? $result['error'] : '',
        ]);
    }

    /**
     * Get SEO configuration for an entity.
     */
    public function ajaxProcessGetSeoConfig()
    {
        $entityType = pSQL(\Tools::getValue('entity_type'));
        $idEntity = (int) \Tools::getValue('id_entity');

        if (empty($entityType) || $idEntity <= 0) {
            $this->jsonExit(['status' => 'error', 'message' => Message::tr('Invalid parameters')]);
        }

        if ((int) \Tools::getValue('all_langs')) {
            $data = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $data[$idLang] = \SeoOptimizerPage::getSeoConfig($entityType, $idEntity, $idLang);
            }
            $this->jsonExit(['status' => 'success', 'data' => $data]);
        }

        $idLang = (int) \Tools::getValue('id_lang');
        $config = \SeoOptimizerPage::getSeoConfig($entityType, $idEntity, $idLang > 0 ? $idLang : null);

        $this->jsonExit(['status' => 'success', 'data' => $config]);
    }

    /**
     * Save SEO configuration for an entity.
     */
    public function ajaxProcessSaveSeoConfig()
    {
        $entityType = pSQL(\Tools::getValue('entity_type'));
        $idEntity = (int) \Tools::getValue('id_entity');

        if (empty($entityType) || $idEntity <= 0) {
            $this->jsonExit(['status' => 'error', 'message' => Message::tr('Invalid parameters')]);
        }

        $noindex = (int) \Tools::getValue('noindex');
        $nofollow = (int) \Tools::getValue('nofollow');

        // Multi-lang save: keywords and canonical_url are per-language
        $langKeywords = \Tools::getValue('keywords_lang');
        $langCanonical = \Tools::getValue('canonical_url_lang');

        if (is_array($langKeywords) || is_array($langCanonical)) {
            $success = true;
            foreach (\Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $data = [
                    'keywords' => is_array($langKeywords) && isset($langKeywords[$idLang])
                        ? (string) $langKeywords[$idLang]
                        : '',
                    'canonical_url' => is_array($langCanonical) && isset($langCanonical[$idLang])
                        ? (string) $langCanonical[$idLang]
                        : '',
                    'noindex' => $noindex,
                    'nofollow' => $nofollow,
                ];
                if (!\SeoOptimizerPage::saveSeoConfig($entityType, $idEntity, $data, $idLang)) {
                    $success = false;
                }
            }
        } else {
            // Legacy single-lang save
            $idLang = (int) \Tools::getValue('id_lang');
            $data = [
                'keywords' => (string) \Tools::getValue('keywords'),
                'canonical_url' => (string) \Tools::getValue('canonical_url'),
                'noindex' => $noindex,
                'nofollow' => $nofollow,
            ];
            $success = \SeoOptimizerPage::saveSeoConfig($entityType, $idEntity, $data, $idLang > 0 ? $idLang : null);
        }

        if ($success) {
            $this->jsonExit(['status' => 'success', 'message' => Message::tr('SEO configuration saved')]);
        }

        $this->jsonExit(['status' => 'error', 'message' => Message::tr('Failed to save')]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function ajaxProcessGetChart()
    {
        switch (\Tools::getValue('chart')) {
            case 'unsecured_links':
            case 'redirected_links':
                return;
            case 'pages_not_found':
                $query = new \DbQuery();
                $query->select('DATE(date_add) as label');
                $query->select('COUNT(DISTINCT(url)) as value');
                $query->select('COUNT(*) as value2');
                $query->from('seooptimizer_log_404');
                $query->where('date_add > DATE_SUB(NOW(), INTERVAL 6 MONTH)');
                $query->groupBy('YEAR(date_add), MONTH(date_add), DAY(date_add)');
                $query->orderBy('date_add ASC');

                $datas = \Db::getInstance()->executeS($query);

                $labels = array_map(function ($label) {
                    return \Tools::displayDate($label);
                }, array_column($datas, 'label'));

                $this->jsonExit([
                    'status' => 'success',
                    'data' => [
                        'labels' => $labels,
                        'datasets' => [
                            [
                                'label' => '404 URLs',
                                'data' => array_column($datas, 'value'),
                            ],
                            [
                                'label' => 'Hits',
                                'data' => array_column($datas, 'value2'),
                            ],
                        ],
                    ],
                ]);
                break;
            default:
                $this->jsonExit(['status' => 'error', 'message' => Message::tr('Invalid chart')]);
        }
    }

    /**
     * @param array<string, mixed> $audit FrontPageAnalyzer result
     *
     * @return string
     */
    private function renderAuditPanel(array $audit): string
    {
        $smarty = \Context::getContext()->smarty;
        $smarty->assign([
            'seoo_audit' => $audit,
            'seoo_module_path' => $this->module->getPathUri(),
        ]);

        return $smarty->fetch($this->module->getLocalPath() . 'views/templates/hook/front-audit-panel.tpl');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonExit(array $payload)
    {
        JsonResponse::send($payload);
    }
}
