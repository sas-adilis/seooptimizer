<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\RichSnippetSearcher;
use Adilis\SeoOptimizer\Utils;

class FormRichSnippets extends FormAbstract implements FormInterface
{
    /**
     * @throws \PrestaShopException
     */
    private function scanRichSnippet()
    {
        CacheManager::delete('scan_rich_snippets');
        $start_time = microtime(true);
        $richSnippetInTheme = new RichSnippetSearcher();
        $duration = microtime(true) - $start_time;

        $richSnippetInTheme->search();
        $cacheContent = [
            'duration' => \Tools::ps_round($duration, 2),
            'items' => $richSnippetInTheme->search(),
            'date' => date('Y-m-d H:i:s')
        ];

        CacheManager::write('scan_rich_snippets', $cacheContent);
    }

    /**
     * @throws \PrestaShopException
     */
    public function getContent(): string
    {
        if (!CacheManager::exists('scan_rich_snippets')) {
            $this->scanRichSnippet();
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Rich snippets'),
                    'icon' => 'icon-star',
                    'description' => $this->l('Rich snippets display enhanced information in Google search results (ratings, prices, availability). Configure structured data markup to improve your click-through rates.'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable Rich Snippets'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_BREADCRUMBS',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable Breadcrumbs'),
                        'desc' => $this->l('Breadcrumbs are a navigational aid that helps users understand the structure of a website and its hierarchy. They are displayed in search results to provide users with a clear path to the page they are looking for.'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_BREADCRUMBS_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_BREADCRUMBS_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_WEBPAGE',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable webpage informations'),
                        'desc' => $this->l('Webpage information is a structured data markup that provides search engines with details about a page, such as its title, URL, and description. This helps search engines understand the content of a page and display it more accurately in search results.'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_WEBPAGE_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_WEBPAGE_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_WEBSITE',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable website informations'),
                        'desc' => $this->l('Website information is a structured data markup that provides search engines with details about a website, such as its name, URL, and logo. This helps search engines understand the content of a website and display it more accurately in search results.'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_WEBSITE_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_WEBSITE_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Merchant return policy is a structured data markup that provides search engines with details about the return policy of a merchant. This helps search engines understand the return policy of a merchant and display it more accurately in search results.'),
                        'label' => $this->l('Enable merchant return policy'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY',
                        'label' => $this->l('Merchant return policy'),
                        'form_group_class' => 'show-if-enable-rs show-if-enable-rs-merchant-return-policy',
                        'desc' => $this->l('Select the best return policy according to your store'),
                        'required' => true,
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Pick an option'),
                            ],
                            'query' => [
                                ['id' => 'MerchantReturnFiniteReturnWindow', 'name' => $this->l('Returns are allowed with a delay restriction')],
                                ['id' => 'MerchantReturnNotPermitted', 'name' => $this->l('Returns are not allowed')],
                                ['id' => 'MerchantReturnUnspecified', 'name' => $this->l('Returns policy is not provided')],
                                ['id' => 'MerchantReturnUnlimitedReturnWindow', 'name' => $this->l('Returns are allowed without any delay restriction')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_RS_MERCHANT_RETURN_DAYS',
                        'form_group_class' => 'show-if-enable-rs show-if-enable-rs-merchant-return-policy',
                        'label' => $this->l('Return days for merchant return policy'),
                        'required' => true,
                        'class' => 'input fixed-width-sm',
                        'desc' => $this->l('Enter the number of days allowed for the return'),
                        'suffix' => $this->l('days'),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_RS_MERCHANT_RETURN_METHOD',
                        'label' => $this->l('Return method'),
                        'form_group_class' => 'show-if-enable-rs show-if-enable-rs-merchant-return-policy',
                        'desc' => $this->l('Select the most adapted return method according to your store'),
                        'required' => true,
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Pick an option'),
                            ],
                            'query' => [
                                ['id' => 'KeepProduct', 'name' => $this->l('Customer keep the product, even when receiving a refund or note credit')],
                                ['id' => 'ReturnByMail', 'name' => $this->l('Customer must return the product by mail')],
                                ['id' => 'ReturnInStore', 'name' => $this->l('Customer must return the product to the store')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_RS_MERCHANT_RETURN_FEES',
                        'form_group_class' => 'show-if-enable-rs show-if-enable-rs-merchant-return-policy',
                        'label' => $this->l('Return fees information'),
                        'desc' => $this->l('Select the return fees information according to your store'),
                        'required' => true,
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Pick an option'),
                            ],
                            'query' => [
                                ['id' => 'FreeReturn', 'name' => $this->l('Product returns are free of charge for the customer')],
                                ['id' => 'OriginalShippingFees', 'name' => $this->l('Customer must pay the original shipping costs when returning a product')],
                                ['id' => 'RestockingFees', 'name' => $this->l('Customer must pay a restocking fee when returning a product')],
                                ['id' => 'ReturnFeesCustomerResponsibility', 'name' => $this->l('Product returns must be paid for, and are the responsibility of, the customer')],
                                ['id' => 'ReturnShippingFees', 'name' => $this->l('Customer must pay the return shipping costs when returning a product')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_ADDITIONAL_PROPERTY',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable additional property'),
                        'desc' => $this->l('Additional property is a structured data markup that provides search engines with additional details about a product, such as its features, stores, or other properties. This helps search engines understand the content of a product and display it more accurately in search results.'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_ADDITIONAL_PROPERTY_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_ADDITIONAL_PROPERTY_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'SEOO_ENABLE_RS_FEATURES',
                        'id' => 'SEOO_ENABLE_RS_FEATURES',
                        'form_group_class' => 'show-if-enable-rs show-if-enable-rs-additional-property',
                        'label' => $this->l('Select features to display in additional property'),
                        'multiple' => true,
                        'required' => true,
                        'desc' => $this->l('Select the features you want to display in the additional property'),
                        'options' => [
                            'default' => ['value' => null, 'label' => $this->l('Display all features')],
                            'query' => \Feature::getFeatures(\Context::getContext()->cookie->id_lang),
                            'id' => 'id_feature',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_RS_STORE',
                        'form_group_class' => 'show-if-enable-rs',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Store information is a structured data markup that provides search engines with details about a store, such as its name, address, and contact information. This helps search engines understand the content of a store and display it more accurately in search results.'),
                        'label' => $this->l('Enable stores'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_RS_STORE_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_RS_STORE_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'name' => 'searchRichSnippet',
                        'text' => $this->l('Scan now'),
                        'label' => $this->l('Search rich snippets already present in your files'),
                        'desc' => $this->l('This allows you to search for rich extracts in your files that would otherwise prevent the module from setting up rich snippets.'),
                        'ajaxAction' => 'scanRichSnippet',
                        'ajaxTarget' => '#searchRichSnippetResult',
                    ],
                    [
                        'type' => 'html',
                        'name' => 'searchRichSnippetResult',
                        'html_content' => $this->getRichSnippetSearchResult()
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'SEOO_ENABLE_RS' => Utils::getValOrConf('SEOO_ENABLE_RS'),
            'SEOO_ENABLE_RS_BREADCRUMBS' => Utils::getValOrConf('SEOO_ENABLE_RS_BREADCRUMBS'),
            'SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY' => Utils::getValOrConf('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY'),
            'SEOO_ENABLE_RS_WEBPAGE' => Utils::getValOrConf('SEOO_ENABLE_RS_WEBPAGE'),
            'SEOO_ENABLE_RS_WEBSITE' => Utils::getValOrConf('SEOO_ENABLE_RS_WEBSITE'),
            'SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY' => Utils::getValOrConf('SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY'),
            'SEOO_RS_MERCHANT_RETURN_DAYS' => Utils::getValOrConf('SEOO_RS_MERCHANT_RETURN_DAYS'),
            'SEOO_RS_MERCHANT_RETURN_METHOD' => Utils::getValOrConf('SEOO_RS_MERCHANT_RETURN_METHOD'),
            'SEOO_RS_MERCHANT_RETURN_FEES' => Utils::getValOrConf('SEOO_RS_MERCHANT_RETURN_FEES'),
            'SEOO_ENABLE_RS_ADDITIONAL_PROPERTY' => Utils::getValOrConf('SEOO_ENABLE_RS_ADDITIONAL_PROPERTY'),
            'SEOO_ENABLE_RS_STORE' => Utils::getValOrConf('SEOO_ENABLE_RS_STORE'),
            'SEOO_ENABLE_RS_FEATURES[]' => Utils::getValOrConf('SEOO_ENABLE_RS_FEATURES'),
        ]);
    }

    public function postProcess()
    {
        \Configuration::updateValue('SEOO_ENABLE_RS', (int) \Tools::getValue('SEOO_ENABLE_RS'));
        \Configuration::updateValue('SEOO_ENABLE_RS_BREADCRUMBS', (int) \Tools::getValue('SEOO_ENABLE_RS_BREADCRUMBS'));
        \Configuration::updateValue('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY', (int) \Tools::getValue('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY'));
        \Configuration::updateValue('SEOO_ENABLE_RS_WEBPAGE', (int) \Tools::getValue('SEOO_ENABLE_RS_WEBPAGE'));
        \Configuration::updateValue('SEOO_ENABLE_RS_WEBSITE', (int) \Tools::getValue('SEOO_ENABLE_RS_WEBSITE'));
        \Configuration::updateValue('SEOO_RS_MERCHANT_RETURN_DAYS', (int) \Tools::getValue('SEOO_RS_MERCHANT_RETURN_DAYS'));
        \Configuration::updateValue('SEOO_ENABLE_RS_ADDITIONAL_PROPERTY', (int) \Tools::getValue('SEOO_ENABLE_RS_ADDITIONAL_PROPERTY'));
        \Configuration::updateValue('SEOO_ENABLE_RS_STORE', (int) \Tools::getValue('SEOO_ENABLE_RS_STORE'));
        \Configuration::updateValue('SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY', \Tools::getValue('SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY'));
        \Configuration::updateValue('SEOO_RS_MERCHANT_RETURN_METHOD', \Tools::getValue('SEOO_RS_MERCHANT_RETURN_METHOD'));
        \Configuration::updateValue('SEOO_RS_MERCHANT_RETURN_FEES', \Tools::getValue('SEOO_RS_MERCHANT_RETURN_FEES'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }

    /**
     * @throws \PrestaShopException
     */
    public function ajaxProcessScanRichSnippet(): string
    {
        $this->scanRichSnippet();
        echo $this->getRichSnippetSearchResult();
        exit;
    }

    private function getRichSnippetSearchResult(): string
    {
        if (CacheManager::exists('scan_rich_snippets')) {
            $cacheContent = CacheManager::get('scan_rich_snippets');
            \Context::getContext()->smarty->assign([
                'duration' => $cacheContent['duration'],
                'items' => $cacheContent['items'],
                'date' => $cacheContent['date'],
            ]);
            return \Context::getContext()->smarty->fetch(
                Utils::getModulePath() . 'views/templates/admin/list-search-rich-snippets.tpl'
            );
        }

        return '';
    }
}
