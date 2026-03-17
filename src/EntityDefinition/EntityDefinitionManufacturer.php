<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionManufacturer extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'manufacturer';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'Manufacturer';
    }

    public function getIcon(): string
    {
        return 'icon-building';
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return [
            'short_description' => Constants::HTML_FIELD,
            'description' => Constants::HTML_FIELD,
            'meta_title' => Constants::TITLE_FIELD,
            'meta_description' => Constants::META_TITLE_FIELD,
        ];
    }

    public function haveIdShopField(): bool
    {
        return false;
    }

    public function getLink($id_primary, $id_lang = null, $id_shop = null): string
    {
        return \Context::getContext()->link->getManufacturerLink(
            $id_primary,
            null,
            $id_lang,
            $id_shop
        );
    }
}
