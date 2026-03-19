<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleIndexer implements IndexerInterface
{

    public static function getType(): string
    {
        return 'module';
    }

    public static function getPages(int $page_id = null): array
    {
        $cache_id = 'sitemap_module_links_'.(int) $page_id;
        if (\Cache::isStored($cache_id)) {
            return \Cache::retrieve($cache_id);
        }

        $links = [];
        $context = \Context::getContext();
        $languages = \Language::getLanguages(true, $context->shop->id);
        $current_language = null;
        foreach ($languages as $language) {
            if ($language['id_lang'] == $context->language->id) {
                $current_language = $language;
                break;
            }
        }

        $modules_links = \Hook::exec('gSitemapAppendUrls', [
            'lang' => $current_language,
        ], null, true);

        if (empty($modules_links) || !is_array($modules_links)) {
            return [];
        }

        foreach ($modules_links as $module_links) {
            if (empty($module_links) || !is_array($module_links)) {
                continue;
            }
            foreach ($module_links as $link) {
                $links[] = [
                    'id_entity' => 0,
                    'url' => $link['link'],
                    'date_updated' => null,
                    'frequency' => \Configuration::get('SEOO_SITEMAP_DEFAULT_FREQUENCY'),
                    'priority' => \Configuration::get('SEOO_SITEMAP_DEFAULT_PRIORITY'),
                ];
            }
        }

        $per_page = (int) \Configuration::get('SEOO_SITEMAP_PER_PAGE');
        if ($page_id && $per_page) {
            $links = array_slice($links, ($page_id - 1) * $per_page, $per_page);
        }

        \Cache::store($cache_id, $links);
        return $links;
    }

    public static function getCount(): int
    {
        return count(self::getPages());
    }
}