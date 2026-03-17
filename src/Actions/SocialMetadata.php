<?php

namespace Adilis\SeoOptimizer\Actions;

class SocialMetadata
{
    public function run()
    {
        if (!\Configuration::get('SEOO_ENABLE_SOCIAL_METADATA')) {
            return '';
        }

        $context = \Context::getContext();
        $controller = \Dispatcher::getInstance()->getController();
        $type = 'website';
        $image_width = $image_height = '';

        // todo: make image type configurable
        switch ($controller) {
            case 'product':
                $product = $context->smarty->getTemplateVars('product');
                $image = $product['cover']['bySize']['cart_default']['url'] ?? '';
                $image_width = $product['cover']['bySize']['cart_default']['width'] ?? '';
                $image_height = $product['cover']['bySize']['cart_default']['height'] ?? '';
                $type = 'product';
                break;
            case 'category':
                $category = $context->smarty->getTemplateVars('category');
                $image = $category['image']['bySize']['category_default']['url'] ?? '';
                $image_width = $category['image']['bySize']['category_default']['width'] ?? '';
                $image_height = $category['image']['bySize']['category_default']['height'] ?? '';
                break;
            case 'cms':
                $type = 'article';
                break;
            case 'manufacturer':
                $id_manufacturer = (int) \Tools::getValue('id_manufacturer');
                $image = $context->link->getManufacturerImageLink($id_manufacturer, 'small_default');
                break;
            case 'supplier':
                $id_supplier = (int) \Tools::getValue('id_supplier');
                $image = $context->link->getSupplierImageLink($id_supplier, 'small_default');
                break;
            default:
                $image = \Configuration::hasKey('PS_LOGO') ? _PS_IMG_ . \Configuration::get('PS_LOGO') : '';
                break;
        }

        if (empty($image)) {
            $image = \Configuration::hasKey('PS_LOGO') ? _PS_IMG_ . \Configuration::get('PS_LOGO') : '';
        }

        if (!empty($image) && empty($image_width)) {
            $size = @getimagesize($image);
            if ($size !== false) {
                $image_width = $size[0];
                $image_height = $size[1];
            }
        }

        $context->smarty->assign([
            'controller_name' => $controller,
            'seoo_social_metadata_type' => $type,
            'seoo_social_metadata_image' => $image,
            'seoo_social_metadata_image_width' => $image_width,
            'seoo_social_metadata_image_height' => $image_height,
        ]);

        return $context->smarty->fetch('module:seooptimizer/views/templates/hook/social_metadata.tpl');
    }
}
