<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionCategory extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'category';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'Category';
    }

    public function getIcon(): string
    {
        return 'icon-th-large';
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return [
            'description' => Constants::HTML_FIELD,
            'meta_title' => Constants::TITLE_FIELD,
            'meta_description' => Constants::META_TITLE_FIELD,
        ];
    }

    public function getLink($id_primary, $id_lang = null, $id_shop = null): string
    {
        return \Context::getContext()->link->getCategoryLink(
            $id_primary,
            null,
            $id_lang,
            null,
            $id_shop
        );
    }
}
