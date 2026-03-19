<?php

namespace Adilis\SeoOptimizer\Events;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface DeleteRedirectInterface
{
    public function __construct(\ObjectModel $object);

    public function process();

    public function shouldRun(): bool;

    public function getRedirections(): array;
}
