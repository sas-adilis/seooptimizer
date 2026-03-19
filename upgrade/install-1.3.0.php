<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($object)
{
    $sql = [];

    // Rename seooptimizer_keyword -> seooptimizer_page with new columns
    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'seooptimizer_keyword`';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_page` (
        `id_seooptimizer_page` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `entity_type` VARCHAR(32) NOT NULL,
        `id_entity` INT(11) UNSIGNED NOT NULL,
        `id_lang` INT(11) UNSIGNED NOT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `url` VARCHAR(2083) NOT NULL DEFAULT \'\',
        `keywords` VARCHAR(512) NOT NULL DEFAULT \'\',
        `count_critical` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `count_warning` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `count_info` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `count_total` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `date_audit` DATETIME DEFAULT NULL,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_seooptimizer_page`),
        UNIQUE KEY `entity_lang_shop` (`entity_type`, `id_entity`, `id_lang`, `id_shop`),
        KEY `entity_type` (`entity_type`, `id_entity`),
        KEY `url` (`url`(191))
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    // Add entity columns to audit_result
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'seooptimizer_audit_result`
        ADD COLUMN `entity_type` VARCHAR(32) NOT NULL DEFAULT \'\' AFTER `id_shop`,
        ADD COLUMN `id_entity` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `entity_type`,
        ADD KEY `entity` (`entity_type`, `id_entity`)';

    foreach ($sql as $query) {
        try {
            Db::getInstance()->execute($query);
        } catch (Exception $e) {
            // Column may already exist
        }
    }

    return true;
}
