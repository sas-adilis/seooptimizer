<?php

namespace Adilis\SeoOptimizer\Actions;

class CanonicalUrlGenerate
{
    const PAGINATION_PARAMS = ['p', 'page'];

    /**
     * @throws \PrestaShopException
     */
    public function run()
    {
        if (\Configuration::get('SEOO_ENABLE_CANONICAL_URLS')) {
            $context = \Context::getContext();
            $controller = \Dispatcher::getInstance()->getController();
            $page = $context->smarty->getTemplateVars('page');
            $languages = \Language::getLanguages();
            $page['alternate'] = [];

            switch ($controller) {
                case 'product':
                    if ($id_product = (int) \Tools::getValue('id_product')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams($context->link->getProductLink($id_product), []);
                            }
                            if (count($languages) >= 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getProductLink($id_product, null, null, null, $lang['id_lang']),
                                    []
                                );
                            }
                        }
                    }
                    break;

                case 'manufacturer':
                    if ($id_manufacturer = (int) \Tools::getValue('id_manufacturer')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams(
                                    $context->link->getManufacturerLink($id_manufacturer),
                                    self::PAGINATION_PARAMS
                                );
                            }
                            if (count($languages) > 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getManufacturerLink($id_manufacturer, null, $lang['id_lang']),
                                    self::PAGINATION_PARAMS
                                );
                            }
                        }
                    }
                    break;

                case 'category':
                    if ($id_category = (int) \Tools::getValue('id_category')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams(
                                    $context->link->getCategoryLink($id_category),
                                    self::PAGINATION_PARAMS
                                );
                            }
                            if (count($languages) > 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getCategoryLink($id_category, null, $lang['id_lang']),
                                    self::PAGINATION_PARAMS
                                );
                            }
                        }
                    }
                    break;

                case 'cms':
                    if ($id_cms = (int) \Tools::getValue('id_cms')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams($context->link->getCMSLink($id_cms), []);
                            }
                            if (count($languages) > 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getCMSLink($id_cms, null, null, $lang['id_lang']),
                                    []
                                );
                            }
                        }
                    } elseif ($id_cms_category = (int) \Tools::getValue('id_cms_category')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams($context->link->getCMSCategoryLink($id_cms_category), []);
                            }
                            if (count($languages) > 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getCMSCategoryLink($id_cms_category, null, $lang['id_lang']),
                                    []
                                );
                            }
                        }
                    }
                    break;

                case 'supplier':
                    if ($id_supplier = (int) \Tools::getValue('id_supplier')) {
                        foreach ($languages as $lang) {
                            if ($lang['id_lang'] == $context->language->id) {
                                $page['canonical'] = self::getUrlWithParams(
                                    $context->link->getSupplierLink($id_supplier),
                                    self::PAGINATION_PARAMS
                                );
                            }
                            if (count($languages) > 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                                $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                    $context->link->getSupplierLink($id_supplier, null, $lang['id_lang']),
                                    self::PAGINATION_PARAMS
                                );
                            }
                        }
                    }
                    break;

                default:
                    foreach ($languages as $lang) {
                        if ($lang['id_lang'] == $context->language->id) {
                            $page['canonical'] = self::getUrlWithParams($context->link->getPageLink($controller), []);
                        }
                        if (count($languages) >= 1 && \Configuration::get('SEOO_ENABLE_ALTERNATE_URLS')) {
                            $page['alternate'][$lang['language_code']] = self::getUrlWithParams(
                                $context->link->getPageLink($controller, null, $lang['id_lang']),
                                []
                            );
                        }
                    }
            }

            if (\Tools::getIsset('fc') && \Tools::getIsset('module') && \Tools::getIsset('controller')) {
                $path = parse_url($_SERVER['REQUEST_URI'])['path'];
                $page['canonical'] = self::getUrlWithParams(
                    $context->link->getBaseLink() . ltrim($path, '/'),
                    self::PAGINATION_PARAMS
                );
            }

            if (empty($page['canonical'])) {
                $page['canonical'] = self::getUrlWithParams(
                    $context->link->getBaseLink() . ltrim($path, '/'),
                    []
                );
            }

            if (!empty($page['canonical']) && \Configuration::get('SEOO_CANONICAL_URLS_HTTP_HEADER')) {
                header('Link: <' . $page['canonical'] . '>; rel="canonical"');
            }

            $context->smarty->assign('page', $page);
        }
    }

    private function getUrlWithParams(string $url, $params_allowed_only = null): string
    {
        parse_str($_SERVER['QUERY_STRING'], $params);

        if (is_array($params_allowed_only)) {
            $params = array_filter($params, function ($key) use ($params_allowed_only) {
                return in_array($key, $params_allowed_only);
            }, ARRAY_FILTER_USE_KEY);
        }

        $params = array_filter($params, function ($value) {
            return !empty($value);
        });

        $excluded_params = explode(',', \Configuration::get('SEOO_CANONICAL_URLS_IGNORE_PARAMS'));
        $params = array_filter($params, function ($key) use ($excluded_params) {
            return !in_array($key, $excluded_params);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($params as $key => $value) {
            if (in_array($key, self::PAGINATION_PARAMS) && (int) $value === 1) {
                unset($params[$key]);
            }
        }

        if (empty($params)) {
            return $url;
        }

        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= http_build_query($params);

        return $url;
    }
}
