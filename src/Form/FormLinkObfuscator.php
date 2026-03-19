<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class FormLinkObfuscator extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Link obfuscation'),
                    'icon' => 'icon-link',
                    'description' => $this->l('Link obfuscation is an SEO technique used to hide or alter the appearance of URLs on a website, making them less detectable by search engine bots while maintaining their functionality for users. This method is often employed to strategically manage the distribution of SEO value, or “link juice,” across a site. By obfuscating links, webmasters can direct search engine crawlers away from less important pages and towards more relevant ones, thereby optimizing the site’s internal linking structure and improving the visibility of key pages in search results.'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_LINK_OBFUSCATION',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable link obfuscation'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_LINK_OBFUSCATION_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_LINK_OBFUSCATION_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                        'desc' => $this->l('By enabling this option, all html links with HTML attribute [data-obfuscate] in your content will be obfuscated. This will help you to hide your links from search engines and protect your website from negative SEO. Give this information to your developers and adapt your theme.'),
                    ],

                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], [
            'SEOO_ENABLE_LINK_OBFUSCATION' => Utils::getValOrConf('SEOO_ENABLE_LINK_OBFUSCATION')
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        Utils::saveFormIntConfiguration('SEOO_ENABLE_LINK_OBFUSCATION');
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}