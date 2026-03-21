<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils\TextNormalizer;

class ManufacturerEntity implements EntityInterface
{
    public function getType(): string
    {
        return 'manufacturer';
    }

    public function getController(): string
    {
        return 'manufacturer';
    }

    public function getIdParam(): string
    {
        return 'id_manufacturer';
    }

    public function getObjectClass(): string
    {
        return 'Manufacturer';
    }

    public function getLabel(): string
    {
        return 'Fabricant';
    }

    public function getAvailableTags(): array
    {
        return [
            '{name}' => 'Nom du fabricant',
            '{description}' => 'Description (texte brut, tronqué)',
            '{shop_name}' => 'Nom de la boutique',
        ];
    }

    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array
    {
        $manufacturer = new \Manufacturer($idEntity, $idLang);
        if (!\Validate::isLoadedObject($manufacturer)) {
            return [];
        }

        return [
            '{name}' => (string) $manufacturer->name,
            '{description}' => TextNormalizer::truncate(strip_tags((string) $manufacturer->description), 160),
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];
    }

    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $manufacturer = new \Manufacturer($idEntity, $idLang);
        return empty(trim((string) $manufacturer->meta_title));
    }

    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $manufacturer = new \Manufacturer($idEntity, $idLang);
        return empty(trim((string) $manufacturer->meta_description));
    }
}
