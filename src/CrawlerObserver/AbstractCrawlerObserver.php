<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

abstract class AbstractCrawlerObserver
{
    public function getKey(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }
}
