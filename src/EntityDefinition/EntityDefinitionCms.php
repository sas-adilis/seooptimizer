<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionCms extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'cms';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'CMS';
    }

    public function getIcon(): string
    {
        return 'icon-file-text';
    }

    /**
     * @return array<string>
     */
    public function getFields(): array
    {
        return [
            'content' => Constants::HTML_FIELD,
            'meta_title' => Constants::TITLE_FIELD,
            'meta_description' => Constants::META_TITLE_FIELD,
        ];
    }

    public function getLink($id_primary, $id_lang = null, $id_shop = null): string
    {
        return \Context::getContext()->link->getCMSLink(
            $id_primary,
            null,
            null,
            $id_lang,
            $id_shop
        );
    }
}
