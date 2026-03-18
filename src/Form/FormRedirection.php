<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Utils;

class FormRedirection extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                    'visual' => __PS_BASE_URI__ . 'modules/seooptimizer/views/img/panda-configure.png',
                    'description' => $this->l('Configure automatic redirection behavior when products or categories are deactivated or deleted.'),
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_REDIRECT_INACTIVE_PRODUCT',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Redirect inactives products to category parent'),
                        'desc' => $this->l('If a product is inactive, it will be redirected to its parent category only if no custom redirection action is specified.'),
                        'values' => [
                            ['id' => 'SEOO_REDIRECT_INACTIVE_PRODUCT_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_REDIRECT_INACTIVE_PRODUCT_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_REDIRECT_DELETED_PRODUCT',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Redirect deleted products to category parent'),
                        'desc' => $this->l('Before deleting a product, a redirection will be added to the default category.'),
                        'values' => [
                            ['id' => 'SEOO_REDIRECT_DELETED_PRODUCT_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_REDIRECT_DELETED_PRODUCT_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_REDIRECT_INACTIVE_CATEGORY',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Redirect inactives categories to category parent'),
                        'desc' => $this->l('If a category is inactive, it will be redirected to its parent.'),
                        'values' => [
                            ['id' => 'SEOO_REDIRECT_INACTIVE_CATEGORY_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_REDIRECT_INACTIVE_CATEGORY_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'name' => 'SEOO_REDIRECT_DELETED_CATEGORY',
                        'required' => true,
                        'is_bool' => true,
                        'label' => $this->l('Redirect deleted categories to category parent'),
                        'desc' => $this->l('Before deleting a category, a redirection will be added to this parent.'),
                        'values' => [
                            ['id' => 'SEOO_REDIRECT_DELETED_CATEGORY_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'SEOO_REDIRECT_DELETED_CATEGORY_off', 'value' => 0, 'label' => $this->l('No')],
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
            'SEOO_REDIRECT_INACTIVE_PRODUCT' => Utils::getValOrConf('SEOO_REDIRECT_INACTIVE_PRODUCT'),
            'SEOO_REDIRECT_DELETED_PRODUCT' => Utils::getValOrConf('SEOO_REDIRECT_DELETED_PRODUCT'),
            'SEOO_REDIRECT_INACTIVE_CATEGORY' => Utils::getValOrConf('SEOO_REDIRECT_INACTIVE_CATEGORY'),
            'SEOO_REDIRECT_DELETED_CATEGORY' => Utils::getValOrConf('SEOO_REDIRECT_DELETED_CATEGORY'),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        \Configuration::updateValue('SEOO_REDIRECT_INACTIVE_PRODUCT', (int) \Tools::getValue('SEOO_REDIRECT_INACTIVE_PRODUCT'));
        \Configuration::updateValue('SEOO_REDIRECT_DELETED_PRODUCT', (int) \Tools::getValue('SEOO_REDIRECT_DELETED_PRODUCT'));
        \Configuration::updateValue('SEOO_REDIRECT_INACTIVE_CATEGORY', (int) \Tools::getValue('SEOO_REDIRECT_INACTIVE_CATEGORY'));
        \Configuration::updateValue('SEOO_REDIRECT_DELETED_CATEGORY', (int) \Tools::getValue('SEOO_REDIRECT_DELETED_CATEGORY'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
