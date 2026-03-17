<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Utils;

class FormMissingImageLegendFix extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('How to fix missing image legend'),
                    'icon' => 'icon-repair',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'name' => 'SEOO_FIX_IMAGE_LEGEND_METHOD',
                        'form_group_class' => 'show-if-fix-image-legend-enable',
                        'label' => $this->l('How to fix missing image legend'),
                        'desc' => $this->l('Enter the minimum length required for a page title. Default is 50'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => Constants::FIX_METHOD_TEXT,
                                    'name' => 'Define a rule text',
                                ],
                                [
                                    'id' => Constants::FIX_METHOD_IA,
                                    'name' => 'Use IA to generate a legend',
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_FIX_IMAGE_LEGEND_TEXT',
                        'label' => $this->l('Image legend rule'),
                        'form_group_class' => 'show-if-fix-image-legend-enable show-if-fix-image-legend-method-text',
                        'desc' => $this->l('Build a rule to generate a legend. You can use task variables like {title}, {meta_title}, {legend}'),
                        'tags' => [
                            '{product_title}' => 'Page title',
                            '{product_meta_title}' => 'Page meta title',
                            '{counter}' => 'Numeric counter',
                        ],
                        'required' => true,
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_FIX_IMAGE_LEGEND_IA_PROMPT',
                        'label' => $this->l('IA prompt'),
                        'form_group_class' => 'show-if-fix-image-legend-enable show-if-fix-image-legend-method-ia',
                        'desc' => $this->l('Enter the prompt to generate a legend with IA. You can use task variables like {title}, {meta_title}, {legend}'),
                        'required' => true,
                        'lang' => true,
                        'tags' => [
                            '{product_title}' => 'Page title',
                            '{product_meta_title}' => 'Page meta title',
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
            'SEOO_FIX_IMAGE_LEGEND_METHOD' => Utils::getValOrConf('SEOO_FIX_IMAGE_LEGEND_METHOD'),
            'SEOO_FIX_IMAGE_LEGEND_TEXT' => Utils::getValOrConf('SEOO_FIX_IMAGE_LEGEND_TEXT', true),
            'SEOO_FIX_IMAGE_LEGEND_IA_PROMPT' => Utils::getValOrConf('SEOO_FIX_IMAGE_LEGEND_IA_PROMPT', true),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        Utils::saveFormConfiguration('SEOO_FIX_IMAGE_LEGEND_METHOD');
        Utils::saveFormConfiguration('SEOO_FIX_IMAGE_LEGEND_TEXT', true);
        Utils::saveFormConfiguration('SEOO_FIX_IMAGE_LEGEND_IA_PROMPT', true);
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
