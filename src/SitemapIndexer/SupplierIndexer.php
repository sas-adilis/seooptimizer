<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Constants;

class SupplierIndexer implements IndexerInterface
{

    public static function getType(): string
    {
        return 'supplier';
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public static function getPages(int $page_id = null): array
    {
        if ((int)\Configuration::get('SEOO_SUPPLIER_PAGE_INDEXATION') !== Constants::PAGE_INDEXATION_DO_NOTHING) {
            return [];
        }

        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('s.id_supplier, s.name, sl.meta_title');
        $query->select('IF(s.date_upd > s.date_upd, s.date_upd, s.date_upd) as date_upd');
        $query->from('supplier', 's');
        $query->leftJoin('supplier_lang', 'sl', 's.id_supplier = sl.id_supplier AND sl.id_lang = ' . $context->language->id);
        $query->innerJoin('supplier_shop', 'ss', 's.id_supplier = ss.id_supplier AND ss.id_shop = ' . $context->shop->id);
        $query->where('s.active = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $suppliers = \Db::getInstance()->executeS($query);

        foreach ($suppliers as $supplier) {
            $links[] = [
                'id_entity' => (int) $supplier['id_supplier'],
                'url' => $context->link->getSupplierLink($supplier, \Tools::str2url($supplier['name'])),
                'date_updated' => date('Y-m-d H:i:s'),
                'frequency' => \Configuration::get('SEOO_SITEMAP_SUPPLIER_FREQUENCY'),
                'priority' => \Configuration::get('SEOO_SITEMAP_SUPPLIER_PRIORITY'),
                'images' => self::getSupplierImages($supplier),
            ];
        }

        return $links;
    }

    public static function getCount(): int
    {
        if ((int)\Configuration::get('SEOO_SUPPLIER_PAGE_INDEXATION') !== Constants::PAGE_INDEXATION_DO_NOTHING) {
            return 0;
        }

        $context = \Context::getContext();
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('supplier', 's');
        $query->innerJoin('supplier_shop', 'ss', 's.id_supplier = ss.id_supplier AND ss.id_shop = ' . $context->shop->id);
        $query->where('s.active = 1');

        return (int)\Db::getInstance()->getValue($query);
    }

    private static function getSupplierImages(array $supplier): array
    {
        if (!\Configuration::get('SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES')) {
            return [];
        }

        if (!file_exists(_PS_SUPP_IMG_DIR_ .(int)$supplier['id_supplier'] . '.jpg')) {
            return [];
        }

        $image_link = \Context::getContext()->link->getSupplierImageLink(
            $supplier['id_supplier'],
            \Configuration::get('SEOO_SITEMAP_SUPPLIER_IMAGE_FORMAT')
        );

        return [
            [
                'url' => $image_link,
                'name' => $supplier['name'],
                'caption' => $supplier['meta_title'],
            ],
        ];
    }
}