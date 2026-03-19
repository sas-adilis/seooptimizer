<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class FormCanonicalUrls extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Canonical URLs'),
                    'icon' => 'icon-code',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-canonical.png',
                    'description' => $this->l('Canonical URLs help manage duplicate content by designating a preferred version of a webpage, consolidating link equity and improving visibility in search results.'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_CANONICAL_URLS',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable Canonical URLs'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_CANONICAL_URLS_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_CANONICAL_URLS_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_CANONICAL_URLS_IGNORE_PARAMS',
                        'form_group_class' => 'show-if-enable-canonical-urls',
                        'label' => $this->l('Ignore parameters'),
                        'desc' => $this->l('List of parameters to ignore when generating canonical URLs. Separate each parameter with a comma.'),
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_ALTERNATE_URLS',
                        'form_group_class' => 'show-if-enable-canonical-urls',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable alternate URLs (hreflang)'),
                        'desc' => $this->l('Alternate URLs are different versions of the same webpage tailored for specific audiences, such as by language or region, and are indicated to search engines using this tag to ensure users receive the most relevant content.'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_ALTERNATE_URLS_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_ALTERNATE_URLS_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_CANONICAL_URLS_HTTP_HEADER',
                        'form_group_class' => 'show-if-enable-canonical-urls',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable canonical URLs in HTTP header'),
                        'desc' => $this->l('Add a link header with the canonical URL of the page for search engines.'),
                        'values' => [
                            ['id' => 'SEOO_CANONICAL_URLS_HTTP_HEADER_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_CANONICAL_URLS_HTTP_HEADER_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'SEOO_ENABLE_CANONICAL_URLS' => Utils::getValOrConf('SEOO_ENABLE_CANONICAL_URLS'),
            'SEOO_CANONICAL_URLS_IGNORE_PARAMS' => Utils::getValOrConf('SEOO_CANONICAL_URLS_IGNORE_PARAMS'),
            'SEOO_ENABLE_ALTERNATE_URLS' => Utils::getValOrConf('SEOO_ENABLE_ALTERNATE_URLS'),
            'SEOO_CANONICAL_URLS_HTTP_HEADER' => Utils::getValOrConf('SEOO_CANONICAL_URLS_HTTP_HEADER'),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        \Configuration::updateValue('SEOO_ENABLE_CANONICAL_URLS', (int) \Tools::getValue('SEOO_ENABLE_CANONICAL_URLS'));
        \Configuration::updateValue('SEOO_ENABLE_ALTERNATE_URLS', (int) \Tools::getValue('SEOO_ENABLE_ALTERNATE_URLS'));
        \Configuration::updateValue('SEOO_CANONICAL_URLS_HTTP_HEADER', (int) \Tools::getValue('SEOO_CANONICAL_URLS_HTTP_HEADER'));
    }
}
