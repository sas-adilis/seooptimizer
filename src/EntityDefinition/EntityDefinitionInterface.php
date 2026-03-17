<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

interface EntityDefinitionInterface
{
    /**
     * @param $id_primary
     * @param $id_lang
     * @param $id_shop
     *
     * @return string
     *
     * Generate entity link for report
     */
    public function getLink($id_primary, $id_lang = null, $id_shop = null): string;

    /**
     * @return string
     *
     * Primary field name
     */
    public function getPrimaryKey(): string;

    /**
     * @return string
     *
     * Table name
     */
    public function getTable(): string;

    /**
     * @return string
     *
     * Entity name
     */
    public function getKey(): string;

    /**
     * @return string Entity
     */
    public function getTitle(): string;

    /**
     * @return bool
     *
     * Determine
     */
    public function haveIdShopField(): bool;

    /**
     * @return bool
     *
     * Determine
     */
    public function haveActiveField(): bool;

    /**
     * @return bool
     *
     * Determine
     */
    public function isEnabled(): bool;

    /**
     * @return array
     *
     * Fields in table for content analyze
     */
    public function getFields(): array;

    public function updateRow(array $row): bool;
}
