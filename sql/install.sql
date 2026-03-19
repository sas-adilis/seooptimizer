CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_redirect` (
    `id_seooptimizer_redirect` int(11) NOT NULL AUTO_INCREMENT,
    `redirect_from` varchar(2083) NOT NULL,
    `redirect_to` varchar(2083) NOT NULL,
    `redirect_type` varchar(3) NOT NULL DEFAULT '301',
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_seooptimizer_redirect`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_log_404` (
    `id_seooptimizer_log_404` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(11) UNSIGNED NOT NULL DEFAULT 0,
    `id_shop_group` int(11) UNSIGNED NOT NULL DEFAULT 0,
    `url` varchar(2083) NOT NULL,
    `referer` varchar(2083) NOT NULL,
    `remote_ip` varchar(15) NOT NULL DEFAULT '',
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_seooptimizer_log_404`),
    KEY `url` (`url`(191))
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_page` (
    `id_seooptimizer_page` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(32) NOT NULL,
    `id_entity` INT(11) UNSIGNED NOT NULL,
    `id_lang` INT(11) UNSIGNED NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `url` VARCHAR(2083) NOT NULL DEFAULT '',
    `keywords` VARCHAR(512) NOT NULL DEFAULT '',
    `count_critical` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `count_warning` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `count_info` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `count_total` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `score` DECIMAL(5,1) NOT NULL DEFAULT 0.0,
    `grade` VARCHAR(4) NOT NULL DEFAULT '',
    `date_audit` DATETIME DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_seooptimizer_page`),
    UNIQUE KEY `entity_lang_shop` (`entity_type`, `id_entity`, `id_lang`, `id_shop`),
    KEY `entity_type` (`entity_type`, `id_entity`),
    KEY `url` (`url`(191))
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_audit_run` (
    `id_seooptimizer_audit_run` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_key` VARCHAR(64) NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `status` VARCHAR(16) NOT NULL DEFAULT 'running',
    `total_urls` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `crawled` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `items_json` MEDIUMTEXT,
    `custom_kpis_json` TEXT,
    `urls_json` MEDIUMTEXT,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_seooptimizer_audit_run`),
    UNIQUE KEY `audit_shop` (`audit_key`, `id_shop`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_audit_result` (
    `id_seooptimizer_audit_result` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_key` VARCHAR(64) NOT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `entity_type` VARCHAR(32) NOT NULL DEFAULT '',
    `id_entity` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `url` VARCHAR(2083) NOT NULL,
    `severity` VARCHAR(16) NOT NULL DEFAULT 'info',
    `type` VARCHAR(64) NOT NULL DEFAULT '',
    `message` TEXT,
    `data_json` TEXT,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_seooptimizer_audit_result`),
    KEY `audit_key` (`audit_key`),
    KEY `entity` (`entity_type`, `id_entity`),
    KEY `url` (`url`(191)),
    KEY `severity` (`severity`),
    KEY `audit_severity` (`audit_key`, `severity`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_indexation_rule` (
    `id_seooptimizer_indexation_rule` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(11) NOT NULL DEFAULT 1,
    `type` varchar(25) NOT NULL,
    `term` varchar(2083) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_seooptimizer_indexation_rule`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
