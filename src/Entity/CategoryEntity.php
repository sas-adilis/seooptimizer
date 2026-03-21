<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\TextNormalizer;

class CategoryEntity implements EntityInterface
{
    public function getType(): string
    {
        return 'category';
    }

    public function getController(): string
    {
        return 'category';
    }

    public function getIdParam(): string
    {
        return 'id_category';
    }

    public function getObjectClass(): string
    {
        return 'Category';
    }

    public function getLabel(): string
    {
        return 'Catégorie';
    }

    public function getAvailableTags(): array
    {
        return [
            '{name}' => 'Nom de la catégorie',
            '{description}' => 'Description (texte brut, tronqué)',
            '{parent_category}' => 'Catégorie parente',
            '{nb_products}' => 'Nombre de produits',
            '{shop_name}' => 'Nom de la boutique',
        ];
    }

    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array
    {
        $category = new \Category($idEntity, $idLang, $idShop);
        if (!\Validate::isLoadedObject($category)) {
            return [];
        }

        $parentName = '';
        if ((int) $category->id_parent > 0) {
            $parent = new \Category((int) $category->id_parent, $idLang);
            $parentName = $parent->name ?: '';
        }

        $nbProducts = $category->getProducts($idLang, 1, 1, null, null, true);

        return [
            '{name}' => (string) $category->name,
            '{description}' => TextNormalizer::truncate(strip_tags((string) $category->description), 160),
            '{parent_category}' => $parentName,
            '{nb_products}' => (string) (int) $nbProducts,
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];
    }

    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $category = new \Category($idEntity, $idLang, $idShop);
        return empty(trim((string) $category->meta_title));
    }

    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $category = new \Category($idEntity, $idLang, $idShop);
        return empty(trim((string) $category->meta_description));
    }
}
