<?php

namespace Adilis\SeoOptimizer\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * SEO grade column in the Symfony back-office grids.
 */
class GridScoreColumn
{
    /**
     * Add a SEO grade DataColumn to a Symfony grid.
     *
     * @param array<string, mixed> $params Hook params
     * @param string $afterColumn Column ID after which to insert
     */
    public static function addColumn(array $params, string $afterColumn = 'active')
    {
        if (!(int) \Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            return;
        }

        $columnClass = 'PrestaShop\\PrestaShop\\Core\\Grid\\Column\\Type\\DataColumn';
        if (!class_exists($columnClass)) {
            return;
        }

        $params['definition']->getColumns()->addAfter(
            $afterColumn,
            (new $columnClass('seo_grade'))
                ->setName('SEO')
                ->setOptions(['field' => 'seo_grade'])
        );
    }

    /**
     * Add LEFT JOIN on seooptimizer_page to a Symfony grid query builder.
     *
     * @param array<string, mixed> $params Hook params
     * @param string $entityType
     * @param string $tableAlias Main table alias in the grid query
     * @param string $primaryKey Primary key column name
     */
    public static function addJoin(array $params, string $entityType, string $tableAlias, string $primaryKey)
    {
        if (!(int) \Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            return;
        }

        $context = \Context::getContext();

        $joinCondition = 'seoo_p.entity_type = "' . pSQL($entityType) . '"'
            . ' AND seoo_p.id_entity = ' . $tableAlias . '.' . $primaryKey
            . ' AND seoo_p.id_lang = ' . (int) $context->language->id
            . ' AND seoo_p.id_shop = ' . (int) $context->shop->id;

        foreach (['search_query_builder', 'count_query_builder'] as $builder) {
            if (isset($params[$builder])) {
                $params[$builder]->leftJoin(
                    $tableAlias,
                    _DB_PREFIX_ . 'seooptimizer_page',
                    'seoo_p',
                    $joinCondition
                );
            }
        }

        if (isset($params['search_query_builder'])) {
            $params['search_query_builder']->addSelect(
                'IFNULL(seoo_p.grade, "-") AS seo_grade'
            );
        }
    }
}
