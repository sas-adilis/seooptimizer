<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\TextNormalizer;

class SupplierEntity implements EntityInterface
{
    public function getType(): string
    {
        return 'supplier';
    }

    public function getController(): string
    {
        return 'supplier';
    }

    public function getIdParam(): string
    {
        return 'id_supplier';
    }

    public function getObjectClass(): string
    {
        return 'Supplier';
    }

    public function getLabel(): string
    {
        return 'Fournisseur';
    }

    public function getAvailableTags(): array
    {
        return [
            '{name}' => 'Nom du fournisseur',
            '{description}' => 'Description (texte brut, tronqué)',
            '{shop_name}' => 'Nom de la boutique',
        ];
    }

    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array
    {
        $supplier = new \Supplier($idEntity, $idLang);
        if (!\Validate::isLoadedObject($supplier)) {
            return [];
        }

        return [
            '{name}' => (string) $supplier->name,
            '{description}' => TextNormalizer::truncate(strip_tags((string) $supplier->description), 160),
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];
    }

    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $supplier = new \Supplier($idEntity, $idLang);
        return empty(trim((string) $supplier->meta_title));
    }

    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $supplier = new \Supplier($idEntity, $idLang);
        return empty(trim((string) $supplier->meta_description));
    }
}
