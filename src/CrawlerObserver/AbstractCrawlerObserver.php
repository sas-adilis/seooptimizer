<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class AbstractCrawlerObserver
{
    public function getKey(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }
}
