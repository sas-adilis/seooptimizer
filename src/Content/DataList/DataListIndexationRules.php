<?php

namespace Adilis\SeoOptimizer\Content\DataList;

use Adilis\SeoOptimizer\Constants;

class DataListIndexationRules extends DataList implements DataListInterface
{
    public function getTable(): string
    {
        return 'seooptimizer_indexation_rule';
    }

    public function getFields(): array
    {
        return [
            'id_seooptimizer_indexation_rule' => [
                'title' => 'ID',
                'type' => 'int',
                'orderby' => true,
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'type' => [
                'title' => $this->l('Type'),
                'orderby' => true,
                'callback_object' => $this,
                'callback' => 'displayType',
            ],
            'term' => [
                'title' => $this->l('Term or  URL'),
                'orderby' => true,
            ],
            'date_add' => [
                'title' => $this->l('Date add'),
                'type' => 'datetime',
                'orderby' => true,
            ],
            'date_upd' => [
                'title' => $this->l('Date update'),
                'type' => 'datetime',
                'orderby' => true,
            ],
        ];
    }

    public function displayType($echo)
    {
        switch ($echo) {
            case Constants::RULE_TYPE_IS:
                return $this->l('URL is');
            case Constants::RULE_TYPE_CONTAINS:
                return $this->l('URL contains');
            case Constants::RULE_TYPE_STARTS_WITH:
                return $this->l('URL start with');
        }
    }

    public function getTitle(): string
    {
        return $this->l('Indexation rules');
    }

    public function getIcon(): string
    {
        return 'icon-database';
    }
}
