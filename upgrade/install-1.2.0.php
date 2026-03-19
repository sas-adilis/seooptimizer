<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_2_0($object)
{
    $sql = [];

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_audit_run` (
        `id_seooptimizer_audit_run` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `audit_key` VARCHAR(64) NOT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `status` VARCHAR(16) NOT NULL DEFAULT \'running\',
        `total_urls` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `crawled` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `items_json` MEDIUMTEXT,
        `custom_kpis_json` TEXT,
        `urls_json` MEDIUMTEXT,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_seooptimizer_audit_run`),
        UNIQUE KEY `audit_shop` (`audit_key`, `id_shop`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_audit_result` (
        `id_seooptimizer_audit_result` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `audit_key` VARCHAR(64) NOT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `url` VARCHAR(2083) NOT NULL,
        `severity` VARCHAR(16) NOT NULL DEFAULT \'info\',
        `type` VARCHAR(64) NOT NULL DEFAULT \'\',
        `message` TEXT,
        `data_json` TEXT,
        `date_add` DATETIME NOT NULL,
        PRIMARY KEY (`id_seooptimizer_audit_result`),
        KEY `audit_key` (`audit_key`),
        KEY `url` (`url`(191)),
        KEY `severity` (`severity`),
        KEY `audit_severity` (`audit_key`, `severity`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}
