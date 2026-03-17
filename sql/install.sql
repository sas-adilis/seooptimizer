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

CREATE TABLE IF NOT EXISTS `PREFIX_seooptimizer_indexation_rule` (
    `id_seooptimizer_indexation_rule` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(11) NOT NULL DEFAULT 1,
    `type` varchar(25) NOT NULL,
    `term` varchar(2083) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_seooptimizer_indexation_rule`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
