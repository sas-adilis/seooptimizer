<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Utils;

class FormIndexationRule extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        if (
            \Tools::getIsset('updatedata_list_indexation_rules')
            && ($id_seooptimizer_indexation_rule = (int) \Tools::getValue('id_seooptimizer_indexation_rule'))
        ) {
            $rule = \Db::getInstance()->getRow('
                SELECT *
                FROM ' . _DB_PREFIX_ . 'seooptimizer_indexation_rule
                WHERE id_seooptimizer_indexation_rule = ' . (int) $id_seooptimizer_indexation_rule
            );

            $fields_value = [
                'id_seooptimizer_indexation_rule' => $id_seooptimizer_indexation_rule,
                'type' => \Tools::getValue('type', $rule['type']),
                'term' => \Tools::getValue('term', $rule['type'] === Constants::RULE_TYPE_IS ? '' : $rule['term']),
                'url' => \Tools::getValue('url', $rule['type'] !== Constants::RULE_TYPE_IS ? '' : $rule['term']),
            ];
            \Context::getContext()->smarty->assign('show_' . $this->getKey(true), true);
        } else {
            $fields_value = [
                'id_seooptimizer_indexation_rule' => '',
                'type' => \Tools::getValue('type', ''),
                'term' => \Tools::getValue('term', ''),
                'url' => \Tools::getValue('url', ''),
            ];
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Add/Edit indexation rule'),
                    'icon' => 'icon-plus-sign-alt',
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_seooptimizer_indexation_rule',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'type',
                        'required' => true,
                        'label' => $this->l('Rule type'),
                        'options' => [
                            'default' => [
                                'value' => null,
                                'label' => $this->l('Pick an option'),
                            ],
                            'query' => [
                                ['id' => Constants::RULE_TYPE_IS, 'label' => $this->l('URL is')],
                                ['id' => Constants::RULE_TYPE_CONTAINS, 'label' => $this->l('URL contains')],
                                ['id' => Constants::RULE_TYPE_STARTS_WITH, 'label' => $this->l('URL start with')],
                            ],
                            'id' => 'id',
                            'name' => 'label',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'name' => 'term',
                        'form_group_class' => 'show-if-not-is',
                        'label' => $this->l('Term'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'url',
                        'form_group_class' => 'show-if-is',
                        'label' => $this->l('URL'),
                        'required' => true,
                        'prefix' => rtrim(\Context::getContext()->shop->getBaseURL(true), '/'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], $fields_value);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        $id_seooptimizer_indexation_rule = \Tools::getValue('id_seooptimizer_indexation_rule');
        $context = \Context::getContext();
        $type = \Tools::getValue('type');
        $term = \Tools::getValue('term');
        $url = \Tools::getValue('url');

        if (!in_array($type, [Constants::RULE_TYPE_IS, Constants::RULE_TYPE_CONTAINS, Constants::RULE_TYPE_STARTS_WITH])) {
            $context->controller->errors[] = $this->l('Please select a valid type');
        }

        if ($type !== Constants::RULE_TYPE_IS && empty($term) || !Utils::isRelativeUrl($term)) {
            $context->controller->errors[] = $this->l('Please provide a term');
        }

        if ($type === Constants::RULE_TYPE_IS && empty($url) || !Utils::isRelativeUrl($url)) {
            $context->controller->errors[] = $this->l('Please provide a URL');
        }

        if (!empty($context->controller->errors)) {
            return;
        }

        if ($type === Constants::RULE_TYPE_IS) {
            $term = $url;
        }

        if ($id_seooptimizer_indexation_rule) {
            \Db::getInstance()->update('seooptimizer_indexation_rule', [
                'type' => pSQL($type),
                'term' => pSQL($term),
                'date_upd' => pSQL(date('Y-m-d H:i:s')),
            ], 'id_seooptimizer_indexation_rule = ' . (int) $id_seooptimizer_indexation_rule);
        } else {
            \Db::getInstance()->insert('seooptimizer_indexation_rule', [
                'type' => pSQL($type),
                'term' => pSQL($term),
                'date_add' => pSQL(date('Y-m-d H:i:s')),
                'date_upd' => pSQL(date('Y-m-d H:i:s')),
                'id_shop' => (int) \Context::getContext()->shop->id,
            ]);
        }
    }
}
