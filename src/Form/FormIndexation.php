<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Utils;

class FormIndexation extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-configure.png',
                    'description' => $this->l('Control how search engines index your pages. Set noindex, redirect or return 404 for supplier, manufacturer, store and sitemap pages.'),
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'name' => 'SEOO_SUPPLIER_PAGE_INDEXATION',
                        'required' => true,
                        'label' => $this->l('Supplier page indexation'),
                        'values' => [
                            ['id' => 'SEOO_SUPPLIER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_DO_NOTHING, 'value' => Constants::PAGE_INDEXATION_DO_NOTHING, 'label' => $this->l('Do nothing')],
                            ['id' => 'SEOO_SUPPLIER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_NOINDEX, 'value' => Constants::PAGE_INDEXATION_NOINDEX, 'label' => $this->l('Disable page indexation')],
                            ['id' => 'SEOO_SUPPLIER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_404, 'value' => Constants::PAGE_INDEXATION_404, 'label' => $this->l('Redirect to 404 page')],
                            ['id' => 'SEOO_SUPPLIER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_301, 'value' => Constants::PAGE_INDEXATION_REDIRECT_301, 'label' => $this->l('Redirect to another page (301 permanent)')],
                            ['id' => 'SEOO_SUPPLIER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_302, 'value' => Constants::PAGE_INDEXATION_REDIRECT_302, 'label' => $this->l('Redirect to another page (302 temporary)')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_SUPPLIER_PAGE_REDIRECTION',
                        'label' => $this->l('Redirection URL'),
                        'required' => true,
                    ],
                    [
                        'type' => 'radio',
                        'name' => 'SEOO_MANUFACTURER_PAGE_INDEXATION',
                        'required' => true,
                        'label' => $this->l('Manufacturer page indexation'),
                        'values' => [
                            ['id' => 'SEOO_MANUFACTURER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_DO_NOTHING, 'value' => Constants::PAGE_INDEXATION_DO_NOTHING, 'label' => $this->l('Do nothing')],
                            ['id' => 'SEOO_MANUFACTURER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_NOINDEX, 'value' => Constants::PAGE_INDEXATION_NOINDEX, 'label' => $this->l('Disable page indexation')],
                            ['id' => 'SEOO_MANUFACTURER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_404, 'value' => Constants::PAGE_INDEXATION_404, 'label' => $this->l('Redirect to 404 page')],
                            ['id' => 'SEOO_MANUFACTURER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_301, 'value' => Constants::PAGE_INDEXATION_REDIRECT_301, 'label' => $this->l('Redirect to another page (301 permanent)')],
                            ['id' => 'SEOO_MANUFACTURER_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_302, 'value' => Constants::PAGE_INDEXATION_REDIRECT_302, 'label' => $this->l('Redirect to another page (302 temporary)')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_MANUFACTURER_PAGE_REDIRECTION',
                        'label' => $this->l('Redirection URL'),
                        'required' => true,
                    ],
                    [
                        'type' => 'radio',
                        'name' => 'SEOO_STORE_PAGE_INDEXATION',
                        'required' => true,
                        'label' => $this->l('Store page indexation'),
                        'values' => [
                            ['id' => 'SEOO_STORE_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_DO_NOTHING, 'value' => Constants::PAGE_INDEXATION_DO_NOTHING, 'label' => $this->l('Do nothing')],
                            ['id' => 'SEOO_STORE_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_NOINDEX, 'value' => Constants::PAGE_INDEXATION_NOINDEX, 'label' => $this->l('Disable page indexation')],
                            ['id' => 'SEOO_STORE_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_404, 'value' => Constants::PAGE_INDEXATION_404, 'label' => $this->l('Redirect to 404 page')],
                            ['id' => 'SEOO_STORE_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_301, 'value' => Constants::PAGE_INDEXATION_REDIRECT_301, 'label' => $this->l('Redirect to another page (301 permanent)')],
                            ['id' => 'SEOO_STORE_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_302, 'value' => Constants::PAGE_INDEXATION_REDIRECT_302, 'label' => $this->l('Redirect to another page (302 temporary)')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_STORE_PAGE_REDIRECTION',
                        'label' => $this->l('Redirection URL'),
                        'required' => true,
                    ],
                    [
                        'type' => 'radio',
                        'name' => 'SEOO_SITEMAP_PAGE_INDEXATION',
                        'required' => true,
                        'label' => $this->l('Sitemap page indexation'),
                        'values' => [
                            ['id' => 'SEOO_SITEMAP_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_DO_NOTHING, 'value' => Constants::PAGE_INDEXATION_DO_NOTHING, 'label' => $this->l('Do nothing')],
                            ['id' => 'SEOO_SITEMAP_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_NOINDEX, 'value' => Constants::PAGE_INDEXATION_NOINDEX, 'label' => $this->l('Disable page indexation')],
                            ['id' => 'SEOO_SITEMAP_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_404, 'value' => Constants::PAGE_INDEXATION_404, 'label' => $this->l('Redirect to 404 page')],
                            ['id' => 'SEOO_SITEMAP_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_301, 'value' => Constants::PAGE_INDEXATION_REDIRECT_301, 'label' => $this->l('Redirect to another page (301 permanent)')],
                            ['id' => 'SEOO_SITEMAP_PAGE_INDEXATION_' . Constants::PAGE_INDEXATION_REDIRECT_302, 'value' => Constants::PAGE_INDEXATION_REDIRECT_302, 'label' => $this->l('Redirect to another page (302 temporary)')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_SITEMAP_PAGE_REDIRECTION',
                        'label' => $this->l('Redirection URL'),
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
            'SEOO_SUPPLIER_PAGE_INDEXATION' => Utils::getValOrConf('SEOO_SUPPLIER_PAGE_INDEXATION'),
            'SEOO_MANUFACTURER_PAGE_INDEXATION' => Utils::getValOrConf('SEOO_MANUFACTURER_PAGE_INDEXATION'),
            'SEOO_SITEMAP_PAGE_INDEXATION' => Utils::getValOrConf('SEOO_SITEMAP_PAGE_INDEXATION'),
            'SEOO_STORE_PAGE_INDEXATION' => Utils::getValOrConf('SEOO_STORE_PAGE_INDEXATION'),
            'SEOO_SUPPLIER_PAGE_REDIRECTION' => Utils::getValOrConf('SEOO_SUPPLIER_PAGE_REDIRECTION'),
            'SEOO_MANUFACTURER_PAGE_REDIRECTION' => Utils::getValOrConf('SEOO_MANUFACTURER_PAGE_REDIRECTION'),
            'SEOO_STORE_PAGE_REDIRECTION' => Utils::getValOrConf('SEOO_STORE_PAGE_REDIRECTION'),
            'SEOO_SITEMAP_PAGE_REDIRECTION' => Utils::getValOrConf('SEOO_SITEMAP_PAGE_REDIRECTION'),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        $context = \Context::getContext();
        $settings_to_save = [
            'SEOO_SUPPLIER_PAGE_INDEXATION' => 'SEOO_SUPPLIER_PAGE_REDIRECTION',
            'SEOO_MANUFACTURER_PAGE_INDEXATION' => 'SEOO_MANUFACTURER_PAGE_REDIRECTION',
            'SEOO_STORE_PAGE_INDEXATION' => 'SEOO_STORE_PAGE_REDIRECTION',
            'SEOO_SITEMAP_PAGE_INDEXATION' => 'SEOO_SITEMAP_PAGE_REDIRECTION',
        ];

        foreach ($settings_to_save as $setting => $setting_url) {
            if (
                in_array((int) \Tools::getValue($setting), [Constants::PAGE_INDEXATION_REDIRECT_301, Constants::PAGE_INDEXATION_REDIRECT_302])
                && (!\Validate::isUrl(\Tools::getValue($setting_url)))
            ) {
                $context->controller->errors[] = $this->l('You must specify a valid redirection URL');
            }
        }

        if (!count($context->controller->errors)) {
            foreach ($settings_to_save as $setting => $setting_url) {
                \Configuration::updateValue($setting, (int) \Tools::getValue($setting));
                if (in_array((int) \Tools::getValue($setting), [Constants::PAGE_INDEXATION_REDIRECT_301, Constants::PAGE_INDEXATION_REDIRECT_302])) {
                    \Configuration::updateValue($setting_url, \Tools::getValue($setting_url));
                } else {
                    \Configuration::updateValue($setting_url, '');
                }
            }
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        }
    }
}
