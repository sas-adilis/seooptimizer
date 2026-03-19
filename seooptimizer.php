<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerIndexationRule.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerLog404.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerPage.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerRedirect.php';

use Adilis\SeoOptimizer\Constants;

class SeoOptimizer extends Module
{
    const MAX_ELEMENTS_PER_PROCESS = 1000;

    private $report = [];
    private $start_process_time = 0;
    /**
     * @var array
     */
    private $configurations = [];

    public function __construct()
    {
        $this->name = 'seooptimizer';
        $this->tab = 'seo';
        $this->version = '1.4.0';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('SEO Optimizer');
        $this->description = $this->l('Optimize your SEO');
        $this->bootstrap = true;

        $this->configurations = [
            'SEOO_SUPPLIER_PAGE_INDEXATION' => Constants::PAGE_INDEXATION_DO_NOTHING,
            'SEOO_SUPPLIER_PAGE_REDIRECTION' => '',
            'SEOO_MANUFACTURER_PAGE_INDEXATION' => Constants::PAGE_INDEXATION_DO_NOTHING,
            'SEOO_MANUFACTURER_PAGE_REDIRECTION' => '',
            'SEOO_STORE_PAGE_INDEXATION' => Constants::PAGE_INDEXATION_DO_NOTHING,
            'SEOO_STORE_PAGE_REDIRECTION' => '',
            'SEOO_SITEMAP_PAGE_INDEXATION' => Constants::PAGE_INDEXATION_DO_NOTHING,
            'SEOO_SITEMAP_PAGE_REDIRECTION' => '',
            'SEOO_CODE_VERIFICATION_GOOGLE' => '',
            'SEOO_CODE_VERIFICATION_BING' => '',
            'SEOO_CODE_VERIFICATION_PINTEREST' => '',
            'SEOO_TITLE_MIN_LENGTH' => 50,
            'SEOO_TITLE_MAX_LENGTH' => 70,
            'SEOO_META_TITLE_MIN_LENGTH' => 140,
            'SEOO_META_TITLE_MAX_LENGTH' => 170,
            'SEOO_SITEMAP_PER_PAGE' => 1000,
            'SEOO_PERF_THRESHOLD_GOOD' => 750,
            'SEOO_PERF_THRESHOLD_SLOW' => 1000,
            'SEOO_WEIGHT_THRESHOLD_LIGHT' => 1024,
            'SEOO_WEIGHT_THRESHOLD_HEAVY' => 3072,
            'SEOO_TEXT_THRESHOLD_LOW' => 100,
            'SEOO_TEXT_THRESHOLD_GOOD' => 300,

        ];
    }

    public function install(): bool
    {
        if (file_exists($this->getLocalPath() . 'sql/install.php')) {
            require_once $this->getLocalPath() . 'sql/install.php';
        }

        foreach ($this->configurations as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        return
            parent::install()
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerInitBefore')
            && $this->registerHook('actionObjectDeleteBefore')
            && $this->registerHook('actionSupplierFormBuilderModifier')
            && $this->registerHook('actionOutputHTMLBefore')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayAdminProductsSeoStepBottom')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionCategoryFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateCategoryFormHandler')
            && $this->registerHook('actionAfterCreateCategoryFormHandler')
            && $this->registerHook('actionManufacturerFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateManufacturerFormHandler')
            && $this->registerHook('actionAfterCreateManufacturerFormHandler')
            && $this->registerHook('displayBackOfficeCategory')
            ;
    }

    public function uninstall(): bool
    {
        if (file_exists($this->getLocalPath() . 'sql/uninstall.php')) {
            require_once $this->getLocalPath() . 'sql/uninstall.php';
        }

        foreach ($this->configurations as $key => $value) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/tabs.17.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/table.17.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/seooptimizer.css', 'all');

            $this->context->controller->addJS($this->_path . 'views/js/seooptimizer.js');
            $this->context->controller->addJS($this->_path . 'views/js/robots-txt.js');
            $this->context->controller->addJS($this->_path . 'views/js/llms-txt.js');

            $shopUrl = rtrim($this->context->shop->getBaseURL(true), '/');

            Media::addJsDef([
                'SeoOptimizerAjaxUrl' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                'SeoOptimizerRobots' => [
                    'shopUrl' => $shopUrl,
                    'presets' => (new Adilis\SeoOptimizer\Form\FormRobotsTxt())->getPresetsContentPublic(),
                    'i18n' => [
                        'disallowDetected' => $this->l('Disallow: / detected — no page will be indexed!'),
                        'noDisallow' => $this->l('No Disallow: / in production'),
                        'sitemapDeclared' => $this->l('Sitemap declared'),
                        'noSitemap' => $this->l('No Sitemap directive found'),
                        'cartBlocked' => $this->l('Cart/account/order pages blocked'),
                        'cartNotBlocked' => $this->l('Cart/account pages not blocked — may create duplicate content'),
                        'paramsBlocked' => $this->l('Sort/filter parameters blocked'),
                        'paramsNotBlocked' => $this->l('Sort/filter URL parameters not blocked'),
                        'userAgentPresent' => $this->l('User-agent directive present'),
                        'noUserAgent' => $this->l('No User-agent directive — file will be ignored by crawlers'),
                        'errorsDetected' => $this->l('error(s) detected'),
                        'errors' => $this->l('error(s)'),
                        'warnings' => $this->l('warning(s)'),
                        'checksPassed' => $this->l('checks passed'),
                        'noErrors' => $this->l('No errors detected'),
                        'blocked' => $this->l('Blocked'),
                        'allowed' => $this->l('Allowed'),
                        'matchesRule' => $this->l('Matches rule:'),
                        'willBeCrawled' => $this->l('This URL will be crawled by robots.'),
                    ],
                ],
                'SeoOptimizerLlms' => [
                    'presets' => (new Adilis\SeoOptimizer\Form\FormLlmsTxt())->getPresetsContentPublic(),
                    'i18n' => [
                        'titlePresent' => $this->l('Title present (# ...)'),
                        'missingTitle' => $this->l('Missing title — add a line starting with # followed by your site name'),
                        'descPresent' => $this->l('Description present (> ...)'),
                        'missingDesc' => $this->l('Missing description — add a line starting with > followed by a short description'),
                        'sectionsFound' => $this->l('section(s) found'),
                        'noSections' => $this->l('No sections found — add ## Section headings'),
                        'linksDeclared' => $this->l('link(s) declared'),
                        'noLinks' => $this->l('No links found — add links with - [Text](url): description'),
                        'emptyLinks' => $this->l('link(s) with empty URL'),
                        'errorsDetected' => $this->l('error(s) detected'),
                        'errors' => $this->l('error(s)'),
                        'warnings' => $this->l('warning(s)'),
                        'checksPassed' => $this->l('checks passed'),
                        'valid' => $this->l('Valid'),
                    ],
                ],
            ]);
        }

        if (Tools::getValue('controller') === 'AdminSuppliers') {
            $this->context->controller->addJS($this->_path . 'views/js/supplier-form.js');
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function getContent()
    {
        // Dispatch AJAX calls
        if ((int) Tools::getValue('ajax')) {
            $action = Tools::getValue('action');
            if (!empty($action)) {
                $method = 'ajaxProcess' . ucfirst($action);
                if (method_exists($this, $method)) {
                    return $this->$method();
                }
            }
        }

        $content_class = [
            Adilis\SeoOptimizer\Form\FormRedirectionEdit::class,
            Adilis\SeoOptimizer\Form\FormRedirection::class,
            Adilis\SeoOptimizer\Form\FormRedirectionImport::class,
            Adilis\SeoOptimizer\Form\FormRobotsTxt::class,
            Adilis\SeoOptimizer\Form\FormLlmsTxt::class,
            Adilis\SeoOptimizer\Form\FormCanonicalUrls::class,
            Adilis\SeoOptimizer\Form\FormIndexation::class,
            Adilis\SeoOptimizer\Form\FormVerificationCode::class,
            Adilis\SeoOptimizer\Form\FormSettings::class,
            Adilis\SeoOptimizer\Form\FormIndexationRule::class,
            Adilis\SeoOptimizer\Form\FormRichSnippets::class,
            Adilis\SeoOptimizer\Form\FormSocial::class,
            Adilis\SeoOptimizer\Form\FormSitemap::class,
            Adilis\SeoOptimizer\Form\FormSocialMetaData::class,
            Adilis\SeoOptimizer\Form\FormLinkObfuscator::class,

            Adilis\SeoOptimizer\Content\DataList\DataListRedirections::class,
            Adilis\SeoOptimizer\Content\DataList\DataListIndexationRules::class,
            Adilis\SeoOptimizer\Content\DataList\DataListPagesNotFound::class,
        ];

        foreach ($content_class as $class) {
            (new $class())->process();
        }

        // Audits (crawler-based)
        $audits = [
            new Adilis\SeoOptimizer\Audit\AuditHeadingHierarchy(),
            new Adilis\SeoOptimizer\Audit\AuditMissingAlt(),
            new Adilis\SeoOptimizer\Audit\AuditBrokenLinks(),
            new Adilis\SeoOptimizer\Audit\AuditPageLoadTime(),
            new Adilis\SeoOptimizer\Audit\AuditPageWeight(),
            new Adilis\SeoOptimizer\Audit\AuditUnsecuredLinks(),
            new Adilis\SeoOptimizer\Audit\AuditMetaTags(),
            new Adilis\SeoOptimizer\Audit\AuditInternalLinks(),
            new Adilis\SeoOptimizer\Audit\AuditTextRatio(),
            new Adilis\SeoOptimizer\Audit\AuditKeywordCheck(),
        ];
        foreach ($audits as $audit) {
            (new Adilis\SeoOptimizer\Audit\AuditRunner($audit))->process();
        }

        $scoreCalculator = new Adilis\SeoOptimizer\Score\SeoScoreCalculator();
        $seoScores = $scoreCalculator->compute();

        // Pages overview
        $pagesAggregator = new Adilis\SeoOptimizer\Pages\PagesAggregator();
        $pagesData = $pagesAggregator->aggregate();
        $totalCritical = 0;
        $totalWarnings = 0;
        $pagesWithIssues = 0;
        foreach ($pagesData as $p) {
            $totalCritical += $p['critical'];
            $totalWarnings += $p['warning'];
            if ($p['total'] > 0) {
                $pagesWithIssues++;
            }
        }

        // Assign pages variables first so pages.tpl can be rendered
        $this->context->smarty->assign([
            'seoo_module_path' => $this->_path,
            'seoo_pages_data' => $pagesData,
            'seoo_pages_has_data' => $pagesAggregator->hasData(),
            'seoo_pages_total' => count($pagesData),
            'seoo_pages_with_issues' => $pagesWithIssues,
            'seoo_pages_critical' => $totalCritical,
            'seoo_pages_warnings' => $totalWarnings,
            'seoo_pages_clean' => count($pagesData) - $pagesWithIssues,
        ]);

        $this->context->smarty->assign([
            'seoo_scores' => $seoScores,
            'seoo_export_redirections_url' => $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => $this->name, 'exportdata_list_redirections' => 1]
            ),
            'seoo_pages_html' => $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/pages.tpl'),
        ]);

        return $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/configure.tpl');
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessRunFullAudit()
    {
        $firstProcess = Tools::getValue('first_process') === 'true';
        $runner = new Adilis\SeoOptimizer\Pages\FullAuditRunner();
        $runner->run($firstProcess);
    }

    public function ajaxProcessReauditPage()
    {
        $url = Tools::getValue('url');
        if (empty($url) || !Validate::isUrl($url)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid URL']);
            exit;
        }

        $auditor = new Adilis\SeoOptimizer\Pages\SinglePageAuditor();
        $result = $auditor->auditUrl($url);

        echo json_encode($result);
        exit;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessGetChart()
    {
        $chart = Tools::getValue('chart');

        switch ($chart) {
            case 'unsecured_links':
                break;
            case 'pages_not_found':
                $query = new DbQuery();
                $query->select('DATE(date_add) as label');
                $query->select('COUNT(DISTINCT(url)) as value');
                $query->select('COUNT(*) as value2');
                $query->from('seooptimizer_log_404');
                $query->where('date_add > DATE_SUB(NOW(), INTERVAL 6 MONTH)');
                $query->groupBy('YEAR(date_add), MONTH(date_add), DAY(date_add)');
                $query->orderBy('date_add ASC');

                $datas = Db::getInstance()->executeS($query);

                $labels = array_map(function ($label) {
                    return Tools::displayDate($label);
                }, array_column($datas, 'label'));

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'labels' => $labels,
                        'datasets' => [
                            [
                                'label' => '404 URLs',
                                'data' => array_column($datas, 'value'),
                            ],
                            [
                                'label' => 'Hits',
                                'data' => array_column($datas, 'value2'),
                            ],
                        ],
                    ],
                ]);
                exit;
                break;
            case 'redirected_links':
                break;
            default:
                $this->returnJsonError('Invalid chart');
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayBeforeBodyClosingTag()
    {
        $action_classes = [
            Adilis\SeoOptimizer\Actions\WatchPageNotFound::class,
            Adilis\SeoOptimizer\Actions\RichSnippetsGenerate::class,
        ];
        foreach ($action_classes as $class) {
            (new $class())->run();
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/seooptimizer.front.css', 'all');

        $configuration_key = null;
        switch (Dispatcher::getInstance()->getController()) {
            case 'stores':
                $configuration_key = 'SEOO_STORE_PAGE_INDEXATION';
                break;
            case 'manufacturer':
                $configuration_key = 'SEOO_MANUFACTURER_PAGE_INDEXATION';
                break;
            case 'supplier':
                $configuration_key = 'SEOO_SUPPLIER_PAGE_INDEXATION';
                break;
            case 'sitemap':
                $configuration_key = 'SEOO_SITEMAP_PAGE_INDEXATION';
                break;
        }

        if ($configuration_key) {
            $indexationAction = (int) Configuration::get($configuration_key);
            switch ($indexationAction) {
                case Constants::PAGE_INDEXATION_NOINDEX:
                    header('X-Robots-Tag: noindex');
                    $page = $this->context->smarty->getTemplateVars('page');
                    $page['meta']['robots'] = 'noindex';
                    $this->context->smarty->assign('page', $page);
                    break;
                case Constants::PAGE_INDEXATION_404:
                    Tools::redirect(
                        'pagenotfound',
                        __PS_BASE_URI__,
                        $this->context->link, [
                            'HTTP/1.0 404 Not Found',
                        ]);
                    break;
                case Constants::PAGE_INDEXATION_REDIRECT_301:
                case Constants::PAGE_INDEXATION_REDIRECT_302:
                    $redirect_url = Configuration::get(str_replace('INDEXATION', 'REDIRECTION', $configuration_key));
                    if (Validate::isUrl($redirect_url)) {
                        $http_code = $indexationAction === Constants::PAGE_INDEXATION_REDIRECT_301 ? Constants::HTTP_CODE_301 : Constants::HTTP_CODE_302;
                        header('Location: ' . $redirect_url, true, $http_code);
                    }
                    break;
            }
        }

        $html_content = '';

        $action_classes = [
            Adilis\SeoOptimizer\Actions\CanonicalUrlGenerate::class,
            Adilis\SeoOptimizer\Actions\SocialMetadata::class,
        ];
        foreach ($action_classes as $class) {
            $html_content .= (new $class())->run();
        }
        $this->context->smarty->assign([
            'seoo_verification_code_google' => Configuration::get('SEOO_CODE_VERIFICATION_GOOGLE'),
            'seoo_verification_code_bing' => Configuration::get('SEOO_CODE_VERIFICATION_BING'),
            'seoo_verification_code_pinterest' => Configuration::get('SEOO_CODE_VERIFICATION_PINTEREST'),
        ]);

        return $this->display(__FILE__, 'header.tpl') . $html_content;
    }

    public function hookActionFrontControllerInitBefore($params)
    {
        $redirect = Db::getInstance()->getRow('
            SELECT *
            FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect WHERE
            redirect_from = "' . pSQL($_SERVER['REQUEST_URI']) . '"'
        );

        if ($redirect && Validate::isUrl($redirect['redirect_to'])) {
            Tools::redirect(
                $redirect['redirect_to'],
                __PS_BASE_URI__,
                $this->context->link,
                (int) $redirect['redirect_type'] == 301 ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 302 Moved Temporarily'
            );
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function hookActionObjectDeleteBefore($params)
    {
        $actions_classes = [
            \Adilis\SeoOptimizer\Events\CategoryDeleteRedirect::class,
            \Adilis\SeoOptimizer\Events\ProductDeleteRedirect::class,
            \Adilis\SeoOptimizer\Events\ManufacturerDeleteRedirect::class,
        ];

        foreach ($actions_classes as $class) {
            (new $class($params['object']))->process();
        }
    }

    public function hookActionSupplierFormBuilderModifier($params) {
        $actions_classes = [
            Adilis\SeoOptimizer\FormBuilderModifier\SupplierFormModifier::class,
        ];

        foreach ($actions_classes as $class) {
            (new $class($params))->process();
        }
    }


    public function hookActionOutputHTMLBefore($params) {
        $actions_classes = [
            Adilis\SeoOptimizer\HtmlOutputBefore\LinkObfuscator::class,
        ];

        foreach ($actions_classes as $class) {
            (new $class($params))->process($params['html']);
        }
    }

    /**
     * @return string
     */
    public function getCronUrl(): string
    {
        return $this->context->link->getModuleLink($this->name, 'cron', [
            'token' => $this->secure_key,
            'audit' => 'all',
        ]);
    }

    public function hookModuleRoutes($params): array
    {
        return [
            'module-seooptimizer-sitemap-index' => [
                'controller' => 'sitemap',
                'rule' => 'sitemap.xml',
                'keywords' => [],
                'params' => [
                    'module' => 'seooptimizer',
                    'fc' => 'module'
                ]
            ],
            'module-seooptimizer-sitemap' => [
                'controller' => 'sitemap',
                'rule' => 'sitemap/{type}/{lang}/{page}.xml',
                'keywords' => [
                    'type' => [
                        'regexp' => '[a-z_]*',
                        'param' => 'type'
                    ],
                    'lang' => [
                        'regexp' => '[a-zA-Z]{2,3}',
                        'param' => 'lang',
                    ],
                    'page' => [
                        'regexp' => '[0-9]*',
                        'param' => 'page',
                    ]
                ],
                'params' => [
                    'module' => 'seooptimizer',
                    'fc' => 'module'
                ]
            ],

        ];
    }
}
