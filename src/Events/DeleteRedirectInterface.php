<?php

namespace Adilis\SeoOptimizer\Events;

interface DeleteRedirectInterface
{
    public function __construct(\ObjectModel $object);

    public function process();

    public function shouldRun(): bool;

    public function getRedirections(): array;
}
