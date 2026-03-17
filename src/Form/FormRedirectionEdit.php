<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Utils;

class FormRedirectionEdit extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        $fields_value = [
            'id_seooptimizer_redirect' => '',
            'redirect_from' => \Tools::getValue('redirect_form', ''),
            'redirect_to' => \Tools::getValue('redirect_to', ''),
            'redirect_type' => \Tools::getValue('redirect_type', '301'),
        ];

        if (
            \Tools::getIsset('updatedata_list_redirections')
            && ($id_seooptimizer_redirect = (int) \Tools::getValue('id_seooptimizer_redirect'))
        ) {
            $redirect = \Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect WHERE id_seooptimizer_redirect = ' . (int) $id_seooptimizer_redirect);
            $fields_value = [
                'id_seooptimizer_redirect' => $id_seooptimizer_redirect,
                'redirect_from' => \Tools::getValue('redirect_form', $redirect['redirect_from']),
                'redirect_to' => \Tools::getValue('redirect_to', $redirect['redirect_to']),
                'redirect_type' => \Tools::getValue('redirect_type', $redirect['redirect_type']),
            ];
            \Context::getContext()->smarty->assign('show_' . $this->getKey(true), true);
        } elseif (
            \Tools::getIsset('create_redirection_from_404')
            && ($id_seooptimizer_log_404 = (int) \Tools::getValue('create_redirection_from_404'))
        ) {
            $log_404 = \Db::getInstance()->getRow('SELECT url FROM ' . _DB_PREFIX_ . 'seooptimizer_log_404 WHERE id_seooptimizer_log_404 = ' . (int) $id_seooptimizer_log_404);
            $fields_value = [
                'id_seooptimizer_redirect' => '',
                'redirect_from' => $log_404['url'],
                'redirect_to' => '',
                'redirect_type' => 301,
            ];
            \Context::getContext()->smarty->assign('show_' . $this->getKey(true), true);
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Add/Edit redirection'),
                    'icon' => 'icon-plus-sign-alt',
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_seooptimizer_redirect',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Redirect from'),
                        'name' => 'redirect_from',
                        'required' => true,
                        'prefix' => rtrim(\Context::getContext()->shop->getBaseURL(true), '/'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Redirect to'),
                        'name' => 'redirect_to',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Redirect type'),
                        'name' => 'redirect_type',
                        'required' => true,
                        'options' => [
                            'query' => [
                                ['id' => Constants::HTTP_CODE_301, 'name' => Constants::HTTP_CODE_301],
                                ['id' => Constants::HTTP_CODE_302, 'name' => Constants::HTTP_CODE_302],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
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
        $id_seooptimizer_redirect = \Tools::getValue('id_seooptimizer_redirect');
        $redirect_from = \Tools::getValue('redirect_from');
        $redirect_to = \Tools::getValue('redirect_to');
        $redirect_type = \Tools::getValue('redirect_type');
        $context = \Context::getContext();

        if (substr($redirect_from, 0, 1) !== '/') {
            $redirect_from = '/' . $redirect_from;
        }

        if (empty($redirect_from)) {
            $context->controller->errors[] = $this->l('Invalid redirect from URL');
        } else {
            $redirect_from_full = \Context::getContext()->shop->getBaseURL(true) . $redirect_from;
            if (!\Validate::isUrl($redirect_from_full)) {
                $context->controller->errors[] = $this->l('Invalid redirect from URL');
            }
        }

        if (empty($redirect_to)) {
            $context->controller->errors[] = $this->l('Invalid redirect to URL');
        } else {
            if (!\Validate::isUrl($redirect_to)) {
                $context->controller->errors[] = $this->l('Invalid redirect to URL');
            }
        }

        if (!in_array((int) $redirect_type, [Constants::HTTP_CODE_301, Constants::HTTP_CODE_302])) {
            $context->controller->errors[] = $this->l('Invalid redirect type');
        }

        if (!$context->controller->errors) {
            if ((int) $id_seooptimizer_redirect) {
                \Db::getInstance()->update('seooptimizer_redirect', [
                    'redirect_from' => pSQL($redirect_from),
                    'redirect_to' => pSQL($redirect_to),
                    'redirect_type' => pSQL($redirect_type),
                    'date_upd' => date('Y-m-d H:i:s'),
                ], 'id_seooptimizer_redirect = ' . (int) $id_seooptimizer_redirect, 1);
            } else {
                \Db::getInstance()->insert('seooptimizer_redirect', [
                    'redirect_from' => pSQL($redirect_from),
                    'redirect_to' => pSQL($redirect_to),
                    'redirect_type' => pSQL($redirect_type),
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]);
            }
            \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
        } else {
            \Context::getContext()->smarty->assign('show_' . $this->getKey(true), true);
        }
    }
}
