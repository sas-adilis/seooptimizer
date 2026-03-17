<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

class ProductIndexer implements IndexerInterface
{

    public static function getType(): string
    {
        return 'product';
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function getPages(int $page_id = null): array
    {
        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('p.id_product, pl.link_rewrite, p.ean13, p.reference');
        $query->select('IF(ps.date_upd > p.date_upd, ps.date_upd, p.date_upd) as date_upd');
        $query->select('cl.link_rewrite as category');
        $query->from('product', 'p');
        $query->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $context->shop->id);
        $query->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND ps.id_shop = ' . $context->shop->id . ' AND pl.id_lang = ' . $context->language->id);
        $query->leftJoin('category_lang', 'cl', 'ps.id_category_default = cl.id_category AND cl.id_lang = ' . $context->language->id);
        $query->where('ps.active = 1');
        $query->where('p.visibility IN ("both", "search")');

        if (\Group::isFeatureActive() && !empty(\Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $query->innerJoin('category_group', 'cg', 'ps.id_category_default = cg.id_category');
            $query->where('cg.id_group =' . (int) \Configuration::get('PS_UNIDENTIFIED_GROUP'));
        }
        
        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $products = \Db::getInstance()->executeS($query);

        foreach ($products as $product) {
            $links[] = [
                'url' => $context->link->getProductLink($product, $product['link_rewrite'], $product['category'], $product['ean13']),
                'date_updated' => date('Y-m-d H:i:s'),
                'frequency' => \Configuration::get('SEOO_SITEMAP_PRODUCT_FREQUENCY'),
                'priority' => \Configuration::get('SEOO_SITEMAP_PRODUCT_PRIORITY'),
                'images' => self::getProductImages($product['id_product']),
            ];
        }

        return $links;
    }

    public static function getCount(): int
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('product', 'p');
        $query->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product');
        $query->where('ps.active = 1');
        $query->where('p.visibility IN ("both", "search")');

        if (\Group::isFeatureActive() && !empty(\Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $query->innerJoin('category_group', 'cg', 'ps.id_category_default = cg.id_category');
            $query->where('cg.id_group =' . (int) \Configuration::get('PS_UNIDENTIFIED_GROUP'));
        }

        return \Db::getInstance()->getValue($query);
    }

    private static function getProductImages($id_product)
    {
        $cache_id = 'ShopPageIndexer::getProductImages';
        if (!\Cache::isStored($cache_id)) {
            $context = \Context::getContext();
            $query = new \DbQuery();
            $query->select('i.id_image, il.legend, ps.id_product, pl.link_rewrite, pl.name as product_name');
            $query->from('image_shop', 'image_shop');
            $query->innerJoin('image', 'i', 'image_shop.id_image = i.id_image');
            $query->innerJoin('image_lang', 'il', 'i.id_image = il.id_image AND il.id_lang = ' . $context->language->id);
            $query->innerJoin('product_shop', 'ps', 'i.id_product = ps.id_product AND ps.id_shop = ' . $context->shop->id);
            $query->innerJoin('product_lang', 'pl', 'i.id_product = pl.id_product AND ps.id_shop = ' . $context->shop->id . ' AND pl.id_lang = ' . $context->language->id);
            $query->where('ps.active = 1');
            $query->where('image_shop.id_shop = ' . $context->shop->id);
            $query->orderBy('i.position');

            $images = \Db::getInstance()->executeS($query);
            $cache_by_product = [];
            foreach ($images as $image) {
                if (!isset($cache_by_product[$image['id_product']])) {
                    $cache_by_product[$image['id_product']] = [];
                }
                $cache_by_product[$image['id_product']][] = [
                    'url' => $context->link->getImageLink(
                        $image['link_rewrite'],
                        $image['id_image'],
                        \Configuration::get('SEOO_SITEMAP_PRODUCT_IMAGE_FORMAT')
                    ),
                    'name' => $image['product_name'],
                    'caption' => $image['legend'],
                ];
            }

            \Cache::store($cache_id, $cache_by_product);
        }

        $images = \Cache::retrieve($cache_id);
        return $images[$id_product] ?? [];
    }
}