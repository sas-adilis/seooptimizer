<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.5.0 — Register Symfony grid hooks for SEO score column
 * (products, categories, manufacturers, suppliers, CMS).
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_5_0($object)
{
    $hooks = [
        'actionProductGridDefinitionModifier',
        'actionProductGridQueryBuilderModifier',
        'actionCategoryGridDefinitionModifier',
        'actionCategoryGridQueryBuilderModifier',
        'actionManufacturerGridDefinitionModifier',
        'actionManufacturerGridQueryBuilderModifier',
        'actionSupplierGridDefinitionModifier',
        'actionSupplierGridQueryBuilderModifier',
        'actionCmsPageGridDefinitionModifier',
        'actionCmsPageGridQueryBuilderModifier',
    ];

    foreach ($hooks as $hook) {
        $object->registerHook($hook);
    }

    return true;
}
