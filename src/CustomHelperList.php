<?php

namespace Adilis\SeoOptimizer;

class CustomHelperList extends \HelperList
{
    public $beforeGetList;

    public function __construct()
    {
        parent::__construct();
        $this->actions = ['edit', 'delete'];
        $this->token = \Tools::getAdminTokenLite('AdminModules');
        $this->currentIndex = \AdminController::$currentIndex . '&configure=seooptimizer';
        $this->no_link = true;
        $this->shopLinkType = '';
        $this->simple_header = false;
        $this->show_toolbar = true;
        $this->title = $this->l('Table');
        $this->title_icon = 'icon-list';
        $this->_default_pagination = 20;
        $this->_defaultOrderBy = 'id_seooptimizer_redirect';
        $this->_defaultOrderWay = 'DESC';
        $this->tpl_vars = [
            'link' => \Context::getContext()->link,
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => 'Supprimer la sélection',
                'confirm' => 'Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?',
            ],
        ];
    }

    public function setIdentifier($identifier)
    {
        $this->id = $identifier;
        $this->identifier = 'id_' . $identifier;
        $this->table = $identifier;
        $this->_defaultOrderBy = $this->identifier;
        $this->_defaultOrderWay = 'DESC';

        $this->toolbar_btn = [
            'export' => [
                'href' => $this->currentIndex . '&export' . $this->table . '&token=' . $this->token,
                'desc' => 'Export',
            ],
        ];
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function render(array $fields_list)
    {
        if (!$this->identifier) {
            throw new \PrestaShopException('Identifier is not set');
        }

        $this->handleProcessResetFilters();
        $this->handleProcessFilter();

        $this->fields_list = $fields_list;
        $this->listTotal = $this->getListTotal();

        return $this->generateList($this->getList(), $fields_list);
    }

    /**
     * @throws \PrestaShopException
     */
    protected function checkSqlLimit($limit)
    {
        if (empty($limit)) {
            if (
                isset(\Context::getContext()->cookie->{$this->id . '_pagination'})
                && \Context::getContext()->cookie->{$this->id . '_pagination'}
            ) {
                $limit = \Context::getContext()->cookie->{$this->id . '_pagination'};
            } else {
                $limit = $this->_default_pagination;
            }
        }

        $limit = (int) \Tools::getValue($this->id . '_pagination', $limit);
        if (in_array($limit, $this->_pagination) && $limit != $this->_default_pagination) {
            \Context::getContext()->cookie->{$this->id . '_pagination'} = $limit;
        } else {
            unset(\Context::getContext()->cookie->{$this->id . '_pagination'});
        }

        if (!is_numeric($limit)) {
            throw new \PrestaShopException('Invalid limit. It should be a numeric.');
        }

        return $limit;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getList(
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null
    ) {
        $context = \Context::getContext();
        $id_lang = $context->language->id;
        if (!is_numeric($start) || !\Validate::isUnsignedId($id_lang)) {
            throw new \PrestaShopException('get list params is not valid');
        }

        $limit = $this->checkSqlLimit($limit);

        /* Determine offset from current page */
        $start = 0;

        if ((int) \Tools::getValue('submitFilter' . $this->id)) {
            $start = ((int) \Tools::getValue('submitFilter' . $this->id) - 1) * $limit;
        } elseif (
            empty($start)
            && isset($context->cookie->{$this->id . '_start'})
            && \Tools::isSubmit('export' . $this->table)
        ) {
            $start = $context->cookie->{$this->id . '_start'};
        }

        if ($start) {
            $context->cookie->{$this->id . '_start'} = $start;
        } elseif (isset($context->cookie->{$this->id . '_start'})) {
            unset($context->cookie->{$this->id . '_start'});
        }

        $filters = $this->context->cookie->getFamily($this->getCookiePrefix() . $this->id . 'Filter_');

        $query = new \DbQuery();
        $query->select('*');
        $query->from($this->table, 'a');

        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if (
                $value != null
                && strpos($key, $this->getCookiePrefix() . $this->id . 'Filter_') === 0
            ) {
                $key = str_replace($this->getCookiePrefix() . $this->id . 'Filter_', '', $key);
                $tmp_tab = explode('!', $key);
                $filter = count($tmp_tab) > 1 ? $tmp_tab[1] : $tmp_tab[0];

                if (isset($this->fields_list[$filter])) {
                    $field = $this->fields_list[$filter];
                    if (!isset($field['type'])) {
                        $field['type'] = 'text';
                    }

                    if (($field['type'] == 'date' || $field['type'] == 'datetime') && is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    $key = isset($tmp_tab[1]) ? $tmp_tab[0] . '.`' . $tmp_tab[1] . '`' : '`' . $tmp_tab[0] . '`';

                    if (is_array($value)) {
                        if (isset($value[0]) && !empty($value[0])) {
                            if (!\Validate::isDate($value[0])) {
                                // todo: manage error
                            } else {
                                $query->where(pSQL($key) . ' >= \'' . \pSQL(\Tools::dateFrom($value[0])) . '\'');
                            }
                        }

                        if (isset($value[1]) && !empty($value[1])) {
                            if (!\Validate::isDate($value[1])) {
                                // todo: manage error
                            } else {
                                $query->where(pSQL($key) . ' <= \'' . \pSQL(\Tools::dateTo($value[1])) . '\'');
                            }
                        }
                    } else {
                        $check_key = ($key == $this->identifier || $key == '`' . $this->identifier . '`');
                        $alias = 'a';

                        if ($field['type'] == 'int' || $field['type'] == 'bool') {
                            $query->where((($check_key || $key == '`active`') ? $alias . '.' : '') . pSQL($key) . ' = ' . (int) $value);
                        } elseif ($field['type'] == 'decimal') {
                            $query->where(($check_key ? $alias . '.' : '') . pSQL($key) . ' = ' . (float) $value);
                        } elseif ($field['type'] == 'select') {
                            $query->where(($check_key ? $alias . '.' : '') . pSQL($key) . ' = \'' . \pSQL($value) . '\'');
                        } elseif ($field['type'] == 'price') {
                            $value = (float) str_replace(',', '.', $value);
                            $query->where(($check_key ? $alias . '.' : '') . pSQL($key) . ' = ' . \pSQL(trim($value)));
                        } else {
                            $query->where(($check_key ? $alias . '.' : '') . pSQL($key) . ' LIKE \'%' . \pSQL(trim($value)) . '%\'');
                        }
                    }
                }
            }
        }

        $query->limit($limit, $start);

        $order_by = $this->checkOrderBy($order_by);
        $order_way = $this->checkOrderDirection($order_way);

        $query->orderBy($order_by . ' ' . $order_way);

        if ($this->beforeGetList instanceof \Closure) {
            ($this->beforeGetList)($query);
        }

        $this->sql = $query->build();

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    /**
     * @throws \PrestaShopException
     */
    protected function checkOrderBy($orderBy)
    {
        if (empty($orderBy)) {
            $prefix = $this->getCookiePrefix();

            if ($this->context->cookie->{$prefix . $this->id . 'Orderby'}) {
                $orderBy = $this->context->cookie->{$prefix . $this->id . 'Orderby'};
            } else {
                $orderBy = $this->_defaultOrderBy;
            }
        }

        /* Check params validity */
        if (!\Validate::isOrderBy($orderBy)) {
            throw new \PrestaShopException('Invalid "order by" clause.');
        }

        if (!isset($this->fields_list[$orderBy]['order_key']) && isset($this->fields_list[$orderBy]['filter_key'])) {
            $this->fields_list[$orderBy]['order_key'] = $this->fields_list[$orderBy]['filter_key'];
        }

        if (isset($this->fields_list[$orderBy]['order_key'])) {
            $orderBy = $this->fields_list[$orderBy]['order_key'];
        }

        if (preg_match('/[.!]/', $orderBy)) {
            $orderBySplit = preg_split('/[.!]/', $orderBy);
            $orderBy = pSQL($orderBySplit[0]) . '.`' . pSQL($orderBySplit[1]) . '`';
        } elseif ($orderBy) {
            $orderBy = pSQL($orderBy);
        }

        return $orderBy;
    }

    protected function checkOrderDirection($orderDirection)
    {
        $prefix = $this->getCookiePrefix();
        if (empty($orderDirection)) {
            if ($this->context->cookie->{$prefix . $this->id . 'Orderway'}) {
                $orderDirection = $this->context->cookie->{$prefix . $this->id . 'Orderway'};
            } else {
                $orderDirection = $this->_defaultOrderWay;
            }
        }

        if (!\Validate::isOrderWay($orderDirection)) {
            throw new \PrestaShopException('Invalid order direction.');
        }

        return pSQL(\Tools::strtoupper($orderDirection));
    }

    public function handleProcessFilter()
    {
        if (
            \Tools::isSubmit('submitFilter' . $this->id)
            || $this->context->cookie->{'submitFilter' . $this->id} !== false
            || \Tools::getValue($this->id . 'Orderby')
            || \Tools::getValue($this->id . 'Orderway')) {
            $prefix = $this->getCookiePrefix();
            foreach ($_POST as $key => $value) {
                if ($value === '') {
                    unset($this->context->cookie->{$prefix . $key});
                } elseif (stripos($key, $this->id . 'Filter_') === 0) {
                    $this->context->cookie->{$prefix . $key} = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : json_encode($value);
                }
            }

            foreach ($_GET as $key => $value) {
                if (stripos($key, $this->id . 'Filter_') === 0) {
                    $this->context->cookie->{$prefix . $key} = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $this->context->cookie->$key = !is_array($value) ? $value : json_encode($value);
                }

                if (stripos($key, $this->id . 'Orderby') === 0 && \Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $this->_defaultOrderBy) {
                        unset($this->context->cookie->{$prefix . $key});
                    } else {
                        $this->context->cookie->{$prefix . $key} = $value;
                    }
                } elseif (stripos($key, $this->id . 'Orderway') === 0 && \Validate::isOrderWay($value)) {
                    if ($value === '' || $value == $this->_defaultOrderWay) {
                        unset($this->context->cookie->{$prefix . $key});
                    } else {
                        $this->context->cookie->{$prefix . $key} = $value;
                    }
                }
            }
        }
    }

    private function getListTotal()
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from($this->table, 'a');

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    private function handleProcessResetFilters()
    {
        if (\Tools::isSubmit('submitReset' . $this->id)) {
            $prefix = $this->getCookiePrefix();
            $filters = $this->context->cookie->getFamily($prefix . $this->id . 'Filter_');
            foreach ($filters as $cookie_key => $filter) {
                if (strpos($cookie_key, $this->getCookiePrefix() . $this->id . 'Filter_') === 0) {
                    $key = str_replace($this->getCookiePrefix() . $this->id . 'Filter_', '', $key);
                    if (is_array($this->fields_list) && array_key_exists($key, $this->fields_list)) {
                        $this->context->cookie->$cookie_key = null;
                    }
                    unset($this->context->cookie->$cookie_key);
                }
            }

            if (isset($this->context->cookie->{'submitFilter' . $this->id})) {
                unset($this->context->cookie->{'submitFilter' . $this->id});
            }
            if (isset($this->context->cookie->{$prefix . $this->id . 'Orderby'})) {
                unset($this->context->cookie->{$prefix . $this->id . 'Orderby'});
            }
            if (isset($this->context->cookie->{$prefix . $this->id . 'Orderway'})) {
                unset($this->context->cookie->{$prefix . $this->id . 'Orderway'});
            }

            $this->context->cookie->write();
            \Tools::redirectAdmin($this->currentIndex . '&token=' . $this->token);
        }
    }

    private function getCookiePrefix()
    {
        return 'custom_helper_';
    }
}
