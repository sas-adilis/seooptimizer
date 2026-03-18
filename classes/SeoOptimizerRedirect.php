<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SeoOptimizerRedirect extends ObjectModel
{
    /** @var string */
    public $redirect_from;

    /** @var string */
    public $redirect_to;

    /** @var string */
    public $redirect_type;

    /** @var string */
    public $date_add;

    /**
     * @var array<string, mixed>
     */
    public static $definition = [
        'table' => 'seooptimizer_redirect',
        'primary' => 'id_seooptimizer_redirect',
        'fields' => [
            'redirect_from' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 2083, 'required' => true],
            'redirect_to' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 2083, 'required' => true],
            'redirect_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 3],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
