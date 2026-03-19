<?php

namespace Adilis\SeoOptimizer\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Content\Form\FormSocialConfiguration;
use Adilis\SeoOptimizer\Utils;
use PrestaShop\PrestaShop\Adapter\Validate;

class RichSnippetsGenerate
{
    private $snippets = [];

    /**
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function run()
    {
        if (!\Configuration::get('SEOO_ENABLE_RS')) {
            return;
        }

        $context = \Context::getContext();
        $controller = \Dispatcher::getInstance()->getController();

        if (
            !in_array($controller, ['index', 'product', 'stores'])
            && !$context->smarty->getTemplateVars('listing')
        ) {
            return;
        }

        $this->generateOrganisationSnippet();
        if (\Configuration::get('SEOO_ENABLE_RS_WEBSITE')) {
            $this->generateWebSiteSnippet();
        }
        if (\Configuration::get('SEOO_ENABLE_RS_WEBPAGE')) {
            $this->generateWebPageSnippet();
        }
        if (\Configuration::get('SEOO_ENABLE_RS_BREADCRUMBS')) {
            $this->generateBreadcrumbsSnippet();
        }

        $this->generateProductsItemListSnippet();

        if (\Dispatcher::getInstance()->getController() === 'product') {
            if (\Configuration::get('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY')) {
                $this->generateMerchantReturnPolicySnippet();
            }
            $this->generateProductSnippet();
        }

        if (
            \Dispatcher::getInstance()->getController() === 'stores'
            && \Configuration::get('SEOO_ENABLE_RS_STORE')
        ) {
            $this->generateStoresSnippet();
        }

        if (count($this->snippets) > 0) {
            $this->cleanSnippet($this->snippets);

            foreach ($this->snippets as &$snippet) {
                $snippet = json_encode($snippet, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }

            $context->smarty->assign('rich_snippets', $this->snippets);
            return $context->smarty->fetch('module:seooptimizer/views/templates/hook/rich_snippets.tpl');
        }
    }

    private function generateBreadcrumbsSnippet()
    {
        $context = \Context::getContext();
        $breadcrumb = $context->smarty->getTemplateVars('breadcrumb');

        if ($breadcrumb) {
            $root_shop_category = \Category::getRootCategory($context->language->id);
            $root_shop_category_link = $context->link->getCategoryLink($root_shop_category);

            array_filter($breadcrumb['links'], function ($item) use ($root_shop_category_link) {
                return $item['url'] !== $root_shop_category_link;
            });

            $this->snippets[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'name' => end($breadcrumb['links'])['title'],
                'itemListElement' => array_map(function ($item, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => (int) $index + 1,
                        'name' => $item['title'],
                        'item' => $item['url'],
                    ];
                }, $breadcrumb['links'], array_keys($breadcrumb['links'])),
            ];
        }
    }

    private function generateWebPageSnippet()
    {
        $context = \Context::getContext();
        $shopUrl = $context->shop->getBaseURL();
        $page = $context->smarty->getTemplateVars('page');

        if ($page) {
            $snippet = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                '@id' => $shopUrl . '#WebPage',
                'url' => $page['canonical'],
                'name' => $page['meta']['title'],
            ];

            if (\Configuration::get('SEOO_ENABLE_RS_WEBSITE')) {
                $snippet['isPartOf'] = [
                    '@id' => $shopUrl . '#WebSite',
                ];
            } else {
                $snippet['isPartOf'] = [
                    '@type' => 'WebSite',
                    'url' => $shopUrl,
                    'name' => \Configuration::get('PS_SHOP_NAME'),
                    'publisher' => [
                        '@id' => $shopUrl . '#Organization',
                    ],
                ];
            }

            $this->snippets[] = $snippet;
        }
    }

    private function generateWebSiteSnippet()
    {
        $context = \Context::getContext();
        $shopUrl = $context->shop->getBaseURL();
        $searchPageUrl = $context->link->getPageLink('search');

        $this->snippets[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $shopUrl . '#WebSite',
            'url' => $shopUrl,
            'name' => \Configuration::get('PS_SHOP_NAME'),
            'description' => '', // todo: get HP description ?
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $searchPageUrl . '?search_query={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
            'publisher' => [
                '@id' => $shopUrl . '#Organization',
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    private function generateOrganisationSnippet()
    {
        $context = \Context::getContext();
        $shopUrl = $context->shop->getBaseURL();

        $snippet = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $shopUrl . '#Organization',
            'name' => \Configuration::get('PS_SHOP_NAME'),
            'email' => \Configuration::get('PS_SHOP_EMAIL'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => \Configuration::get('PS_SHOP_ADDR1'),
                'addressLocality' => \Configuration::get('PS_SHOP_CITY'),
                'postalCode' => \Configuration::get('PS_SHOP_CODE'),
                'addressCountry' => \Country::getIsoById(\Configuration::get('PS_SHOP_COUNTRY_ID')),
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'telephone' => \Configuration::get('PS_SHOP_PHONE'),
                    'contactType' => $this->l('Customer service'),
                    'availableLanguage' => array_map(function ($lang) {
                        return $lang['name'];
                    }, \Language::getLanguages()),
                ],
            ],
        ];

        if (\Configuration::hasKey('PS_LOGO')) {
            $logoUrl = rtrim($shopUrl, '/') . _PS_IMG_ . \Configuration::get('PS_LOGO');
            $snippet['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ];
        }

        $snippet['sameAs'] = [];
        foreach (array_keys(FormSocialConfiguration::getSocialNetworks()) as $key) {
            $configurationValue = \Configuration::get('SEOO_' . $key . '_ADDRESS', $context->language->id);
            if ($configurationValue) {
                $snippet['sameAs'][] = \Configuration::get('SEOO_' . $key . '_ADDRESS', $context->language->id);
            }
        }
        $this->snippets[] = $snippet;
    }

    private function l(string $string): string
    {
        return \Translate::getModuleTranslation('seooptimizer', $string, 'RichSnippetsGenerate');
    }

    private function generateProductsItemListSnippet()
    {
        $context = \Context::getContext();
        $listing = $context->smarty->getTemplateVars('listing');

        if (!$listing) {
            return;
        }

        $page_name = $listing['label'];
        if (isset($listing['pagination']) && $listing['pagination']['pages_count'] > 1) {
            $page_name .= sprintf($this->l(' - page %s '), $listing['pagination']['current_page']);
        }

        $snippet = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $page_name,
            'numberOfItems' => $listing['pagination']['total_items'],
            'itemListElement' => [],
        ];

        foreach ($listing['products'] as $index => $product) {
            $itemListElement = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'mainEntityOfPage' => $product['url'],
                'url' => $product['url'],
                'name' => $product['name'],
                'image' => $product['cover']['bySize']['home_default']['url'],
                'identifier' => $this->getIdentifier($product),
            ];

            $snippet['itemListElement'][] = $itemListElement;
        }

        $this->snippets[] = $snippet;
    }

    private function getIdentifier($product)
    {
        return $product['ean13'] ?: $product['upc'] ?: $product['reference'];
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    private function generateProductSnippet()
    {
        $context = \Context::getContext();
        $product = $context->smarty->getTemplateVars('product');
        $page = $context->smarty->getTemplateVars('page');
        $shopUrl = $context->shop->getBaseURL();

        $isInStock = $product['quantity_all_versions'] > 0 || \Product::isAvailableWhenOutOfStock($product['out_of_stock']);

        $productSnippet = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'ProductId' => $product['id_product'],
            'url' => $product['url'],
            'name' => $product['name'],
            'description' => $page['meta']['description'],
            'category' => $product['category_name'],
            'image' => array_map(function ($image) {
                return $image['bySize']['home_default']['url'];
            }, $product['images']),
            'identifier' => $product['ean13'] ?: $product['upc'] ?: $product['reference'],
            'sku' => $product['reference'],
            'mpn' => $product['mpn'],
            'gtin13' => $product['ean13'],
        ];

        if ($product['product_type'] === 'combinations') {
            $combinations = $this->getCombinations($product['id_product']);

            $productSnippet['offers'] = [
                '@type' => 'AggregateOffer',
                'priceCurrency' => $context->currency->iso_code,
                'offerCount' => count($combinations),
                'lowPrice' => min(array_column($combinations, 'price')),
                'highPrice' => max(array_column($combinations, 'price')),
                'offers' => [],
            ];

            foreach ($combinations as $combination) {
                $combination_images = array_values(array_filter($product['images'], function ($image) use ($combination) {
                    return in_array($image['id_image'], $combination['ids_image']);
                }));
                $isCombinationInStock = $combination['quantity'] > 0 || \Product::isAvailableWhenOutOfStock($product['out_of_stock']);

                $offer = [
                    '@type' => 'Offer',
                    'price' => $combination['price'],
                    'name' => $product['name'] . ' - ' . $combination['attributes'],
                    'priceCurrency' => $context->currency->iso_code,
                    'availability' => $isCombinationInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => $this->getItemCondition($product['condition']['type']),
                    'sku' => $combination['reference'],
                    'mpn' => $combination['mpn'],
                    'gtin13' => $combination['ean13'],
                    'url' => $context->link->getProductLink($product['id_product'], null, null, null, null, null, $combination['id_product_attribute']),
                    'image' => array_map(function ($image) {
                        return $image['bySize']['home_default']['url'];
                    }, $combination_images),
                ];

                if ($combination['specific_price']['reduction'] > 0) {
                    if (Validate::isDate($combination['specific_price']['to'])) {
                        $offer['priceValidUntil'] = Utils::getDatePart($combination['specific_price']['to']);
                    } else {
                        $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
                    }
                } else {
                    $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
                }

                if (\Configuration::get('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY')) {
                    $offer['hasMerchantReturnPolicy'] = [
                        '@id' => $shopUrl . '#MerchantReturnPolicy',
                    ];
                }

                $productSnippet['offers']['offers'][] = $offer;
            }
        } else {
            $offer = [
                '@type' => 'Offer',
                'price' => $product['price_amount'],
                'priceCurrency' => $context->currency->iso_code,
                'availability' => $isInStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => $this->getItemCondition($product['condition']['type']),
            ];

            // todo: check if this is correct
            if ($product['specific_price']['reduction'] > 0) {
                if (Validate::isDate($product['specific_price']['to'])) {
                    $offer['priceValidUntil'] = Utils::getDatePart($product['specific_price']['to']);
                } else {
                    $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
                }
            } else {
                $offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
            }

            if (\Configuration::get('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY')) {
                $offer['hasMerchantReturnPolicy'] = [
                    '@id' => $shopUrl . '#MerchantReturnPolicy',
                ];
            }

            $productSnippet['offers'] = $offer;
        }

        if ($product['id_manufacturer']) {
            $id_manufacturer = $product['id_manufacturer'];
            $manufacturer = \Manufacturer::getNameById($id_manufacturer);
            $productSnippet['brand'] = [
                '@type' => 'Brand',
                'name' => $manufacturer,
                'url' => $context->link->getManufacturerLink($id_manufacturer),
            ];
            if (file_exists(_PS_MANU_IMG_DIR_ . $id_manufacturer . '.jpg')) {
                $productSnippet['brand']['logo'] = $context->link->getManufacturerImageLink($id_manufacturer, 'small_default');
            }
        }

        if (\Configuration::get('SEOO_ENABLE_RS_AGGREGATE_RATING')) {
        }

        if (\Configuration::get('SEOO_ENABLE_RS_ADDITIONAL_PROPERTY')) {
            $productSnippet['additionalProperty'] = [];
            $features_allowed = \Configuration::get('SEOO_ENABLE_RS_FEATURES');
            if (!empty($features_allowed)) {
                $features_allowed = explode(',', $features_allowed);
            }
            foreach ($product['features'] as $property) {
                if (
                    empty($features_allowed)
                    || in_array($property['id_feature'], $features_allowed)
                ) {
                    $productSnippet['additionalProperty'][] = [
                        '@type' => 'PropertyValue',
                        'name' => $property['name'],
                        'value' => $property['value'],
                    ];
                }
            }
        }

        $this->snippets[] = $productSnippet;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    private function getCombinations($id_product)
    {
        $context = \Context::getContext();

        $query = new \DbQuery();
        $query->select('pa.id_product_attribute, pa.reference, pa.ean13, pa.upc, pa.mpn');
        $query->from('product_attribute', 'pa');
        $query->innerJoin('product_attribute_shop', 'pas', 'pa.id_product_attribute = pas.id_product_attribute AND pas.id_shop = ' . (int) $context->shop->id);
        $query->innerJoin('product_attribute_combination', 'pac', 'pa.id_product_attribute = pac.id_product_attribute');
        $query->innerJoin('product_attribute_image', 'pai', 'pa.id_product_attribute = pai.id_product_attribute');
        $query->where('pa.id_product = ' . (int) $id_product);
        $query->groupBy('pa.id_product_attribute');

        $subquery = new \DbQuery();
        $subquery->select('GROUP_CONCAT(pai.id_image) as ids_image');
        $subquery->from('product_attribute_image', 'pai');
        $subquery->where('pai.id_product_attribute = pa.id_product_attribute');
        $subquery->groupBy('pai.id_product_attribute');
        $query->select('(' . $subquery->build() . ') as ids_image');

        $subquery = new \DbQuery();
        $subquery->select('GROUP_CONCAT(CONCAT(agl.name, " : ", al.name)) as attributes');
        $subquery->from('product_attribute_combination', 'pac');
        $subquery->innerJoin('attribute', 'a', 'a.id_attribute = pac.id_attribute');
        $subquery->innerJoin('attribute_lang', 'al', 'a.id_attribute = al.id_attribute AND al.id_lang = ' . (int) $context->language->id);
        $subquery->innerJoin('attribute_group_lang', 'agl', 'a.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int) $context->language->id);
        $subquery->where('pac.id_product_attribute = pa.id_product_attribute');
        $subquery->groupBy('pac.id_product_attribute');
        $query->select('(' . $subquery->build() . ') as attributes');

        $combinations = \Db::getInstance()->executeS($query);
        foreach ($combinations as &$combination) {
            $combination['ids_image'] = explode(',', $combination['ids_image']);
            $combination['quantity'] = \StockAvailable::getQuantityAvailableByProduct($id_product, $combination['id_product_attribute']);
            $combination['price_without_reduction'] = \ProductCore::getPriceStatic(
                $id_product,
                true,
                $combination['id_product_attribute'],
                2,
                null,
                false,
                false
            );
            $combination['price'] = \ProductCore::getPriceStatic(
                $id_product,
                true,
                $combination['id_product_attribute'],
                2,
                null,
                false,
                true,
                1,
                false,
                null,
                null,
                null,
                $specific_price
            );
            $combination['specific_price'] = $specific_price;
        }

        return $combinations;
    }

    public function cleanSnippet(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->cleanSnippet($value);
                if (empty($value)) {
                    unset($array[$key]);
                }
            } elseif (empty($value) && !is_numeric($value)) {
                unset($array[$key]);
            }
        }
        unset($value);
    }

    private function getItemCondition($condition): string
    {
        switch ($condition) {
            case 'used':
                return 'https://schema.org/UsedCondition';
            case 'refurbished':
                return 'https://schema.org/RefurbishedCondition';
            default:
                return 'https://schema.org/NewCondition';
        }
    }

    private function generateMerchantReturnPolicySnippet()
    {
        if (\Configuration::get('SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY')) {
            $context = \Context::getContext();
            $shopUrl = $context->shop->getBaseURL();

            $merchantReturnPolicy = [
                '@context' => 'https://schema.org',
                '@type' => 'MerchantReturnPolicy',
                '@id' => $shopUrl . '#MerchantReturnPolicy',
                'name' => $this->l('Return Policy'),
                'url' => $shopUrl . '/content/9-return-policy',
                'merchantReturnDays' => \Configuration::get('SEOO_RS_MERCHANT_RETURN_DAYS'),
                'returnFees' => \Configuration::get('SEOO_RS_MERCHANT_RETURN_FEES'),
            ];

            if (\Configuration::get('SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY')) {
                $merchantReturnPolicy['returnPolicyCategory'] = sprintf(
                    'https://schema.org/%s',
                    \Configuration::get('SEOO_RS_MERCHANT_RETURN_POLICY_CATEGORY')
                );
            }

            if (\Configuration::get('SEOO_RS_MERCHANT_RETURN_METHOD')) {
                $merchantReturnPolicy['returnMethod'] = sprintf(
                    'https://schema.org/%s',
                    \Configuration::get('SEOO_RS_MERCHANT_RETURN_METHOD')
                );
            }

            $this->snippets[] = $merchantReturnPolicy;
        }
    }

    /**
     * @throws \Exception
     */
    private function generateStoresSnippet()
    {
        $context = \Context::getContext();
        $stores = $context->smarty->getTemplateVars('stores');

        foreach ($stores as $store) {
            $storeSnippet = [
                '@context' => 'https://schema.org',
                '@type' => 'Store',
                'name' => $store['name'],
                'image' => $store['image']['bySize']['stores_default']['url'],
                'telephone' => $store['phone'],
                'email' => $store['email'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $store['address']['address1'],
                    'addressLocality' => $store['address']['city'],
                    'postalCode' => $store['address']['postcode'],
                    'addressCountry' => \Country::getIsoById($store['address']['id_country']),
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $store['latitude'],
                    'longitude' => $store['longitude'],
                ],
                'openingHoursSpecification' => [],
            ];

            foreach ($store['business_hours'] as $day_number => $hour) {
                if (strpos($hour['hours'][0], '-') === false) {
                    continue;
                }
                list($open, $close) = explode('-', $hour['hours'][0]);
                $open = str_replace('h', ':', $open);
                $close = str_replace('h', ':', $close);
                $storeSnippet['openingHoursSpecification'][] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $this->getDayOfWeek($day_number),
                    'opens' => trim($open),
                    'closes' => trim($close),
                ];
            }

            $this->snippets[] = $storeSnippet;
        }
    }

    /**
     * @throws \Exception
     */
    private function getDayOfWeek(int $day_number): string
    {
        switch ($day_number) {
            case 0: return 'https://schema.org/Monday';
            case 1: return 'https://schema.org/Tuesday';
            case 2: return 'https://schema.org/Wednesday';
            case 3: return 'https://schema.org/Thursday';
            case 4: return 'https://schema.org/Friday';
            case 5: return 'https://schema.org/Saturday';
            case 6: return 'https://schema.org/Sunday';
        }
        throw new \Exception('Invalid day number');
    }
}
