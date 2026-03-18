<?php

namespace Adilis\SeoOptimizer\Form;

use Adilis\SeoOptimizer\TranslateHelper;
use Adilis\SeoOptimizer\Utils;

class FormSocial extends FormAbstract implements FormInterface
{
    /**
     * @throws \Exception
     */
    public static function getSocialNetworks(): array
    {
        return [
            'INSTAGRAM' => TranslateHelper::get()->l('Instagram'),
            'FACEBOOK' => TranslateHelper::get()->l('Facebook'),
            'TWITTER' => TranslateHelper::get()->l('X (Twitter)'),
            'PINTEREST' => TranslateHelper::get()->l('Pinterest'),
            'YOUTUBE' => TranslateHelper::get()->l('Youtube'),
            'LINKEDIN' => TranslateHelper::get()->l('LinkedIn'),
            'TIKTOK' => TranslateHelper::get()->l('TikTok'),
        ];
    }

    /**
     * @throws \Exception
     */
    public function getContent(): string
    {
        $inputs = $fields_value = [];
        foreach (self::getSocialNetworks() as $key => $network) {
            $inputs[] = [
                'type' => 'text',
                'name' => 'SEOO_' . $key . '_ADDRESS',
                'label' => sprintf(
                    $this->l('%s address'),
                    $network
                ),
                'required' => true,
                'lang' => true,
            ];

            /*$fields_value['SEOO_' . $key . '_ADDRESS'] = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $fields_value['SEOO_' . $key . '_ADDRESS'][(int) $lang['id_lang']] = Utils::getValOrConf(
                    'SEOO_' . $key . '_ADDRESS',
                    (int) $lang['id_lang']
                );
            }*/

            $fields_value['SEOO_' . $key . '_ADDRESS'] = Utils::getValOrConf('SEOO_' . $key . '_ADDRESS', true);
        }

        return $this->renderForm([
            'form' => [
                'legend' => [
                    'title' => $this->l('Social addresses'),
                    'icon' => 'icon-cloud',
                    'description' => $this->l('Configure your social network URLs. These are used for rich snippets (structured data) and social metadata to improve your visibility on social platforms.'),
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit' . $this->getKey(),
                ],
            ],
        ], $fields_value);
    }

    /**
     * @throws \Exception
     */
    public function postProcess()
    {
        $context = \Context::getContext();
        foreach (\Language::getLanguages(false) as $lang) {
            foreach (self::getSocialNetworks() as $key => $network) {
                $url = \Tools::getValue('SEOO_' . $key . '_ADDRESS_' . (int) $lang['id_lang']);
                if (!empty($url) && !\Validate::isAbsoluteUrl($url)) {
                    $context->controller->errors[] = sprintf(
                        $this->l('Invalid URL for %s in %s'),
                        $network,
                        $lang['name']
                    );
                }
            }
        }

        if (!empty($context->controller->errors)) {
            return;
        }

        foreach (self::getSocialNetworks() as $key => $network) {
            $configuration_value = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $configuration_value[(int) $lang['id_lang']] = \Tools::getValue('SEOO_' . $key . '_ADDRESS_' . (int) $lang['id_lang']);
            }
            \Configuration::updateValue('SEOO_' . $key . '_ADDRESS', $configuration_value);
        }

        \Tools::redirectAdmin(Utils::getConfigFormUrl(4));
    }
}
