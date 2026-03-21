<?php

namespace Adilis\SeoOptimizer\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

interface EntityInterface
{
    /**
     * Unique type identifier (e.g. 'product', 'category').
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Front controller name for this entity (e.g. 'product', 'category', 'cms').
     *
     * @return string
     */
    public function getController(): string;

    /**
     * URL parameter name for the entity ID (e.g. 'id_product').
     *
     * @return string
     */
    public function getIdParam(): string;

    /**
     * PrestaShop ObjectModel class name (e.g. 'Product', 'Category').
     *
     * @return string
     */
    public function getObjectClass(): string;

    /**
     * Human-readable label for back-office display.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Available dynamic tags for meta templates.
     * Returns tag => description.
     *
     * @return array<string, string>
     */
    public function getAvailableTags(): array;

    /**
     * Resolve tag values for a given entity ID.
     *
     * @param int $idEntity
     * @param int $idLang
     * @param int $idShop
     * @return array<string, string> tag => resolved value
     */
    public function resolveTagValues(int $idEntity, int $idLang, int $idShop): array;

    /**
     * Check if the entity's meta title is empty.
     *
     * @param int $idEntity
     * @param int $idLang
     * @param int $idShop
     * @return bool
     */
    public function isMetaTitleEmpty(int $idEntity, int $idLang, int $idShop): bool;

    /**
     * Check if the entity's meta description is empty.
     *
     * @param int $idEntity
     * @param int $idLang
     * @param int $idShop
     * @return bool
     */
    public function isMetaDescriptionEmpty(int $idEntity, int $idLang, int $idShop): bool;
}
