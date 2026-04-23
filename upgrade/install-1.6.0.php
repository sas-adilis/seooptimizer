<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.6.0 — Add per-entity SEO configuration fields (canonical, noindex, nofollow)
 * and register hooks for entity form display.
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_6_0($object)
{
    $sql = [];

    // Add canonical_url column
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'seooptimizer_page`
        ADD COLUMN `canonical_url` VARCHAR(2083) NOT NULL DEFAULT "" AFTER `keywords`';

    // Add noindex column (0=default, 1=noindex, 2=index)
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'seooptimizer_page`
        ADD COLUMN `noindex` TINYINT(1) NOT NULL DEFAULT 0 AFTER `canonical_url`';

    // Add nofollow column (0=default, 1=nofollow)
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'seooptimizer_page`
        ADD COLUMN `nofollow` TINYINT(1) NOT NULL DEFAULT 0 AFTER `noindex`';

    foreach ($sql as $query) {
        try {
            Db::getInstance()->execute($query);
        } catch (\Throwable $e) {
            // Column may already exist — ignore
        }
    }

    // Clean up hooks that were registered without corresponding methods
    $hooksToRemove = [
        'actionProductSave',
        'actionAfterUpdateCategoryFormHandler',
        'actionAfterCreateCategoryFormHandler',
    ];

    foreach ($hooksToRemove as $hook) {
        $object->unregisterHook($hook);
    }

    // Register new hooks for displaying SEO config/audit panel
    $hooks = [
        'displayAdminProductsSeoStepBottom',
        'displayBackOfficeCategory',
    ];

    foreach ($hooks as $hook) {
        $object->registerHook($hook);
    }

    return true;
}
