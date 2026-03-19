<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class FormSettings extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        $module = \Module::getInstanceByName(Utils::MODULE_NAME);
        $context = \Context::getContext();
        $cronBaseUrl = $context->link->getModuleLink(Utils::MODULE_NAME, 'cron', [
            'token' => $module->secure_key,
        ]);

        $auditKeys = [
            'heading_hierarchy', 'missing_alt', 'broken_links', 'page_load_time',
            'page_weight', 'unsecured_links', 'meta_tags', 'internal_links',
            'text_ratio', 'keyword_check',
        ];

        $cronUrls = [];
        $cronUrls[] = ['label' => $this->l('Full audit (all)'), 'url' => $cronBaseUrl . '&audit=all'];
        foreach ($auditKeys as $key) {
            $cronUrls[] = ['label' => $key, 'url' => $cronBaseUrl . '&audit=' . $key];
        }

        $context->smarty->assign('seoo_cron_urls', $cronUrls);

        return $this->renderCronSection() . $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-configure.png',
                    'description' => $this->l('Configure SEO Optimizer settings: title and meta description length thresholds, page load time and weight thresholds for audits.'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TITLE_MIN_LENGTH',
                        'label' => $this->l('Page title minimum length'),
                        'desc' => $this->l('Enter the minimum length required for a page title. Default is 50'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TITLE_MAX_LENGTH',
                        'label' => $this->l('Page title maximum length'),
                        'desc' => $this->l('Enter the maximum length required for a page title. Default is 70'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_META_TITLE_MIN_LENGTH',
                        'label' => $this->l('Page meta title minimum length'),
                        'desc' => $this->l('Enter the minimum length required for a page title. Default is 140'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_META_TITLE_MAX_LENGTH',
                        'label' => $this->l('Page meta title maximum length'),
                        'desc' => $this->l('Enter the maximum length required for a page meta title. Default is 170'),
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_perf',
                        'html_content' => '<hr><h4><i class="icon-dashboard"></i> ' . $this->l('Page load time thresholds') . '</h4>',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PERF_THRESHOLD_GOOD',
                        'label' => $this->l('Good threshold (ms)'),
                        'desc' => $this->l('Pages loading under this value (in milliseconds) are considered fast. Default: 750'),
                        'suffix' => 'ms',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PERF_THRESHOLD_SLOW',
                        'label' => $this->l('Slow threshold (ms)'),
                        'desc' => $this->l('Pages loading above this value (in milliseconds) are considered slow. Between good and slow = medium. Default: 1000'),
                        'suffix' => 'ms',
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_weight',
                        'html_content' => '<hr><h4><i class="icon-hdd-o"></i> ' . $this->l('Page weight thresholds') . '</h4>',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_WEIGHT_THRESHOLD_LIGHT',
                        'label' => $this->l('Light threshold (KB)'),
                        'desc' => $this->l('Pages under this total weight (in KB) are considered light. Default: 1024 (1 MB)'),
                        'suffix' => 'KB',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_WEIGHT_THRESHOLD_HEAVY',
                        'label' => $this->l('Heavy threshold (KB)'),
                        'desc' => $this->l('Pages above this total weight (in KB) are considered heavy. Between light and heavy = moderate. Default: 3072 (3 MB)'),
                        'suffix' => 'KB',
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_text',
                        'html_content' => '<hr><h4><i class="icon-font"></i> ' . $this->l('Text content thresholds') . '</h4>',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TEXT_THRESHOLD_LOW',
                        'label' => $this->l('Insufficient threshold (words)'),
                        'desc' => $this->l('Pages with fewer words than this value are considered insufficient. Default: 100'),
                        'suffix' => 'words',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TEXT_THRESHOLD_GOOD',
                        'label' => $this->l('Good threshold (words)'),
                        'desc' => $this->l('Pages with more words than this value are considered good. Between insufficient and good = improvable. Default: 300'),
                        'suffix' => 'words',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'SEOO_TITLE_MIN_LENGTH' => Utils::getValOrConf('SEOO_TITLE_MIN_LENGTH'),
            'SEOO_TITLE_MAX_LENGTH' => Utils::getValOrConf('SEOO_TITLE_MAX_LENGTH'),
            'SEOO_META_TITLE_MIN_LENGTH' => Utils::getValOrConf('SEOO_META_TITLE_MIN_LENGTH'),
            'SEOO_META_TITLE_MAX_LENGTH' => Utils::getValOrConf('SEOO_META_TITLE_MAX_LENGTH'),
            'SEOO_PERF_THRESHOLD_GOOD' => Utils::getValOrConf('SEOO_PERF_THRESHOLD_GOOD'),
            'SEOO_PERF_THRESHOLD_SLOW' => Utils::getValOrConf('SEOO_PERF_THRESHOLD_SLOW'),
            'SEOO_WEIGHT_THRESHOLD_LIGHT' => Utils::getValOrConf('SEOO_WEIGHT_THRESHOLD_LIGHT'),
            'SEOO_WEIGHT_THRESHOLD_HEAVY' => Utils::getValOrConf('SEOO_WEIGHT_THRESHOLD_HEAVY'),
            'SEOO_TEXT_THRESHOLD_LOW' => Utils::getValOrConf('SEOO_TEXT_THRESHOLD_LOW'),
            'SEOO_TEXT_THRESHOLD_GOOD' => Utils::getValOrConf('SEOO_TEXT_THRESHOLD_GOOD'),
        ]);
    }

    /**
     * @return string
     */
    private function renderCronSection(): string
    {
        return \Context::getContext()->smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/cron-urls.tpl'
        );
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        \Configuration::updateValue('SEOO_TITLE_MIN_LENGTH', (int) \Tools::getValue('SEOO_TITLE_MIN_LENGTH'));
        \Configuration::updateValue('SEOO_TITLE_MAX_LENGTH', (int) \Tools::getValue('SEOO_TITLE_MAX_LENGTH'));
        \Configuration::updateValue('SEOO_META_TITLE_MIN_LENGTH', (int) \Tools::getValue('SEOO_META_TITLE_MIN_LENGTH'));
        \Configuration::updateValue('SEOO_META_TITLE_MAX_LENGTH', (int) \Tools::getValue('SEOO_META_TITLE_MAX_LENGTH'));
        \Configuration::updateValue('SEOO_PERF_THRESHOLD_GOOD', (int) \Tools::getValue('SEOO_PERF_THRESHOLD_GOOD'));
        \Configuration::updateValue('SEOO_PERF_THRESHOLD_SLOW', (int) \Tools::getValue('SEOO_PERF_THRESHOLD_SLOW'));
        \Configuration::updateValue('SEOO_WEIGHT_THRESHOLD_LIGHT', (int) \Tools::getValue('SEOO_WEIGHT_THRESHOLD_LIGHT'));
        \Configuration::updateValue('SEOO_WEIGHT_THRESHOLD_HEAVY', (int) \Tools::getValue('SEOO_WEIGHT_THRESHOLD_HEAVY'));
        \Configuration::updateValue('SEOO_TEXT_THRESHOLD_LOW', (int) \Tools::getValue('SEOO_TEXT_THRESHOLD_LOW'));
        \Configuration::updateValue('SEOO_TEXT_THRESHOLD_GOOD', (int) \Tools::getValue('SEOO_TEXT_THRESHOLD_GOOD'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
