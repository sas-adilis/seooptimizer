<?php

namespace Adilis\SeoOptimizer\PageSpeed;

use Adilis\SeoOptimizer\SitemapIndexer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Picks the pages sent to PageSpeed Insights: the home page plus a few pages of each page type,
 * so that every template of the theme is covered without analyzing the whole shop.
 */
class PageSpeedSampler
{
    /**
     * @param int $perType Number of pages taken for each type
     *
     * @return array<int, array{url: string, type: string}>
     */
    public static function getSample(int $perType): array
    {
        $perType = max(1, $perType);
        $sample = [];
        $seen = [];

        $home = \Context::getContext()->link->getPageLink('index', true);
        $sample[] = ['url' => $home, 'type' => 'home'];
        $seen[$home] = true;

        foreach (SitemapIndexer::getAllPagesTypes() as $type) {
            $taken = 0;
            foreach (SitemapIndexer::getPagesByType($type, 1) as $item) {
                if ($taken >= $perType) {
                    break;
                }
                if (empty($item['url']) || isset($seen[$item['url']])) {
                    continue;
                }
                $seen[$item['url']] = true;
                $sample[] = ['url' => $item['url'], 'type' => (string) $type];
                ++$taken;
            }
        }

        return $sample;
    }
}
