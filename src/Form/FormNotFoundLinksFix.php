<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\TranslateHelper;
use Adilis\SeoOptimizer\Utils;

class FormNotFoundLinksFix extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('How to fix?'),
                    'icon' => 'icon-repair',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'name' => 'SEOO_FIX_NOT_FOUND_LINKS_METHOD',
                        'label' => $this->l('How to fix link'),
                        'options' => [
                            'query' => [
                                [
                                    'id' => Constants::FIX_METHOD_IGNORE,
                                    'name' => TranslateHelper::get()->l('Ignore'),
                                ],
                                [
                                    'id' => Constants::FIX_METHOD_REMOVE,
                                    'name' => TranslateHelper::get()->l('Remove link/image'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
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
            'SEOO_FIX_NOT_FOUND_LINKS_METHOD' => Utils::getValOrConf('SEOO_FIX_NOT_FOUND_LINKS_METHOD')
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        Utils::saveFormConfiguration('SEOO_FIX_NOT_FOUND_LINKS_METHOD');
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
