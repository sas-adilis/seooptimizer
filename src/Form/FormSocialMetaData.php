<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class FormSocialMetaData extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Metadata social'),
                    'icon' => 'icon-rss',
                    'description' => $this->l('Enable Open Graph and Twitter Card metadata to control how your pages appear when shared on social networks.'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_ENABLE_SOCIAL_METADATA',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Enable social metadata'),
                        'values' => [
                            ['id' => 'SEOO_ENABLE_SOCIAL_METADATA__on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_ENABLE_SOCIAL_METADATA__off', 'value' => 0, 'label' => $this->l('No')],
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
            'SEOO_ENABLE_SOCIAL_METADATA' => Utils::getValOrConf('SEOO_ENABLE_SOCIAL_METADATA'),
        ]);
    }

    public function postProcess()
    {
        Utils::saveFormIntConfiguration('SEOO_ENABLE_SOCIAL_METADATA');
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
