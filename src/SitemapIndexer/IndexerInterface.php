<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface IndexerInterface
{
    public static function getType(): string;
    public static function getPages(int $page_id = null): array;
    public static function getCount(): int;

}