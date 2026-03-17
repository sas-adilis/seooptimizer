<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

class EntityDefinitionLoader
{
    private static $instances;
    private static $definitionClasses = [
        EntityDefinitionProduct::class,
        EntityDefinitionCategory::class,
        EntityDefinitionCms::class,
        EntityDefinitionCmsCategory::class,
        EntityDefinitionManufacturer::class,
        EntityDefinitionSimpleBlogPost::class,
        EntityDefinitionSimpleBlogCategory::class,
        EntityDefinitionMeta::class,
        EntityDefinitionImage::class,
    ];

    public static function getInstances($clear_cache = false)
    {
        if (self::$instances !== null && !$clear_cache) {
            return self::$instances;
        }

        self::$instances = [];

        self::$instances = array_map(function ($definitionClass) {
            $instance = new $definitionClass();
            if ($instance->isEnabled()) {
                return [$instance];
            }

            return [];
        }, self::$definitionClasses);

        self::$instances = array_merge(...self::$instances);

        return self::$instances;
    }
}
