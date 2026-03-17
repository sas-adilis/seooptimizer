<?php

namespace Adilis\SeoOptimizer;

class ShopPageIndexer
{



    public static function getPagesByType($page_type):array
    {
        if (method_exists(__CLASS__, 'index' . ucfirst($page_type))) {
            return call_user_func([__CLASS__, 'index' . ucfirst($page_type)]);
        }
        return [];
    }

    public static function getPagesCountByType($page_type):int
    {
        if (method_exists(__CLASS__, 'count' . ucfirst($page_type))) {
            return call_user_func([__CLASS__, 'count' . ucfirst($page_type)]);
        }
        return 0;
    }

    /**
     * @throws \PrestaShopDatabaseException
     */

}
