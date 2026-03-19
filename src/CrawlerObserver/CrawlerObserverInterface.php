<?php

namespace Adilis\SeoOptimizer\CrawlerObserver;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface CrawlerObserverInterface
{
    public function getResults(): array;
}
