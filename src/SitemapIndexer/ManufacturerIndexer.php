<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

use Adilis\SeoOptimizer\Constants;

class ManufacturerIndexer implements IndexerInterface
{

    public static function getType(): string
    {
        return 'manufacturer';
    }

    public static function getPages(int $page_id = null): array
    {
        if ((int)\Configuration::get('SEOO_MANUFACTURER_PAGE_INDEXATION') !== Constants::PAGE_INDEXATION_DO_NOTHING) {
            return [];
        }

        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('m.id_manufacturer, m.name, ml.meta_title');
        $query->select('IF(m.date_upd > m.date_upd, m.date_upd, m.date_upd) as date_upd');
        $query->from('manufacturer', 'm');
        $query->leftJoin('manufacturer_lang', 'ml', 'm.id_manufacturer = ml.id_manufacturer AND ml.id_lang = ' . $context->language->id);
        $query->innerJoin('manufacturer_shop', 'ms', 'm.id_manufacturer = ms.id_manufacturer AND ms.id_shop = ' . $context->shop->id);
        $query->where('m.active = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $manufacturers = \Db::getInstance()->executeS($query);

        foreach ($manufacturers as $manufacturer) {
            $links[] = [
                'url' => $context->link->getManufacturerLink($manufacturer, \Tools::str2url($manufacturer['name'])),
                'date_updated' => date('Y-m-d H:i:s'),
                'frequency' => \Configuration::get('SEOO_SITEMAP_MANUFACTURER_FREQUENCY'),
                'priority' => \Configuration::get('SEOO_SITEMAP_MANUFACTURER_PRIORITY'),
                'images' => self::getManufacturerImages($manufacturer),
            ];
        }

        return $links;
    }

    public static function getCount(): int
    {
        if ((int)\Configuration::get('SEOO_MANUFACTURER_PAGE_INDEXATION') !== Constants::PAGE_INDEXATION_DO_NOTHING) {
            return 0;
        }

        $context = \Context::getContext();
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('manufacturer', 'm');
        $query->innerJoin('manufacturer_shop', 'ms', 'm.id_manufacturer = ms.id_manufacturer AND ms.id_shop = ' . $context->shop->id);
        $query->where('m.active = 1');

        return (int)\Db::getInstance()->getValue($query);
    }

    private static function getManufacturerImages(array $manufacturer): array
    {
        if (!\Configuration::get('SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES')) {
            return [];
        }

        if (!file_exists(_PS_MANU_IMG_DIR_ .(int)$manufacturer['id_manufacturer'] . '.jpg')) {
            return [];
        }

        $image_link = \Context::getContext()->link->getManufacturerImageLink(
            $manufacturer['id_manufacturer'],
            \Configuration::get('SEOO_SITEMAP_MANUFACTURER_IMAGE_FORMAT')
        );

        return [
            [
                'url' => $image_link,
                'name' => $manufacturer['name'],
                'caption' => $manufacturer['meta_title'],
            ],
        ];
    }
}