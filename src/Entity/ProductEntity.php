<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\TextNormalizer;

class ProductEntity implements EntityInterface
{
    public function getType(): string
    {
        return 'product';
    }

    public function getController(): string
    {
        return 'product';
    }

    public function getIdParam(): string
    {
        return 'id_product';
    }

    public function getObjectClass(): string
    {
        return 'Product';
    }

    public function getLabel(): string
    {
        return 'Produit';
    }

    public function getAvailableTags(): array
    {
        return [
            '{name}' => 'Nom du produit',
            '{description_short}' => 'Description courte',
            '{price}' => 'Prix TTC formaté',
            '{manufacturer}' => 'Nom du fabricant',
            '{category}' => 'Catégorie par défaut',
            '{reference}' => 'Référence produit',
            '{ean13}' => 'Code EAN13',
            '{shop_name}' => 'Nom de la boutique',
        ];
    }

    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array
    {
        $product = new \Product($idEntity, false, $idLang, $idShop);
        if (!\Validate::isLoadedObject($product)) {
            return [];
        }

        $categoryName = '';
        if ((int) $product->id_category_default) {
            $category = new \Category((int) $product->id_category_default, $idLang);
            $categoryName = $category->name ?: '';
        }

        $manufacturerName = '';
        if ((int) $product->id_manufacturer) {
            $manufacturerName = \Manufacturer::getNameById((int) $product->id_manufacturer) ?: '';
        }

        $price = \Product::getPriceStatic(
            $idEntity, true, null, 2, null, false, true, 1,
            false, null, null, null, $null, true, true, \Context::getContext()
        );

        $locale = \Context::getContext()->getCurrentLocale();
        $currencyCode = \Context::getContext()->currency->iso_code;

        return [
            '{name}' => (string) $product->name,
            '{description_short}' => TextNormalizer::truncate(strip_tags((string) $product->description_short), 160),
            '{price}' => $locale->formatPrice($price, $currencyCode),
            '{manufacturer}' => $manufacturerName,
            '{category}' => $categoryName,
            '{reference}' => (string) $product->reference,
            '{ean13}' => (string) $product->ean13,
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];
    }

    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $product = new \Product($idEntity, false, $idLang, $idShop);
        return empty(trim((string) $product->meta_title));
    }

    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $product = new \Product($idEntity, false, $idLang, $idShop);
        return empty(trim((string) $product->meta_description));
    }
}
