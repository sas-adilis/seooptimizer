<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionProduct extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'product';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'Products';
    }

    public function getIcon(): string
    {
        return 'icon-tag';
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return [
            'description' => Constants::HTML_FIELD,
            'description_short' => Constants::HTML_FIELD,
            'meta_title' => Constants::TITLE_FIELD,
            'meta_description' => Constants::META_TITLE_FIELD,
        ];
    }

    public function haveActiveField(): bool
    {
        return true;
    }

    public function getLink($id_primary, $id_lang = null, $id_shop = null): string
    {
        return \Context::getContext()->link->getProductLink(
            $id_primary,
            null,
            null,
            null,
            $id_lang,
            $id_shop
        );
    }
}
