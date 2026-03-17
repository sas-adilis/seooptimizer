<?php
/**
 * 2024 Adilis.
 * Manage returns and exchanges easily and quickly
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TranslateHelper
{
    /**
     * @var self|null
     */
    protected static $instance;

    public static function get()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @throws \Exception
     */
    public function l($string, $source = '', $locale = null)
    {
        return \Translate::getModuleTranslation('seeoptimizer', $string, $source, null, false, $locale);
    }
}
