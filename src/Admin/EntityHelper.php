<?php

namespace Adilis\SeoOptimizer\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Entity type / controller mapping and entity resolution (ID + front URL).
 */
class EntityHelper
{
    /** Admin controller => entity type */
    const CONTROLLER_MAP = [
        'AdminProducts' => 'product',
        'AdminCategories' => 'category',
        'AdminManufacturers' => 'manufacturer',
        'AdminSuppliers' => 'supplier',
        'AdminCmsContent' => 'cms',
    ];

    /** Entity types using the server-rendered tab system */
    const TAB_ENTITIES = ['category'];

    /**
     * Detect the entity ID from the current request (GET/POST params + Symfony route params).
     *
     * @param \Module $module
     * @param string $entityType
     *
     * @return int
     */
    public static function detectEntityId(\Module $module, string $entityType): int
    {
        // Parameter names per entity type (Legacy name + Symfony route name)
        $paramMap = [
            'product' => ['id_product', 'productId'],
            'category' => ['id_category', 'categoryId'],
            'manufacturer' => ['id_manufacturer', 'manufacturerId'],
            'supplier' => ['id_supplier', 'supplierId'],
            'cms' => ['id_cms', 'cmsPageId'],
        ];

        $params = isset($paramMap[$entityType]) ? $paramMap[$entityType] : [];

        // 1. Try GET/POST (Legacy admin)
        foreach ($params as $param) {
            $id = (int) \Tools::getValue($param);
            if ($id > 0) {
                return $id;
            }
        }

        // 2. Try Symfony Request attributes (PS 8/9 route params)
        if (class_exists('Symfony\Component\HttpFoundation\Request')) {
            try {
                $request = call_user_func(['Symfony\Component\HttpFoundation\Request', 'createFromGlobals']);
                // In PS 8/9, the global kernel request has the route attributes
                if (method_exists($module, 'get') && $module->get('request_stack')) {
                    $request = $module->get('request_stack')->getCurrentRequest();
                }
                if ($request) {
                    foreach ($params as $param) {
                        $val = $request->attributes->getInt($param);
                        if ($val > 0) {
                            return $val;
                        }
                        // Also check query and request bags
                        $val = (int) $request->get($param, 0);
                        if ($val > 0) {
                            return $val;
                        }
                    }
                    // Generic "id"
                    $val = $request->attributes->getInt('id');
                    if ($val > 0) {
                        return $val;
                    }
                }
            } catch (\Throwable $e) {
                // Symfony not available, skip
            }
        }

        // 3. Fallback: generic "id" parameter
        $id = (int) \Tools::getValue('id');
        if ($id > 0) {
            return $id;
        }

        // 4. Parse from URL path (last numeric segment: /edit/3 or /3/edit)
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match('/\/(\d+)(?:\/edit|\/update)?(?:\?|$)/', $uri, $matches)) {
            $id = (int) $matches[1];
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Resolve the front-office URL for a given entity type and ID.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param int $idLang
     *
     * @return string
     */
    public static function getEntityUrl(string $entityType, int $idEntity, int $idLang): string
    {
        $link = \Context::getContext()->link;

        switch ($entityType) {
            case 'product':
                return $link->getProductLink($idEntity, null, null, null, $idLang);
            case 'category':
                return $link->getCategoryLink($idEntity, null, $idLang);
            case 'manufacturer':
                return $link->getManufacturerLink($idEntity, null, $idLang);
            case 'supplier':
                return $link->getSupplierLink($idEntity, null, $idLang);
            case 'cms':
                return $link->getCMSLink($idEntity, null, $idLang);
            default:
                return '';
        }
    }
}
