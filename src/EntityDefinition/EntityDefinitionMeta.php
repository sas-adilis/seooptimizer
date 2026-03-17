<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionMeta extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'meta';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'Page';
    }

    public function getIcon(): string
    {
        return 'icon-globe';
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return [
            'title' => Constants::TITLE_FIELD,
            'description' => Constants::META_TITLE_FIELD,
        ];
    }

    public function getLink($id_primary, $id_lang = null, $id_shop = null): string
    {
        return \Context::getContext()->link->getPageLink(
            $id_primary,
            null,
            $id_lang,
            null,
            false,
            $id_shop
        );
    }
}
