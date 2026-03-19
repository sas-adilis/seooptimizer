<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param SeoOptimizer $object
 * @return bool
 */
function upgrade_module_1_1_0($object)
{
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'seooptimizer_keyword` (
        `id_seooptimizer_keyword` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `entity_type` VARCHAR(32) NOT NULL,
        `id_entity` INT(11) UNSIGNED NOT NULL,
        `id_lang` INT(11) UNSIGNED NOT NULL,
        `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
        `keywords` VARCHAR(512) NOT NULL DEFAULT \'\',
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_seooptimizer_keyword`),
        UNIQUE KEY `entity_lang_shop` (`entity_type`, `id_entity`, `id_lang`, `id_shop`),
        KEY `entity_type` (`entity_type`, `id_entity`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

    return Db::getInstance()->execute($sql);
}
