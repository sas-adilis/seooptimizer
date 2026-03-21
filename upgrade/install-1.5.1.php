<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.5.1 — Register Symfony grid hooks for all entity types
 * (categories, manufacturers, suppliers, CMS).
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_5_1($object)
{
    $hooks = [
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
