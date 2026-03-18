<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SeoOptimizerLog404 extends ObjectModel
{
    /** @var int */
    public $id_shop;

    /** @var int */
    public $id_shop_group;

    /** @var string */
    public $url;

    /** @var string */
    public $referer;

    /** @var string */
    public $remote_ip;

    /** @var string */
    public $date_add;

    /**
     * @var array<string, mixed>
     */
    public static $definition = [
        'table' => 'seooptimizer_log_404',
        'primary' => 'id_seooptimizer_log_404',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 2083, 'required' => true],
            'referer' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 2083],
            'remote_ip' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 15],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
