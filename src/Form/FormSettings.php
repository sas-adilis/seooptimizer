<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Audit\AuditRegistry;
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

        $auditKeys = AuditRegistry::getKeys();

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
                    'description' => $this->l('Configure SEO Optimizer settings: title and meta description length thresholds, server response time and weight thresholds for audits.'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TITLE_MIN_LENGTH',
                        'label' => $this->l('Page title minimum length'),
                        'desc' => $this->l('Enter the minimum recommended length of a page title. Default is 50'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_TITLE_MAX_LENGTH',
                        'label' => $this->l('Page title maximum length'),
                        'desc' => $this->l('Enter the maximum recommended length of a page title. Default is 70'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_META_TITLE_MIN_LENGTH',
                        'label' => $this->l('Meta description minimum length'),
                        'desc' => $this->l('Enter the minimum recommended length of a meta description. Default is 140'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_META_TITLE_MAX_LENGTH',
                        'label' => $this->l('Meta description maximum length'),
                        'desc' => $this->l('Enter the maximum recommended length of a meta description. Default is 170'),
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_perf',
                        'html_content' => '<hr><h4><i class="icon-dashboard"></i> ' . $this->l('Server response time thresholds') . '</h4>',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PERF_THRESHOLD_GOOD',
                        'label' => $this->l('Good threshold (ms)'),
                        'desc' => $this->l('Pages responding in less than this value (in milliseconds) are considered fast. Default: 800'),
                        'suffix' => 'ms',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PERF_THRESHOLD_SLOW',
                        'label' => $this->l('Slow threshold (ms)'),
                        'desc' => $this->l('Pages responding in more than this value (in milliseconds) are considered slow. Between good and slow = medium. Default: 1800'),
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
                    [
                        'type' => 'html',
                        'name' => 'separator_front_audit',
                        'html_content' => '<hr><h4><i class="icon-eye"></i> ' . $this->l('Front-office audit panel') . '</h4>',
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_FRONT_AUDIT_ENABLED',
                        'label' => $this->l('Enable front audit panel'),
                        'desc' => $this->l('Display a floating SEO audit button on the front-office for back-office employees. The button triggers a real-time SEO analysis of the current page.'),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'SEOO_FRONT_AUDIT_ENABLED_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_FRONT_AUDIT_ENABLED_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_bo_column',
                        'html_content' => '<hr><h4><i class="icon-columns"></i> ' . $this->l('Back-office listing') . '</h4>',
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_BO_SCORE_COLUMN',
                        'label' => $this->l('Show SEO score column'),
                        'desc' => $this->l('Display a SEO score column in back-office product, category, manufacturer and supplier listings. Requires a completed audit.'),
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'SEOO_BO_SCORE_COLUMN_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_BO_SCORE_COLUMN_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'name' => 'separator_pagespeed',
                        'html_content' => '<hr><h4><i class="icon-dashboard"></i> ' . $this->l('Google PageSpeed Insights (Lighthouse)') . '</h4>' . $this->getPageSpeedHelp(),
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PSI_API_KEY',
                        'label' => $this->l('PageSpeed Insights API key'),
                        'desc' => $this->l('Paste the API key created in the Google Cloud console. It is only used by the server to query the PageSpeed Insights API.'),
                        'class' => 'fixed-width-xxl',
                        'autocomplete' => false,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_PSI_PAGES_PER_TYPE',
                        'label' => $this->l('Pages analyzed per page type'),
                        'desc' => $this->l('The analysis covers the home page plus this number of pages for each type (products, categories, CMS pages...), between 1 and 10. Each page takes 10 to 30 seconds. Default: 3'),
                        'class' => 'fixed-width-xs',
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
            'SEOO_FRONT_AUDIT_ENABLED' => (int) Utils::getValOrConf('SEOO_FRONT_AUDIT_ENABLED'),
            'SEOO_BO_SCORE_COLUMN' => (int) Utils::getValOrConf('SEOO_BO_SCORE_COLUMN'),
            'SEOO_PSI_API_KEY' => Utils::getValOrConf('SEOO_PSI_API_KEY'),
            'SEOO_PSI_PAGES_PER_TYPE' => Utils::getValOrConf('SEOO_PSI_PAGES_PER_TYPE'),
        ]);
    }

    /**
     * Step-by-step instructions to get a PageSpeed Insights API key.
     *
     * @return string
     */
    private function getPageSpeedHelp(): string
    {
        $link = function (string $url, string $label): string {
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        };

        return '<div class="alert alert-info">'
            . '<p>' . $this->l('PageSpeed Insights runs Lighthouse on your pages from Google servers and returns performance, accessibility, best practices and SEO scores, plus the Core Web Vitals. The API is free; a key is required to avoid the very low quota of anonymous requests.') . '</p>'
            . '<p><strong>' . $this->l('How to get an API key') . '</strong></p>'
            . '<ol>'
            . '<li>' . sprintf($this->l('Sign in to the %s with a Google account and create a project (or select an existing one).'), $link('https://console.cloud.google.com/', $this->l('Google Cloud console'))) . '</li>'
            . '<li>' . sprintf($this->l('Open the %s page and click "Enable".'), $link('https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com', $this->l('PageSpeed Insights API'))) . '</li>'
            . '<li>' . sprintf($this->l('Go to %s, click "Create credentials" and choose "API key".'), $link('https://console.cloud.google.com/apis/credentials', $this->l('APIs & Services > Credentials'))) . '</li>'
            . '<li>' . $this->l('Edit the new key and, under "API restrictions", restrict it to "PageSpeed Insights API" only. An application restriction by website (HTTP referrers) also works if it includes your shop address (for example https://www.myshop.com/*): the module sends your shop URL as referrer. Restricting by the IP address of your server is another option.') . '</li>'
            . '<li>' . $this->l('Copy the key and paste it in the field below.') . '</li>'
            . '</ol>'
            . '<p>' . sprintf($this->l('More information in the %s.'), $link('https://developers.google.com/speed/docs/insights/v5/get-started', $this->l('official documentation'))) . '</p>'
            . '<p><i class="icon-warning-sign"></i> ' . $this->l('Your shop must be publicly reachable: Google fetches the pages itself, so a local or password-protected site cannot be analyzed. The URLs of the analyzed pages are sent to Google.') . '</p>'
            . '</div>';
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
        $psiApiKey = trim((string) \Tools::getValue('SEOO_PSI_API_KEY'));
        if ($psiApiKey !== '' && !preg_match('/^[A-Za-z0-9_\-]{20,100}$/', $psiApiKey)) {
            \Context::getContext()->controller->errors[] = $this->l('The PageSpeed Insights API key is not valid: it should only contain letters, digits, dashes and underscores.');

            return;
        }

        \Configuration::updateValue('SEOO_PSI_API_KEY', $psiApiKey);
        \Configuration::updateValue('SEOO_PSI_PAGES_PER_TYPE', max(1, min(10, (int) \Tools::getValue('SEOO_PSI_PAGES_PER_TYPE') ?: 3)));
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
        \Configuration::updateValue('SEOO_FRONT_AUDIT_ENABLED', (int) \Tools::getValue('SEOO_FRONT_AUDIT_ENABLED'));
        \Configuration::updateValue('SEOO_BO_SCORE_COLUMN', (int) \Tools::getValue('SEOO_BO_SCORE_COLUMN'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
