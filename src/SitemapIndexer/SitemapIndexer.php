<?php

namespace Adilis\SeoOptimizer\SitemapIndexer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SitemapIndexer
{

    private static $definitionClasses = [
        ProductIndexer::class,
        CategoryIndexer::class,
        CmsIndexer::class,
        ManufacturerIndexer::class,
        SupplierIndexer::class,
        CmsCategoryIndexer::class,
        MetaIndexer::class,
        ModuleIndexer::class
    ];

    public static function getPagesByType($page_type, $page = 1):array
    {
        /** @var IndexerInterface $definitionClass */
        foreach (self::$definitionClasses as $definitionClass) {
            if (method_exists($definitionClass, 'getPages') && $definitionClass::getType() == $page_type) {
                return $definitionClass::getPages($page);
            }
        }
        return [];
    }

    public static function getPagesCountByType($page_type):int
    {
        /** @var IndexerInterface $definitionClass */
        foreach (self::$definitionClasses as $definitionClass) {
            if (method_exists($definitionClass, 'getCount') && $definitionClass::getType() == $page_type) {
                return $definitionClass::getCount();
            }
        }
        return 0;
    }

    public static function getAllPagesTypes():array
    {
        $types = [];
        /** @var IndexerInterface $definitionClass */
        foreach (self::$definitionClasses as $definitionClass) {
            if (method_exists($definitionClass, 'getType')) {
                $types[] = $definitionClass::getType();
            }
        }
        return $types;
    }

}
