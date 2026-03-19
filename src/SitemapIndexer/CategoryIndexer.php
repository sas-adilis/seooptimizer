<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CategoryIndexer implements IndexerInterface
{
    public static function getType(): string
    {
        return 'category';
    }

    public static function getPages(int $page_id = null): array
    {
        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('c.id_category, cl.link_rewrite, cl.name, cl.meta_title');
        $query->select('IF(c.date_upd > c.date_upd, c.date_upd, c.date_upd) as date_upd');
        $query->from('category', 'c');
        $query->innerJoin('category_shop', 'cs', 'c.id_category = cs.id_category AND cs.id_shop = ' . $context->shop->id);
        $query->leftJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_shop = ' . $context->shop->id . ' AND cl.id_lang = ' . $context->language->id);
        if (\Group::isFeatureActive() && !empty(\Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $query->innerJoin('category_group', 'cg', 'c.id_category = cg.id_category');
            $query->where('cg.id_group =' . (int) \Configuration::get('PS_UNIDENTIFIED_GROUP'));
        }
        $query->where('c.id_parent != ' . (int) \Configuration::get('PS_ROOT_CATEGORY'));
        $query->where('c.id_parent != ' . (int) \Configuration::get('PS_HOME_CATEGORY'));
        $query->where('c.active = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $categories = \Db::getInstance()->executeS($query);

        foreach ($categories as $category) {
            $links[] = [
                'id_entity' => (int) $category['id_category'],
                'url' => $context->link->getCategoryLink($category, $category['link_rewrite']),
                'date_updated' => date('Y-m-d H:i:s'),
                'frequency' => \Configuration::get('SEOO_SITEMAP_CATEGORY_FREQUENCY'),
                'priority' => \Configuration::get('SEOO_SITEMAP_CATEGORY_PRIORITY'),
                'images' => self::getCategoryImages($category),
            ];
        }

        return $links;
    }

    public static function getCount(): int
    {
        $context = \Context::getContext();
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('category', 'c');
        $query->innerJoin('category_shop', 'cs', 'c.id_category = cs.id_category AND cs.id_shop = ' . $context->shop->id);
        if (\Group::isFeatureActive() && !empty(\Configuration::get('PS_UNIDENTIFIED_GROUP'))) {
            $query->innerJoin('category_group', 'cg', 'c.id_category = cg.id_category');
            $query->where('cg.id_group =' . (int) \Configuration::get('PS_UNIDENTIFIED_GROUP'));
        }
        $query->where('c.id_parent != ' . (int) \Configuration::get('PS_ROOT_CATEGORY'));
        $query->where('c.id_parent != ' . (int) \Configuration::get('PS_HOME_CATEGORY'));
        $query->where('c.active = 1');

        return \Db::getInstance()->getValue($query);
    }

    private static function getCategoryImages(array $category): array
    {
        if (!\Configuration::get('SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES')) {
            return [];
        }


        if (!file_exists(_PS_CAT_IMG_DIR_ .(int)$category['id_category'] . '.jpg')) {
            return [];
        }

        $image_link = \Context::getContext()->link->getCatImageLink(
            $category['link_rewrite'],
            (int) $category['id_category'],
            \Configuration::get('SEOO_SITEMAP_CATEGORY_IMAGE_FORMAT')
        );

        return [
            [
                'url' => $image_link,
                'name' => $category['name'],
                'caption' => $category['meta_title'],
            ],
        ];
    }
}