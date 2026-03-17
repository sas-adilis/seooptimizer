<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

class EntityDefinitionCmsCategory extends EntityDefinitionAbstract implements EntityDefinitionInterface
{
    public function getKey(): string
    {
        return 'cms_category';
    }

    public function getTitle(): string
    {
        /* TODO: Translation system */
        return 'CMS category';
    }

    public function getIcon(): string
    {
        return 'icon-folder';
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
        return \Context::getContext()->link->getCMSCategoryLink(
            $id_primary,
            null,
            $id_lang,
            $id_shop
        );
    }
}
