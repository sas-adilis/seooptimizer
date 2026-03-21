<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CmsEntity implements EntityInterface
{
    public function getType(): string
    {
        return 'cms';
    }

    public function getController(): string
    {
        return 'cms';
    }

    public function getIdParam(): string
    {
        return 'id_cms';
    }

    public function getObjectClass(): string
    {
        return 'CMS';
    }

    public function getLabel(): string
    {
        return 'Page CMS';
    }

    public function getAvailableTags(): array
    {
        return [
            '{title}' => 'Titre de la page CMS',
            '{shop_name}' => 'Nom de la boutique',
        ];
    }

    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array
    {
        $cms = new \CMS($idEntity, $idLang);
        if (!\Validate::isLoadedObject($cms)) {
            return [];
        }

        return [
            '{title}' => (string) ($cms->meta_title ?: $cms->head_seo_title),
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];
    }

    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $cms = new \CMS($idEntity, $idLang);
        return empty(trim((string) $cms->meta_title));
    }

    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool
    {
        $cms = new \CMS($idEntity, $idLang);
        return empty(trim((string) $cms->meta_description));
    }
}
