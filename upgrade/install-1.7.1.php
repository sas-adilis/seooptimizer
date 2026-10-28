<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.7.1 — Cache of the HTTP status of the checked links.
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_7_1($object)
{
    return (bool) Db::getInstance()->execute(
        'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_link_status` (
            `url_hash` CHAR(32) NOT NULL,
            `url` VARCHAR(2083) NOT NULL,
            `http_code` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            `redirect_url` VARCHAR(2083) NOT NULL DEFAULT "",
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`url_hash`),
            KEY `date_upd` (`date_upd`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
    );
}
