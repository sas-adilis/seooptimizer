<?php

namespace Adilis\SeoOptimizer;

class Utils
{
    const MODULE_NAME = 'seooptimizer';

    public static function getValOrConf(string $key, bool $id_lang = false)
    {
        if ($id_lang) {
            $fields_value = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $value_key = $key . '_' . $lang['id_lang'];
                $fields_value[(int) $lang['id_lang']] = \Tools::getValue($value_key, \Configuration::get($key, $id_lang));
            }

            return $fields_value;
        } else {
            return \Tools::getValue($key, \Configuration::get($key));
        }
    }

    public static function isRelativeUrl(string $url): bool
    {
        return !preg_match('/^(?:[a-z]+:)?\/\//', $url);
    }

    public static function getConfigFormUrl($conf = null): string
    {
        $params = [
            'configure' => self::MODULE_NAME,
            'module_name' => self::MODULE_NAME,
        ];

        if ($conf) {
            $params['conf'] = (int) $conf;
        }

        return \Context::getContext()->link->getAdminLink('AdminModules', true, [], $params);
    }

    public static function getDatePart($to): string
    {
        return substr($to, 0, 10);
    }

    public static function removePsRootFromPath($path): string
    {
       if (strpos($path, _PS_ROOT_DIR_) !== false) {
           return str_replace(_PS_ROOT_DIR_, '', $path);
       }
    }

    public static function saveFormConfiguration(string $form_name, bool $lang = false)
    {
        if ($lang) {
            $configuration_value = [];
            foreach (\Language::getLanguages(false) as $lang) {
                $configuration_value[(int) $lang['id_lang']] = \Tools::getValue($form_name . '_' . (int) $lang['id_lang']);
            }
            \Configuration::updateValue($form_name, $configuration_value);
        } else {
            \Configuration::updateValue($form_name, \Tools::getValue($form_name));
        }
    }

    public static function saveFormIntConfiguration(string $form_name)
    {
        \Configuration::updateValue($form_name, (int) \Tools::getValue($form_name));
    }

    public static function displayTruncableLink(string $string): string
    {
        $parts = str_split($string, 5);
        return implode('&#8203;', $parts);
    }

    public static function getModulePath(): string
    {
        return _PS_MODULE_DIR_ . self::MODULE_NAME . '/';
    }
}
