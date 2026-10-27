<?php

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Routes
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function get(): array
    {
        return [
            'module-seooptimizer-sitemap-index' => [
                'controller' => 'sitemap',
                'rule' => 'sitemap.xml',
                'keywords' => [],
                'params' => [
                    'module' => 'seooptimizer',
                    'fc' => 'module',
                ],
            ],
            'module-seooptimizer-sitemap' => [
                'controller' => 'sitemap',
                'rule' => 'sitemap/{type}/{lang}/{page}.xml',
                'keywords' => [
                    'type' => [
                        'regexp' => '[a-z_]*',
                        'param' => 'type',
                    ],
                    'lang' => [
                        'regexp' => '[a-zA-Z]{2,3}',
                        'param' => 'lang',
                    ],
                    'page' => [
                        'regexp' => '[0-9]*',
                        'param' => 'page',
                    ],
                ],
                'params' => [
                    'module' => 'seooptimizer',
                    'fc' => 'module',
                ],
            ],
        ];
    }
}
