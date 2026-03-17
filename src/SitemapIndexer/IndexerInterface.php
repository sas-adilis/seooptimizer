<?php
namespace Adilis\SeoOptimizer\SitemapIndexer;

interface IndexerInterface
{
    public static function getType(): string;
    public static function getPages(int $page_id = null): array;
    public static function getCount(): int;

}