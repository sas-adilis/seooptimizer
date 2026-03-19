<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CmsIndexer implements IndexerInterface
{
    public static function getType(): string
    {
        return 'cms';
    }

    public static function getPages(int $page_id = null): array
    {
        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('c.id_cms, cl.link_rewrite');
        $query->from('cms', 'c');
        $query->innerJoin('cms_shop', 'cs', 'c.id_cms = cs.id_cms AND cs.id_shop = ' . $context->shop->id);
        $query->leftJoin('cms_lang', 'cl', 'c.id_cms = cl.id_cms AND cs.id_shop = ' . $context->shop->id . ' AND cl.id_lang = ' . $context->language->id);
        $query->where('c.active = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $cms = \Db::getInstance()->executeS($query);

        foreach ($cms as $page) {
            $links[] = [
                'id_entity' => (int) $page['id_cms'],
                'url' => $context->link->getCMSLink($page, $page['link_rewrite']),
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
        $query->from('cms', 'c');
        $query->innerJoin('cms_shop', 'cs', 'c.id_cms = cs.id_cms AND cs.id_shop = ' . (int)\Context::getContext()->shop->id);
        $query->where('c.active = 1');

        return \Db::getInstance()->getValue($query);
    }
}