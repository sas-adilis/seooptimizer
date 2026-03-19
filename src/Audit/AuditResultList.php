<?php

namespace Adilis\SeoOptimizer\Audit;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Adilis\SeoOptimizer\Storage\AuditResultStorage;

class AuditResultList
{
    /** @var AuditInterface */
    private $audit;

    /** @var \HelperList */
    private $helper;

    /** @var array */
    private $fieldsListDef = [];

    public function __construct(AuditInterface $audit)
    {
        $this->audit = $audit;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $auditKey = $this->audit->getKey();
        $totalResults = AuditResultStorage::countByAuditKey($auditKey);

        if ($totalResults === 0) {
            return '';
        }

        $this->buildFieldsList();
        $filterId = 'audit_' . $auditKey;

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
        $this->helper->identifier = 'id_seooptimizer_audit_result';
        $this->helper->table = $filterId;
        $this->helper->id = $filterId;
        $this->helper->_defaultOrderBy = 'severity';
        $this->helper->_defaultOrderWay = 'ASC';
        $this->helper->bulk_actions = [];
        $this->helper->actions = [];

        $this->helper->tpl_vars = [
            'link' => \Context::getContext()->link,
        ];

        // Handle filters
        $this->handleResetFilters();
        $this->handleFilters();

        // Build SQL filters from cookies
        $filters = $this->getActiveFilters();
        $this->helper->listTotal = AuditResultStorage::countFiltered($auditKey, $filters);

        // Pagination
        $limit = $this->getLimit();
        $start = $this->getStart($limit);

        // Sorting
        $orderBy = $this->getOrderBy();
        $orderWay = $this->getOrderWay();
        $this->helper->orderBy = $orderBy;
        $this->helper->orderWay = $orderWay;

        $results = AuditResultStorage::getFiltered(
            $auditKey,
            $start,
            $limit,
            $orderBy,
            $orderWay,
            $filters
        );

        return $this->helper->generateList($results, $this->fieldsListDef);
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

        $this->fieldsListDef['message'] = [
            'title' => 'Message',
            'type' => 'text',
            'orderby' => true,
            'search' => true,
        ];

        $columnCallbacks = method_exists($this->audit, 'getResultColumnCallbacks')
            ? $this->audit->getResultColumnCallbacks()
            : [];

        $auditColumns = $this->audit->getResultColumns();
        foreach ($auditColumns as $key => $label) {
            $def = [
                'title' => $label,
                'type' => 'text',
                'orderby' => false,
                'search' => true,
            ];

            if (isset($columnCallbacks[$key])) {
                $def['callback_object'] = self::class;
                $def['callback'] = $columnCallbacks[$key];
            }

            $this->fieldsListDef[$key] = $def;
        }
    }

    /**
     * @return array
     */
    private function getActiveFilters(): array
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();
        $filters = [];

        $cookieFilters = $context->cookie->getFamily($filterId . 'Filter_');
        foreach ($cookieFilters as $cookieKey => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $field = str_replace($filterId . 'Filter_', '', $cookieKey);
            if (isset($this->fieldsListDef[$field])) {
                $filters[$field] = $value;
            }
        }

        return $filters;
    }

    /**
     * @return int
     */
    private function getLimit(): int
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

        return $limit;
    }

    /**
     * @param int $limit
     * @return int
     */
    private function getStart(int $limit): int
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        $page = (int) \Tools::getValue('submitFilter' . $filterId, 0);
        if (!$page && isset($context->cookie->{'submitFilter' . $filterId})) {
            $page = (int) $context->cookie->{'submitFilter' . $filterId};
        }

        return $page > 0 ? ($page - 1) * $limit : 0;
    }

    /**
     * @return string
     */
    private function getOrderBy(): string
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        $orderBy = \Tools::getValue($filterId . 'Orderby');
        if (!$orderBy && isset($context->cookie->{$filterId . 'Orderby'})) {
            $orderBy = $context->cookie->{$filterId . 'Orderby'};
        }
        if (!$orderBy) {
            $orderBy = $this->helper->_defaultOrderBy;
        }

        if (\Tools::getValue($filterId . 'Orderby')) {
            $context->cookie->{$filterId . 'Orderby'} = $orderBy;
        }

        return $orderBy;
    }

    /**
     * @return string
     */
    private function getOrderWay(): string
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        $orderWay = \Tools::getValue($filterId . 'Orderway');
        if (!$orderWay && isset($context->cookie->{$filterId . 'Orderway'})) {
            $orderWay = $context->cookie->{$filterId . 'Orderway'};
        }
        if (!$orderWay) {
            $orderWay = $this->helper->_defaultOrderWay;
        }

        if (\Tools::getValue($filterId . 'Orderway')) {
            $context->cookie->{$filterId . 'Orderway'} = $orderWay;
        }

        return strtoupper($orderWay);
    }

    private function handleResetFilters()
    {
        $filterId = $this->helper->id;
        $context = \Context::getContext();

        if (\Tools::isSubmit('submitReset' . $filterId)) {
            $filters = $context->cookie->getFamily($filterId . 'Filter_');
            foreach ($filters as $cookieKey => $filter) {
                unset($context->cookie->$cookieKey);
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

        if (\Tools::isSubmit('submitFilter' . $filterId) || $context->cookie->{'submitFilter' . $filterId} !== false) {
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
        return self::renderCellTemplate('severity_badge', [
            'cell_severity' => $severity,
        ]);
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

        return self::renderCellTemplate('truncated_url', [
            'cell_url' => $url,
            'cell_display' => $display,
        ]);
    }

    /**
     * @param string $score
     * @return string
     */
    public static function displayScoreBadge($score): string
    {
        return self::renderCellTemplate('score_badge', [
            'cell_score_value' => (int) $score,
            'cell_score_label' => $score,
        ]);
    }

    /**
     * @param string $zones
     * @return string
     */
    public static function displayZonesList($zones): string
    {
        $zonesArray = (!empty($zones) && $zones !== '-' && $zones !== 'None')
            ? array_map('trim', explode(',', $zones))
            : [];

        return self::renderCellTemplate('zones_list', [
            'cell_zones' => $zones,
            'cell_zones_array' => $zonesArray,
        ]);
    }

    /**
     * @param string $zones
     * @return string
     */
    public static function displayMissingZones($zones): string
    {
        $zonesArray = (!empty($zones) && $zones !== '-')
            ? array_map('trim', explode(',', $zones))
            : [];

        return self::renderCellTemplate('missing_zones', [
            'cell_zones' => $zones,
            'cell_zones_array' => $zonesArray,
        ]);
    }

    /**
     * @param string $template
     * @param array<string, mixed> $vars
     * @return string
     */
    private static function renderCellTemplate(string $template, array $vars): string
    {
        $smarty = \Context::getContext()->smarty;
        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $smarty->fetch(
            _PS_MODULE_DIR_ . 'seooptimizer/views/templates/admin/helpers/cells/' . $template . '.tpl'
        );
    }
}
