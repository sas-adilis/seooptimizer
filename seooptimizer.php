<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

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
        $this->version = '1.0.0';
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
            'SEO_OPTIMIZER_TITLE_MIN_LENGTH' => 50,
            'SEO_OPTIMIZER_TITLE_MAX_LENGTH' => 70,
            'SEO_OPTIMIZER_META_TITLE_MIN_LENGTH' => 140,
            'SEO_OPTIMIZER_META_TITLE_MAX_LENGTH' => 170,
            'SEOO_SITEMAP_PER_PAGE' => 1000,
            'SEOO_PERF_THRESHOLD_GOOD' => 750,
            'SEOO_PERF_THRESHOLD_SLOW' => 1000,
            'SEOO_WEIGHT_THRESHOLD_LIGHT' => 1024,
            'SEOO_WEIGHT_THRESHOLD_HEAVY' => 3072,

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

            Media::addJsDef([
                'SeoOptimizerAjaxUrl' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
            ]);
        }

        $this->context->controller->addJS($this->_path . 'views/js/supplier-form.js');

    }

    /**
     * @throws PrestaShopException
     */
    public function getContent()
    {
        $content_class = [
            Adilis\SeoOptimizer\Form\FormRedirection::class,
            Adilis\SeoOptimizer\Form\FormRedirectionImport::class,
            Adilis\SeoOptimizer\Form\FormRedirection::class,
            Adilis\SeoOptimizer\Form\FormRobotsTxt::class,
            Adilis\SeoOptimizer\Form\FormCanonicalUrls::class,
            Adilis\SeoOptimizer\Form\FormIndexation::class,
            Adilis\SeoOptimizer\Form\FormVerificationCode::class,
            Adilis\SeoOptimizer\Form\FormSettings::class,
            Adilis\SeoOptimizer\Form\FormIndexationRule::class,
            Adilis\SeoOptimizer\Form\FormRichSnippets::class,
            Adilis\SeoOptimizer\Form\FormSocial::class,
            Adilis\SeoOptimizer\Form\FormMissingImageLegendFix::class,
            Adilis\SeoOptimizer\Form\FormSitemap::class,
            Adilis\SeoOptimizer\Form\FormSocialMetaData::class,
            Adilis\SeoOptimizer\Form\FormNotFoundLinksFix::class,
            Adilis\SeoOptimizer\Form\FormLinkObfuscator::class,

            Adilis\SeoOptimizer\Content\DataList\DataListRedirections::class,
            Adilis\SeoOptimizer\Content\Report\ReportTitleLength::class,
            Adilis\SeoOptimizer\Content\Report\ReportMetaTitleLength::class,
            Adilis\SeoOptimizer\Content\DataList\DataListIndexationRules::class,
            Adilis\SeoOptimizer\Content\DataList\DataListPagesNotFound::class,
            Adilis\SeoOptimizer\Content\Report\ReportUnsecuredLinks::class,
            Adilis\SeoOptimizer\Content\Report\ReportRedirectedLinks::class,
            Adilis\SeoOptimizer\Content\Report\ReportNotFoundLinks::class,
            Adilis\SeoOptimizer\Content\Report\ReportMissingImageLegend::class,
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
        ];
        foreach ($audits as $audit) {
            (new Adilis\SeoOptimizer\Audit\AuditRunner($audit))->process();
        }

        return $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/configure.tpl');
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
