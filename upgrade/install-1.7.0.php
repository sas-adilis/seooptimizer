<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.7.0 — PageSpeed Insights (Lighthouse) results table and settings.
 *
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_7_0($object)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_pagespeed` (
        `id_seooptimizer_pagespeed` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `url` VARCHAR(2083) NOT NULL,
        `page_type` VARCHAR(32) NOT NULL DEFAULT "",
        `strategy` VARCHAR(16) NOT NULL DEFAULT "mobile",
        `status` VARCHAR(16) NOT NULL DEFAULT "pending",
        `score_performance` TINYINT(3) UNSIGNED DEFAULT NULL,
        `score_accessibility` TINYINT(3) UNSIGNED DEFAULT NULL,
        `score_best_practices` TINYINT(3) UNSIGNED DEFAULT NULL,
        `score_seo` TINYINT(3) UNSIGNED DEFAULT NULL,
        `metrics_json` TEXT,
        `audits_json` MEDIUMTEXT,
        `field_json` TEXT,
        `error` TEXT,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_seooptimizer_pagespeed`),
        KEY `shop_status` (`id_shop`, `status`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

    if (!Db::getInstance()->execute($sql)) {
        return false;
    }

    if (Configuration::get('SEOO_PSI_PAGES_PER_TYPE') === false) {
        Configuration::updateValue('SEOO_PSI_PAGES_PER_TYPE', 3);
    }

    return true;
}
