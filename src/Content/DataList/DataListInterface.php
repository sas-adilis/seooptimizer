<?php

namespace Adilis\SeoOptimizer\Content\DataList;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface DataListInterface
{
    public function getTable(): string;

    public function getTitle(): string;

    public function getIcon(): string;

    public function getKey($to_underscore_case = false): string;

    public function renderList(array $fields_list): string;

    public function getContent();

    public function getFields(): array;

    public function process();
}
