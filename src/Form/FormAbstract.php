<?php

namespace Adilis\SeoOptimizer\Form;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\CacheManager;
use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Utils;

abstract class FormAbstract implements FormInterface
{
    public function getKey($to_underscore_case = false): string
    {
        $class_name = (new \ReflectionClass($this))->getShortName();
        if ($to_underscore_case) {
            return \Tools::toUnderscoreCase($class_name);
        }

        return $class_name;
    }

    public function process()
    {
        if ((int) \Tools::getValue('ajax')) {
            $action = \Tools::toCamelCase(\Tools::getValue('action'));
            if (!empty($action) && method_exists($this, 'ajaxProcess' . $action)) {
                return $this->{'ajaxProcess' . $action}();
            }
        }

        if (\Tools::isSubmit('submit' . $this->getKey())) {
            if (!\Tools::getValue('token') || \Tools::getValue('token') !== \Tools::getAdminTokenLite('AdminModules')) {
                \Context::getContext()->controller->errors[] = 'Invalid security token.';
                return;
            }
            if (method_exists($this, 'postProcess')) {
                $this->postProcess();
            }
        }

        foreach (\Tools::getAllValues() as $key => $value) {
            if (
                strpos($key, 'submit' . $this->getKey()) === 0
                && $key !== 'submit' . $this->getKey()
            ) {
                $action = ucfirst(str_replace('submit' . $this->getKey(), '', $key));
                if (method_exists($this, 'postProcess' . $action)) {
                    $this->{'postProcess' . $action}();
                }
            }
        }

        \Context::getContext()->smarty->assign($this->getKey(true), $this->getContent());
    }

    public function getContent(): string
    {
        return '';
    }

    public function renderForm($form, $fields_value = []): string
    {
        $context = \Context::getContext();

        $helper = new \HelperForm();
        $helper->id = $this->getKey();
        $helper->show_toolbar = false;
        $helper->table = $this->getKey();
        $helper->module = \Module::getInstanceByName(Utils::MODULE_NAME);
        $helper->default_form_language = $context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $helper->id;
        $helper->submit_action = 'submitFormModule';
        $helper->currentIndex = $context->link->getAdminLink(
            'AdminModules',
            false,
            [],
            ['configure' => Utils::MODULE_NAME, 'module_name' => Utils::MODULE_NAME]
        );
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'languages' => $context->controller->getLanguages(),
            'id_language' => $context->language->id,
            'fields_value' => $fields_value,
        ];

        return $helper->generateForm([$form]);
    }

    protected function l($string)
    {
        // todo: implement translation
        return $string;
    }
}
