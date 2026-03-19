<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($object)
{
    $sql = [];

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'seooptimizer_page`
        ADD COLUMN `score` DECIMAL(5,1) NOT NULL DEFAULT 0.0 AFTER `count_total`,
        ADD COLUMN `grade` VARCHAR(4) NOT NULL DEFAULT \'\' AFTER `score`';

    foreach ($sql as $query) {
        try {
            Db::getInstance()->execute($query);
        } catch (Exception $e) {
            // Columns may already exist
        }
    }

    return true;
}
