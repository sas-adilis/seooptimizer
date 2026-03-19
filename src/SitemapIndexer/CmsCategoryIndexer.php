<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CmsCategoryIndexer implements IndexerInterface
{
    public static function getType(): string
    {
        return 'cms_category';
    }

    public static function getPages(int $page_id = null): array
    {
        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('c.id_cms_category, cl.link_rewrite, c.date_upd');
        $query->from('cms_category', 'c');
        $query->innerJoin('cms_category_shop', 'cs', 'c.id_cms_category = cs.id_cms_category AND cs.id_shop = ' . $context->shop->id);
        $query->leftJoin('cms_category_lang', 'cl', 'c.id_cms_category = cl.id_cms_category AND cs.id_shop = ' . $context->shop->id . ' AND cl.id_lang = ' . $context->language->id);
        $query->where('c.active = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $categories = \Db::getInstance()->executeS($query);

        foreach ($categories as $category) {
            $links[] = [
                'id_entity' => (int) $category['id_cms_category'],
                'url' => $context->link->getCMSCategoryLink($category, $category['link_rewrite']),
                'date_updated' => null,
                'frequency' => \Configuration::get('SEOO_SITEMAP_CMS_FREQUENCY'),
                'priority' => \Configuration::get('SEOO_SITEMAP_CMS_PRIORITY'),
            ];
        }

        return $links;
    }

    public static function getCount(): int
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('cms_category', 'c');
        $query->innerJoin('cms_category_shop', 'cs', 'c.id_cms_category = cs.id_cms_category AND cs.id_shop = ' . (int)\Context::getContext()->shop->id);
        $query->where('c.active = 1');

        return \Db::getInstance()->getValue($query);
    }
}