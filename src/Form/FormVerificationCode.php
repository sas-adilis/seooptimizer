<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\Utils;

class FormVerificationCode extends FormAbstract implements FormInterface
{
    public function getContent(): string
    {
        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Verification code configuration'),
                    'icon' => 'icon-code',
                    'description' => $this->l('Add verification codes from Google, Bing and Pinterest to prove ownership of your website in their webmaster tools.'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'SEOO_CODE_VERIFICATION_GOOGLE',
                        'label' => $this->l('Google Verification Code'),
                        'desc' => $this->l('Enter the Google verification code'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_CODE_VERIFICATION_BING',
                        'label' => $this->l('Bing Verification Code'),
                        'desc' => $this->l('Enter the Bing verification code'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'SEOO_CODE_VERIFICATION_PINTEREST',
                        'label' => $this->l('Bing Verification Pinterest'),
                        'desc' => $this->l('Enter the Pinterest verification code'),
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
            'SEOO_CODE_VERIFICATION_GOOGLE' => Utils::getValOrConf('SEOO_CODE_VERIFICATION_GOOGLE'),
            'SEOO_CODE_VERIFICATION_BING' => Utils::getValOrConf('SEOO_CODE_VERIFICATION_BING'),
            'SEOO_CODE_VERIFICATION_PINTEREST' => Utils::getValOrConf('SEOO_CODE_VERIFICATION_PINTEREST'),
        ]);
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function postProcess()
    {
        \Configuration::updateValue('SEOO_REDIRECT_INACTIVE_PRODUCT', \Tools::getValue('SEOO_REDIRECT_INACTIVE_PRODUCT'));
        \Configuration::updateValue('SEOO_CODE_VERIFICATION_BING', \Tools::getValue('SEOO_CODE_VERIFICATION_BING'));
        \Configuration::updateValue('SEOO_CODE_VERIFICATION_PINTEREST', \Tools::getValue('SEOO_CODE_VERIFICATION_PINTEREST'));
        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
