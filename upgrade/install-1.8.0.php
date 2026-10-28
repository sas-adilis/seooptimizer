<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.8.0 — History of the scores, by audit, page and date.
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_8_0($object)
{
    return (bool) Db::getInstance()->execute(
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_history` (
            `id_seooptimizer_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
            `audit_key` VARCHAR(64) NOT NULL,
            `url_hash` CHAR(32) NOT NULL,
            `url` VARCHAR(2083) NOT NULL DEFAULT "",
            `score` DECIMAL(4,1) UNSIGNED NOT NULL DEFAULT 0,
            `count_critical` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            `count_warning` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            `count_info` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            `date_audit` DATE NOT NULL,
            PRIMARY KEY (`id_seooptimizer_history`),
            UNIQUE KEY `snapshot` (`id_shop`, `audit_key`, `url_hash`, `date_audit`),
            KEY `date_audit` (`date_audit`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
    );
}
