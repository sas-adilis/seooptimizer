<?php

namespace Adilis\SeoOptimizer\Content\DataList;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Utils;

abstract class DataList implements DataListInterface
{
    /**
     * @var \HelperList
     */
    public $helper;

    private $fields_list = [];

    public function getKey($to_underscore_case = false): string
    {
        $class_name = (new \ReflectionClass($this))->getShortName();
        if ($to_underscore_case) {
            return \Tools::toUnderscoreCase($class_name);
        }

        return $class_name;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function process()
    {
        if (
            \Tools::getIsset('submitBulkdelete' . $this->getKey(true))
            && method_exists($this, 'postProcessBulkDelete')
        ) {
            $this->postProcessBulkDelete();
        }

        if (
            \Tools::getIsset('delete' . $this->getKey(true))
            && method_exists($this, 'postProcessDelete')
        ) {
            $this->postProcessDelete();
        }

        if (
            \Tools::getIsset('export' . $this->getKey(true))
            && method_exists($this, 'postProcessExport')
        ) {
            $this->postProcessExport();
        }

        \Context::getContext()->smarty->assign($this->getKey(true), $this->renderList($this->getFields()));
    }

    /**
     * @throws \PrestaShopException
     */
    protected function checkSqlLimit($limit)
    {
        if (empty($limit)) {
            if (
                isset(\Context::getContext()->cookie->{$this->helper->id . '_pagination'})
                && \Context::getContext()->cookie->{$this->helper->id . '_pagination'}
            ) {
                $limit = \Context::getContext()->cookie->{$this->helper->id . '_pagination'};
            } else {
                $limit = $this->helper->_default_pagination;
            }
        }

        $limit = (int) \Tools::getValue($this->helper->id . '_pagination', $limit);
        if (in_array($limit, $this->helper->_pagination) && $limit != $this->helper->_default_pagination) {
            \Context::getContext()->cookie->{$this->helper->id . '_pagination'} = $limit;
        } else {
            unset(\Context::getContext()->cookie->{$this->helper->id . '_pagination'});
        }

        if (!is_numeric($limit)) {
            throw new \PrestaShopException('Invalid limit. It should be a numeric.');
        }

        return $limit;
    }

    public function getContent()
    {
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function renderList(array $fields_list): string
    {
        $context = \Context::getContext();

        $this->helper = new \HelperList();
        $this->helper->actions = ['edit', 'delete'];
        $this->helper->token = \Tools::getAdminTokenLite('AdminModules');
        $this->helper->currentIndex = \AdminController::$currentIndex . '&configure=seooptimizer';
        $this->helper->no_link = true;
        $this->helper->shopLinkType = '';
        $this->helper->simple_header = true;
        $this->helper->show_toolbar = false;
        $this->helper->_default_pagination = 20;
        $this->helper->tpl_vars = [
            'link' => $context->link,
        ];

        $this->helper->bulk_actions = [
            'delete' => [
                'text' => 'Supprimer la sélection',
                'confirm' => 'Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?',
            ],
        ];
        $this->helper->id = $this->getKey(true);
        $this->helper->identifier = 'id_' . $this->getTable();
        $this->helper->table = $this->helper->id;
        $this->helper->_defaultOrderBy = $this->helper->identifier;
        $this->helper->_defaultOrderWay = 'DESC';

        $this->helper->toolbar_btn = [
            'export' => [
                'href' => $this->helper->currentIndex . '&export' . $this->getKey(true) . '&token=' . $this->helper->token,
                'desc' => 'Export',
            ],
        ];

        $this->fields_list = $this->getFields();

        $this->handleProcessResetFilters();
        $this->handleProcessFilter();

        $this->helper->listTotal = $this->getListTotal();

        if (method_exists($this, 'onBeforeGenerateList')) {
            $this->onBeforeGenerateList();
        }

        return $this->helper->generateList($this->getList(), $this->fields_list);
    }

    public function postProcessDelete($id_primary = null, $redirect = true)
    {
        if (!$id_primary) {
            $id_primary = \Tools::getValue('id_' . $this->getTable());
        }
        if (!\Db::getInstance()->delete($this->getTable(), 'id_' . $this->getTable() . ' = "' . (int) $id_primary . '"', 1)) {
            \Context::getContext()->controller->errors[] = sprintf(
                $this->l('An error occurred while deleting the object with ID %s'),
                $id_primary
            );
        }
        if ($redirect) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(1));
        }
    }

    public function postProcessBulkDelete()
    {
        $identifiers = \Tools::getValue($this->getKey(true) . 'Box');
        $context = \Context::getContext();
        if (is_array($identifiers) && !empty($identifiers)) {
            foreach ($identifiers as $identifier) {
                $this->postProcessDelete($identifier, false);
            }
        } else {
            $context->controller->errors[] = $this->l('You must select at least one element to delete.');
        }

        if (!count($context->controller->errors)) {
            \Tools::redirectAdmin(Utils::getConfigFormUrl(2));
        }
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function postProcessExport()
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }

        $datas = $this->getList(null, null, 0, false);
        if (!count($datas)) {
            return;
        }

        $filename = sprintf(
            'export-%s-%s.csv',
            $this->getKey(true),
            date('Ymd_His')
        );

        header('Cache-Control: no-cahe, must-revalidate');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $df = fopen('php://output', 'w+');
        fputs($df, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($df, array_column($this->getFields(), 'title'), ';');

        foreach ($datas as $data) {
            $current_row = [];
            foreach (array_keys($this->getFields()) as $key) {
                $current_row[] = $data[$key] ?? '';
            }
            fputcsv($df, $current_row, ';');
        }

        fclose($df);
        exit;
    }

    protected function l($string)
    {
        // todo: implement translation
        return $string;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    protected function getList(
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

        if ((int) \Tools::getValue('submitFilter' . $this->helper->id)) {
            $start = ((int) \Tools::getValue('submitFilter' . $this->helper->id) - 1) * $limit;
        } elseif (
            empty($start)
            && isset($context->cookie->{$this->helper->id . '_start'})
            && \Tools::isSubmit('export' . $this->getTable())
        ) {
            $start = $context->cookie->{$this->helper->id . '_start'};
        }

        if ($start) {
            $context->cookie->{$this->helper->id . '_start'} = $start;
        } elseif (isset($context->cookie->{$this->helper->id . '_start'})) {
            unset($context->cookie->{$this->helper->id . '_start'});
        }

        $query = new \DbQuery();
        $query->select('*');
        $query->from($this->getTable(), 'a');

        $this->applyFilter($query);

        $query->limit($limit, $start);

        $this->helper->orderBy = $this->checkOrderBy($order_by);
        $this->helper->orderWay = $this->checkOrderDirection($order_way);

        $query->orderBy($this->helper->orderBy . ' ' . $this->helper->orderWay);

        if (method_exists($this, 'onBeforeGetList')) {
            $this->onBeforeGetList($query);
        }

        $this->sql = $query->build();

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
    }

    private function applyFilter(\DbQuery &$query)
    {
        $context = \Context::getContext();
        $filters = $context->cookie->getFamily($this->helper->id . 'Filter_');
        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if (
                $value != null
                && strpos($key, $this->helper->id . 'Filter_') === 0
            ) {
                $key = str_replace($this->helper->id . 'Filter_', '', $key);
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
                        if (!empty($value[0])) {
                            if (!\Validate::isDate($value[0])) {
                                // todo: manage error
                            } else {
                                $query->where(pSQL($key) . ' >= \'' . \pSQL(\Tools::dateFrom($value[0])) . '\'');
                            }
                        }

                        if (!empty($value[1])) {
                            if (!\Validate::isDate($value[1])) {
                                // todo: manage error
                            } else {
                                $query->where(pSQL($key) . ' <= \'' . \pSQL(\Tools::dateTo($value[1])) . '\'');
                            }
                        }
                    } else {
                        $check_key = ($key == $this->helper->id || $key == '`' . $this->helper->id . '`');
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
    }

    /**
     * @throws \PrestaShopException
     */
    protected function checkOrderBy($orderBy)
    {
        $context = \Context::getContext();
        if (empty($orderBy)) {
            if ($context->cookie->{$this->helper->id . 'Orderby'}) {
                $orderBy = $context->cookie->{$this->helper->id . 'Orderby'};
            } else {
                $orderBy = $this->helper->_defaultOrderBy;
            }
        }

        if (empty($orderBy)) {
            $orderBy = 'id_' . $this->getTable();
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
        $context = \Context::getContext();
        if (empty($orderDirection)) {
            if ($context->cookie->{$this->helper->id . 'Orderway'}) {
                $orderDirection = $context->cookie->{$this->helper->id . 'Orderway'};
            } else {
                $orderDirection = $this->helper->_defaultOrderWay;
            }
        }

        if (empty($orderDirection)) {
            $orderDirection = 'DESC';
        }

        if (!\Validate::isOrderWay($orderDirection)) {
            throw new \PrestaShopException('Invalid order direction.');
        }

        return pSQL(\Tools::strtoupper($orderDirection));
    }

    public function handleProcessFilter()
    {
        $context = \Context::getContext();
        $key = $this->getKey(true);
        if (
            \Tools::isSubmit('submitFilter' . $key)
            || $context->cookie->{'submitFilter' . $key} !== false
            || \Tools::getValue($key . 'Orderby')
            || \Tools::getValue($key . 'Orderway')) {
            foreach ($_POST as $cookie_key => $value) {
                if ($value === '') {
                    unset($context->cookie->$cookie_key);
                } elseif (stripos($cookie_key, $key . 'Filter_') === 0) {
                    $context->cookie->$cookie_key = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($key, 'submitFilter') === 0) {
                    $context->cookie->$cookie_key = !is_array($value) ? $value : json_encode($value);
                }
            }

            foreach ($_GET as $cookie_key => $value) {
                if (stripos($cookie_key, $key . 'Filter_') === 0) {
                    $context->cookie->$cookie_key = !is_array($value) ? $value : json_encode($value);
                } elseif (stripos($cookie_key, 'submitFilter') === 0) {
                    $context->cookie->$cookie_key = !is_array($value) ? $value : json_encode($value);
                }

                if (stripos($cookie_key, $key . 'Orderby') === 0 && \Validate::isOrderBy($value)) {
                    if ($value === '' || $value == $this->helper->_defaultOrderBy) {
                        unset($context->cookie->$cookie_key);
                    } else {
                        $context->cookie->$cookie_key = $value;
                    }
                } elseif (stripos($cookie_key, $key . 'Orderway') === 0 && \Validate::isOrderWay($value)) {
                    if ($value === '' || $value == $this->helper->_defaultOrderWay) {
                        unset($context->cookie->$cookie_key);
                    } else {
                        $context->cookie->$cookie_key = $value;
                    }
                }
            }
        }
    }

    private function getListTotal(): int
    {
        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from($this->getTable(), 'a');

        $this->applyFilter($query);

        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }

    private function handleProcessResetFilters()
    {
        $context = \Context::getContext();
        $key = $this->getKey(true);
        if (\Tools::isSubmit('submitReset' . $key)) {
            $filters = $context->cookie->getFamily($key . 'Filter_');
            foreach ($filters as $cookie_key => $filter) {
                if (strpos($cookie_key, $key . 'Filter_') === 0) {
                    $field = str_replace($key . 'Filter_', '', $cookie_key);
                    if (is_array($this->fields_list) && array_key_exists($field, $this->fields_list)) {
                        $context->cookie->$cookie_key = null;
                    }
                    unset($context->cookie->$cookie_key);
                }
            }

            if (isset($context->cookie->{'submitFilter' . $key})) {
                unset($context->cookie->{'submitFilter' . $key});
            }
            if (isset($context->cookie->{$key . 'Orderby'})) {
                unset($context->cookie->{$key . 'Orderby'});
            }
            if (isset($context->cookie->{$key . 'Orderway'})) {
                unset($context->cookie->{$key . 'Orderway'});
            }

            \Tools::redirectAdmin(Utils::getConfigFormUrl());
        }
    }
}
