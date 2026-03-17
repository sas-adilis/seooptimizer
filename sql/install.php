<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
$sql = str_replace(
    ['PREFIX_', 'ENGINE_TYPE'],
    [_DB_PREFIX_, _MYSQL_ENGINE_],
    $sql
);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
