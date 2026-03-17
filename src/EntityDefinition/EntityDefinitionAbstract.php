<?php

namespace Adilis\SeoOptimizer\EntityDefinition;

use Adilis\SeoOptimizer\Constants;

abstract class EntityDefinitionAbstract implements EntityDefinitionInterface
{
    private $progress = '--';
    private $results_count = 0;
    private $fixed_count = 0;
    private $progress_percentage = 0;

    public function getPrimaryKey(): string
    {
        return 'id_' . $this->getKey();
    }

    public function getTable(): string
    {
        return $this->getKey() . '_lang';
    }

    public function haveIdShopField(): bool
    {
        return true;
    }

    public function haveActiveField(): bool
    {
        return false;
    }

    public function getCount()
    {
        $cache_key = 'definition_count_' . $this->getKey();
        if (\Cache::isStored($cache_key)) {
            return \Cache::retrieve($cache_key);
        }

        $id_lang = (int) \Context::getContext()->language->id;
        $id_shop = (int) \Context::getContext()->shop->id;

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from(pSQL($this->getTable()), 'l');
        if (strpos($this->getTable(), '_lang')) {
            $query->where('l.id_lang = ' . (int) $id_lang);
        }

        if ($this->haveIdShopField()) {
            $query->where('l.id_shop = ' . (int) $id_shop);
        }

        if ($this->haveActiveField()) {
            // $query->where('active = 1');
        }

        if (method_exists($this, 'onBeforeGetCount')) {
            $this->onBeforeGetCount($query);
        }

        $count = \Db::getInstance()->getValue($query);

        \Cache::store($cache_key, $count);

        return $count;
    }

    /**
     * @throws \PrestaShopDatabaseException
     */
    public function getRows($offset = 0, $limit = 100)
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $id_shop = (int) \Context::getContext()->shop->id;

        $query = new \DbQuery();
        $query->from(pSQL($this->getTable()), 'l');
        $query->select('l.' . pSQL($this->getPrimaryKey()));
        $query->select('l.id_lang');

        foreach (array_keys($this->getFields()) as $field) {
            $query->select('l.' . pSQL($field));
        }

        if (strpos($this->getTable(), '_lang')) {
            $query->where('l.id_lang = ' . (int) $id_lang);
        }

        if ($this->haveIdShopField()) {
            $query->where('l.id_shop = ' . (int) $id_shop);
            $query->select('l.id_shop');
        }

        if ($this->haveActiveField()) {
            // $query->where('active = 1');
        }

        $query->limit($limit, $offset);

        if (method_exists($this, 'onBeforeGetRows')) {
            $this->onBeforeGetRows($query);
        }

        return \Db::getInstance()->executeS($query);
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestashopException
     */
    public function updateRow(array $row): bool
    {
        $id_lang = (int) \Context::getContext()->language->id;
        $id_shop = (int) \Context::getContext()->shop->id;

        $datas_to_update = [];
        foreach ($this->getFields() as $field => $field_type) {
            $datas_to_update[$field] = pSQL($row[$field], $field_type === Constants::HTML_FIELD);
        }

        $where = [];

        if (!isset($row[$this->getPrimaryKey()])) {
            throw new \PrestashopException('Primary key not found in row');
        }
        $where[] = $this->getPrimaryKey() . ' = ' . (int) $row[$this->getPrimaryKey()];

        if (strpos($this->getTable(), '_lang')) {
            $where[] = 'id_lang = ' . (int) $id_lang;
        }

        if ($this->haveIdShopField()) {
            $where[] = 'id_shop = ' . (int) $id_shop;
        }

        return \Db::getInstance()->update(
            $this->getTable(),
            $datas_to_update,
            implode(' AND ', $where),
            1
        );
    }

    public function getProgress(): string
    {
        return $this->progress;
    }

    public function setProgress(string $progress): void
    {
        $this->progress = $progress;
    }

    public function getResultsCount(): int
    {
        return $this->results_count;
    }

    public function setResultsCount(int $results_count): void
    {
        $this->results_count = $results_count;
    }

    public function getFixedCount(): int
    {
        return $this->fixed_count;
    }

    public function setFixedCount(int $fixed_count): void
    {
        $this->fixed_count = $fixed_count;
    }

    public function getIcon(): string
    {
        return 'icon-file-text';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getProgressPercentage(): int
    {
        return $this->progress_percentage;
    }

    public function setProgressPercentage(int $progress_percentage)
    {
        $this->progress_percentage = $progress_percentage;
    }
}
