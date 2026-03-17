<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

class MetaIndexer implements IndexerInterface
{
    public static function getType(): string
    {
        return 'meta';
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public static function getPages(int $page_id = null): array
    {
        $context = \Context::getContext();
        $links = [];
        $query = new \DbQuery();
        $query->select('m.page, ml.url_rewrite');
        $query->from('meta', 'm');
        $query->innerJoin('meta_lang', 'ml', 'm.id_meta = ml.id_meta AND ml.id_shop = ' . $context->shop->id . ' AND ml.id_lang = ' . $context->language->id);
        $query->where('m.configurable = 1');

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $query->limit($per_page, ($page_id - 1) * $per_page);
        }

        $metas = \Db::getInstance()->executeS($query);

        foreach ($metas as $meta) {
            try {
                $links[] = [
                    'url' => $context->link->getPageLink($meta['page']),
                    'date_updated' => null,
                    'frequency' => \Configuration::get('SEOO_SITEMAP_META_FREQUENCY'),
                    'priority' => \Configuration::get('SEOO_SITEMAP_META_PRIORITY'),
                ];
            } catch (\PrestaShopException $e) {
                continue;
            }
        }

        return $links;
    }

    public static function getCount(): int
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('meta', 'm');
        $query->where('m.configurable = 1');

        return \Db::getInstance()->getValue($query);
    }
}