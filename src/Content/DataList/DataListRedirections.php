<?php

namespace Adilis\SeoOptimizer\Content\DataList;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

class DataListRedirections extends DataList implements DataListInterface
{
    public function getTable(): string
    {
        return 'seooptimizer_redirect';
    }

    public function getFields(): array
    {
        return [
            'id_seooptimizer_redirect' => [
                'title' => 'ID',
                'type' => 'int',
                'orderby' => true,
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'redirect_from' => [
                'title' => $this->l('Old URL'),
                'orderby' => true,
                'callback_object' => Utils::class,
                'callback' => 'displayTruncableLink',
            ],
            'redirect_to' => [
                'title' => $this->l('New URL'),
                'orderby' => true,
                'callback_object' => Utils::class,
                'callback' => 'displayTruncableLink',
            ],
            'redirect_type' => [
                'title' => $this->l('Redirect Type'),
                'orderby' => true,
                'align' => 'center',
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
                'orderby' => true,
            ],
        ];
    }

    public function getTitle(): string
    {
        return $this->l('Redirections');
    }

    public function getIcon(): string
    {
        return 'icon-share';
    }
}
