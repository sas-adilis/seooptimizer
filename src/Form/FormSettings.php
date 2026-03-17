<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Utils;

class FormSettings extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'SEO_OPTIMIZER_TITLE_MIN_LENGTH',
                        'label' => $this->l('Page title minimum length'),
                        'desc' => $this->l('Enter the minimum length required for a page title. Default is 50'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEO_OPTIMIZER_TITLE_MAX_LENGTH',
                        'label' => $this->l('Page title maximum length'),
                        'desc' => $this->l('Enter the maximum length required for a page title. Default is 70'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEO_OPTIMIZER_META_TITLE_MIN_LENGTH',
                        'label' => $this->l('Page meta title minimum length'),
                        'desc' => $this->l('Enter the minimum length required for a page title. Default is 140'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEO_OPTIMIZER_META_TITLE_MAX_LENGTH',
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
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'SEO_OPTIMIZER_TITLE_MIN_LENGTH' => Utils::getValOrConf('SEO_OPTIMIZER_TITLE_MIN_LENGTH'),
            'SEO_OPTIMIZER_TITLE_MAX_LENGTH' => Utils::getValOrConf('SEO_OPTIMIZER_TITLE_MAX_LENGTH'),
            'SEO_OPTIMIZER_META_TITLE_MIN_LENGTH' => Utils::getValOrConf('SEO_OPTIMIZER_META_TITLE_MIN_LENGTH'),
            'SEO_OPTIMIZER_META_TITLE_MAX_LENGTH' => Utils::getValOrConf('SEO_OPTIMIZER_META_TITLE_MAX_LENGTH'),
            'SEOO_PERF_THRESHOLD_GOOD' => Utils::getValOrConf('SEOO_PERF_THRESHOLD_GOOD'),
            'SEOO_PERF_THRESHOLD_SLOW' => Utils::getValOrConf('SEOO_PERF_THRESHOLD_SLOW'),
            'SEOO_WEIGHT_THRESHOLD_LIGHT' => Utils::getValOrConf('SEOO_WEIGHT_THRESHOLD_LIGHT'),
            'SEOO_WEIGHT_THRESHOLD_HEAVY' => Utils::getValOrConf('SEOO_WEIGHT_THRESHOLD_HEAVY'),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        \Configuration::updateValue('SEO_OPTIMIZER_TITLE_MIN_LENGTH', (int) \Tools::getValue('SEO_OPTIMIZER_TITLE_MIN_LENGTH'));
        \Configuration::updateValue('SEO_OPTIMIZER_TITLE_MAX_LENGTH', (int) \Tools::getValue('SEO_OPTIMIZER_TITLE_MAX_LENGTH'));
        \Configuration::updateValue('SEO_OPTIMIZER_META_TITLE_MIN_LENGTH', (int) \Tools::getValue('SEO_OPTIMIZER_META_TITLE_MIN_LENGTH'));
        \Configuration::updateValue('SEO_OPTIMIZER_META_TITLE_MAX_LENGTH', (int) \Tools::getValue('SEO_OPTIMIZER_META_TITLE_MAX_LENGTH'));
        \Configuration::updateValue('SEOO_PERF_THRESHOLD_GOOD', (int) \Tools::getValue('SEOO_PERF_THRESHOLD_GOOD'));
        \Configuration::updateValue('SEOO_PERF_THRESHOLD_SLOW', (int) \Tools::getValue('SEOO_PERF_THRESHOLD_SLOW'));
        \Configuration::updateValue('SEOO_WEIGHT_THRESHOLD_LIGHT', (int) \Tools::getValue('SEOO_WEIGHT_THRESHOLD_LIGHT'));
        \Configuration::updateValue('SEOO_WEIGHT_THRESHOLD_HEAVY', (int) \Tools::getValue('SEOO_WEIGHT_THRESHOLD_HEAVY'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
