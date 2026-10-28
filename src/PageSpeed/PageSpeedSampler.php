<?php

namespace Adilis\SeoOptimizer\PageSpeed;

use Adilis\SeoOptimizer\Audit\AuditRunner;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Picks the pages sent to PageSpeed Insights: the home page plus a few pages of each page type,
 * so that every template of the theme is covered without analyzing the whole shop.
 * The pages of a type are spread evenly over its whole list (not the first ones).
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

        $home = \Context::getContext()->link->getPageLink('index', true);
        $sample[] = ['url' => $home, 'type' => 'home'];
        $seen = [$home => true];

        foreach (AuditRunner::collectAllUrls() as $type => $items) {
            $count = count($items);
            if ($count === 0) {
                continue;
            }

            $taken = 0;
            $wanted = min($perType, $count);
            for ($i = 0; $i < $wanted; ++$i) {
                // Middle of each of the $wanted equal slices of the list
                $index = (int) floor(($i + 0.5) * $count / $wanted);
                $url = $items[$index]['url'];
                if (isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                $sample[] = ['url' => $url, 'type' => (string) $type];
                ++$taken;
            }
        }

        return $sample;
    }
}
