<?php
/**
 * @author    Adilis <support@adilis.fr>
 * @copyright Adilis
 * @license   http://www.adilis.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SeoOptimizerIndexationRule extends ObjectModel
{
    /** @var int */
    public $id_shop;

    /** @var string */
    public $type;

    /** @var string */
    public $term;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /**
     * @var array<string, mixed>
     */
    public static $definition = [
        'table' => 'seooptimizer_indexation_rule',
        'primary' => 'id_seooptimizer_indexation_rule',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 25, 'required' => true],
            'term' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 2083, 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
