<?php

namespace Adilis\SeoOptimizer\Audit;

use Adilis\SeoOptimizer\CacheManager;

class AuditResultList
{
    /** @var AuditInterface */
    private $audit;

    /** @var \HelperList */
    private $helper;

    /** @var array */
    private $fieldsListDef = [];

    /** @var array */
    private $results = [];

    public function __construct(AuditInterface $audit)
    {
        $this->audit = $audit;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $state = CacheManager::get('audit_' . $this->audit->getKey());

        if (!$state || empty($state['results'])) {
            return '';
        }

        $this->results = $state['results'];

        $this->buildFieldsList();

        $this->helper = new \HelperList();
        $this->helper->no_link = true;
        $this->helper->shopLinkType = '';
        $this->helper->simple_header = false;
        $this->helper->show_toolbar = true;
        $this->helper->title = 'Audit details';
        $this->helper->title_icon = $this->audit->getIcon();
        $this->helper->_default_pagination = 50;
        $this->helper->_pagination = [20, 50, 100, 300, 1000];
        $this->helper->token = \Tools::getAdminTokenLite('AdminModules');
        $this->helper->currentIndex = \AdminController::$currentIndex . '&configure=seooptimizer';
        $this->helper->identifier = 'audit_row_id';
        $this->helper->table = 'audit_' . $this->audit->getKey();
        $this->helper->id = 'audit_' . $this->audit->getKey();
        $this->helper->_defaultOrderBy = 'severity';
        $this->helper->_defaultOrderWay = 'ASC';
        $this->helper->bulk_actions = [];
        $this->helper->actions = [];

        $this->helper->toolbar_btn = [
            'export' => [
                'href' => '#',
                'desc' => 'Export CSV',
                'class' => 'seoo-audit__csv-btn',
                'data-audit-action' => 'exportCsvAudit' . ucfirst($this->audit->getKey()),
            ],
        ];

        $this->helper->tpl_vars = [
            'link' => \Context::getContext()->link,
        ];

        $this->handleResetFilters();
        $this->handleFilters();

        $filteredResults = $this->applyFilters($this->results);
        $this->helper->listTotal = count($filteredResults);

        $sortedResults = $this->applySort($filteredResults);
        $paginatedResults = $this->applyPagination($sortedResults);

        // Add row IDs for HelperList
        foreach ($paginatedResults as $i => &$row) {
            $row['audit_row_id'] = $i + 1;
        }
        unset($row);

        return $this->helper->generateList($paginatedResults, $this->fieldsListDef);
    }

    private function buildFieldsList()
    {
        $this->fieldsListDef = [];

        $this->fieldsListDef['severity'] = [
            'title' => 'Severity',
            'type' => 'text',
            'orderby' => true,
            'search' => true,
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'callback_object' => self::class,
            'callback' => 'displaySeverityBadge',
        ];

        $this->fieldsListDef['url'] = [
            'title' => 'Page',
            'type' => 'text',
            'orderby' => true,
            'search' => true,
            'callback_object' => self::class,
            'callback' => 'displayTruncatedUrl',
        ];

        $auditColumns = $this->audit->getResultColumns();
        foreach ($auditColumns as $key => $label) {
            $this->fieldsListDef[$key] = [
                'title' => $label,
                'type' => 'text',
                'orderby' => true,
                'search' => true,
            ];
        }
    }

    /**
     * @param array $results
     * @return array
     */
    private function applyFilters(array $results): array
    {
        $context = \Context::getContext();
        $filterId = $this->helper->id;
        $filters = $context->cookie->getFamily($filterId . 'Filter_');

        foreach ($filters as $cookieKey => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $field = str_replace($filterId . 'Filter_', '', $cookieKey);

            if (!isset($this->fieldsListDef[$field])) {
                continue;
            }

            $results = array_filter($results, function ($row) use ($field, $value) {
                if (!isset($row[$field])) {
                    return false;
                }

                return stripos((string) $row[$field], $value) !== false;
            });
        }

        return array_values($results);
    }

    /**
     * @param array $results
     * @return array
     */
    private function applySort(array $results): array
    {
        $context = \Context::getContext();
        $filterId = $this->helper->id;

        $orderBy = \Tools::getValue($filterId . 'Orderby');
        if (!$orderBy && isset($context->cookie->{$filterId . 'Orderby'})) {
            $orderBy = $context->cookie->{$filterId . 'Orderby'};
        }
        if (!$orderBy) {
            $orderBy = $this->helper->_defaultOrderBy;
        }

        $orderWay = \Tools::getValue($filterId . 'Orderway');
        if (!$orderWay && isset($context->cookie->{$filterId . 'Orderway'})) {
            $orderWay = $context->cookie->{$filterId . 'Orderway'};
        }
        if (!$orderWay) {
            $orderWay = $this->helper->_defaultOrderWay;
        }

        // Save to cookie
        if (\Tools::getValue($filterId . 'Orderby')) {
            $context->cookie->{$filterId . 'Orderby'} = $orderBy;
        }
        if (\Tools::getValue($filterId . 'Orderway')) {
            $context->cookie->{$filterId . 'Orderway'} = $orderWay;
        }

        $this->helper->orderBy = $orderBy;
        $this->helper->orderWay = strtoupper($orderWay);

        if (!$orderBy || !isset($this->fieldsListDef[$orderBy])) {
            return $results;
        }

        $asc = strtoupper($orderWay) === 'ASC';

        usort($results, function ($a, $b) use ($orderBy, $asc) {
            $va = isset($a[$orderBy]) ? $a[$orderBy] : '';
            $vb = isset($b[$orderBy]) ? $b[$orderBy] : '';

            if (is_numeric($va) && is_numeric($vb)) {
                $cmp = $va - $vb;
            } else {
                $cmp = strcasecmp((string) $va, (string) $vb);
            }

            return $asc ? $cmp : -$cmp;
        });

        return $results;
    }

    /**
     * @param array $results
     * @return array
     */
    private function applyPagination(array $results): array
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        $limit = (int) \Tools::getValue($filterId . '_pagination', 0);
        if (!$limit && isset($context->cookie->{$filterId . '_pagination'})) {
            $limit = (int) $context->cookie->{$filterId . '_pagination'};
        }
        if (!$limit) {
            $limit = $this->helper->_default_pagination;
        }

        if (in_array($limit, $this->helper->_pagination) && $limit != $this->helper->_default_pagination) {
            $context->cookie->{$filterId . '_pagination'} = $limit;
        }

        $page = (int) \Tools::getValue('submitFilter' . $filterId, 0);
        if (!$page && isset($context->cookie->{'submitFilter' . $filterId})) {
            $page = (int) $context->cookie->{'submitFilter' . $filterId};
        }

        $start = $page > 0 ? ($page - 1) * $limit : 0;

        return array_slice($results, $start, $limit);
    }

    private function handleResetFilters()
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        if (\Tools::isSubmit('submitReset' . $filterId)) {
            $filters = $context->cookie->getFamily($filterId . 'Filter_');
            foreach ($filters as $cookieKey => $filter) {
                if (strpos($cookieKey, $filterId . 'Filter_') === 0) {
                    unset($context->cookie->$cookieKey);
                }
            }

            if (isset($context->cookie->{'submitFilter' . $filterId})) {
                unset($context->cookie->{'submitFilter' . $filterId});
            }
            if (isset($context->cookie->{$filterId . 'Orderby'})) {
                unset($context->cookie->{$filterId . 'Orderby'});
            }
            if (isset($context->cookie->{$filterId . 'Orderway'})) {
                unset($context->cookie->{$filterId . 'Orderway'});
            }
        }
    }

    private function handleFilters()
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        if (
            \Tools::isSubmit('submitFilter' . $filterId)
            || $context->cookie->{'submitFilter' . $filterId} !== false
        ) {
            foreach ($_POST as $cookieKey => $value) {
                if (stripos($cookieKey, $filterId . 'Filter_') === 0) {
                    if ($value === '') {
                        unset($context->cookie->$cookieKey);
                    } else {
                        $context->cookie->$cookieKey = !is_array($value) ? $value : json_encode($value);
                    }
                }
            }

            foreach ($_GET as $cookieKey => $value) {
                if (stripos($cookieKey, $filterId . 'Filter_') === 0) {
                    if ($value === '') {
                        unset($context->cookie->$cookieKey);
                    } else {
                        $context->cookie->$cookieKey = !is_array($value) ? $value : json_encode($value);
                    }
                }
            }

            if (\Tools::isSubmit('submitFilter' . $filterId)) {
                $context->cookie->{'submitFilter' . $filterId} = (int) \Tools::getValue('submitFilter' . $filterId);
            }
        }
    }

    /**
     * @param string $severity
     * @return string
     */
    public static function displaySeverityBadge($severity): string
    {
        $colors = [
            'critical' => '#dc2626',
            'warning' => '#f59e0b',
            'info' => '#6b7280',
            'good' => '#16a34a',
        ];

        $color = isset($colors[$severity]) ? $colors[$severity] : '#6b7280';

        return '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:'
            . $color . '" title="' . htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') . '"></span>';
    }

    /**
     * @param string $url
     * @return string
     */
    public static function displayTruncatedUrl($url): string
    {
        $display = $url;
        if (strlen($url) > 80) {
            $display = substr($url, 0, 77) . '...';
        }

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener" title="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '">' . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
