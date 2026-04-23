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

    /** @var string */
    public $secure_key;

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
        $this->version = '1.6.0';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->secure_key = (string) Configuration::get('SEOO_SECURE_KEY');

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
            'SEOO_FRONT_AUDIT_ENABLED' => 0,
            'SEOO_BO_SCORE_COLUMN' => 0,

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

        if (!Configuration::get('SEOO_SECURE_KEY')) {
            Configuration::updateValue('SEOO_SECURE_KEY', bin2hex(random_bytes(16)));
        }

        return
            parent::install()
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerInitBefore')
            && $this->registerHook('actionObjectDeleteBefore')
            && $this->registerHook('actionCategoryFormBuilderModifier')
            && $this->registerHook('actionRootCategoryFormBuilderModifier')
            && $this->registerHook('actionAfterUpdateCategoryFormHandler')
            && $this->registerHook('actionAfterCreateCategoryFormHandler')
            && $this->registerHook('actionAfterUpdateRootCategoryFormHandler')
            && $this->registerHook('actionAfterCreateRootCategoryFormHandler')
            && $this->registerHook('actionSupplierFormBuilderModifier')
            && $this->registerHook('actionOutputHTMLBefore')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('actionProductGridDefinitionModifier')
            && $this->registerHook('actionProductGridQueryBuilderModifier')
            && $this->registerHook('actionCategoryGridDefinitionModifier')
            && $this->registerHook('actionCategoryGridQueryBuilderModifier')
            && $this->registerHook('actionManufacturerGridDefinitionModifier')
            && $this->registerHook('actionManufacturerGridQueryBuilderModifier')
            && $this->registerHook('actionSupplierGridDefinitionModifier')
            && $this->registerHook('actionSupplierGridQueryBuilderModifier')
            && $this->registerHook('actionCmsPageGridDefinitionModifier')
            && $this->registerHook('actionCmsPageGridQueryBuilderModifier')
            && $this->registerHook('displayAdminProductsSeoStepBottom')
            && $this->registerHook('displayBackOfficeFooter')
            && $this->registerHook('actionObjectUpdateAfter')
            && $this->registerHook('actionObjectAddAfter')
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

        Configuration::deleteByName('SEOO_SECURE_KEY');

        return parent::uninstall();
    }

    public function hookBackOfficeHeader()
    {
        // Auto-register hooks added after initial install (avoids requiring reinstall)
        static $hooksChecked = false;
        if (!$hooksChecked) {
            $hooksChecked = true;
            foreach (['actionObjectUpdateAfter', 'actionObjectAddAfter', 'displayBackOfficeFooter'] as $hookName) {
                if (!$this->isRegisteredInHook($hookName)) {
                    $this->registerHook($hookName);
                }
            }
        }

        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/tabs.17.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/table.17.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/seooptimizer.css', 'all');

            $this->context->controller->addJS($this->_path . 'views/js/seooptimizer.js');
            $this->context->controller->addJS($this->_path . 'views/js/audit.js');
            $this->context->controller->addJS($this->_path . 'views/js/pages.js');
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

        // Entity audit panel + SEO config panel — load CSS/JS on entity edit pages
        //
        // Entities with dedicated tab JS (server-rendered HTML + lightweight JS):
        //   - category → entity-form-tabs.js + category-tabs.js
        //
        // Other entities still use the generic JS-built panel:
        //   - product, manufacturer, supplier, cms → seo-config.js
        //
        $entityControllerMap = [
            'AdminProducts' => 'product',
            'AdminCategories' => 'category',
            'AdminManufacturers' => 'manufacturer',
            'AdminSuppliers' => 'supplier',
            'AdminCmsContent' => 'cms',
        ];

        // Entities using the new server-rendered tab system
        $entityTabEntities = ['category'];

        $currentController = Tools::getValue('controller');
        if (isset($entityControllerMap[$currentController])) {
            $entityType = $entityControllerMap[$currentController];

            // Common CSS for all entities
            $this->context->controller->addCSS($this->_path . 'views/css/front-audit.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/entity-audit.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/seo-config.css', 'all');

            if (in_array($entityType, $entityTabEntities, true)) {
                // New approach: server-rendered HTML, JS only handles tab + DOM moves
                $this->context->controller->addJS($this->_path . 'views/js/entity-audit.js');
                $this->context->controller->addJS($this->_path . 'views/js/entity-form-tabs.js');
                $this->context->controller->addJS($this->_path . 'views/js/' . $entityType . '-tabs.js');

                // Pass available tags for meta template insertion
                $entity = Adilis\SeoOptimizer\Entity\EntityRegistry::get($entityType);
                if ($entity) {
                    Media::addJsDef([
                        'SeoOptimizerTags' => $entity->getAvailableTags(),
                    ]);
                }
            } else {
                // Legacy approach: JS-built panel
                $this->context->controller->addJS($this->_path . 'views/js/entity-audit.js');
                $this->context->controller->addJS($this->_path . 'views/js/seo-config.js');

                $idEntity = $this->detectEntityId($entityType);

                if ($idEntity > 0) {
                    $idLang = (int) $this->context->language->id;
                    $url = $this->getEntityUrl($entityType, $idEntity, $idLang);

                    $languages = [];
                    foreach (Language::getLanguages(false) as $lang) {
                        $languages[] = [
                            'id_lang' => (int) $lang['id_lang'],
                            'iso_code' => $lang['iso_code'],
                            'name' => $lang['name'],
                        ];
                    }

                    Media::addJsDef([
                        'SeoOptimizerConfig' => [
                            'ajaxUrl' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                            'entityType' => $entityType,
                            'idEntity' => $idEntity,
                            'entityUrl' => $url,
                            'logoUrl' => $this->_path . 'logo.png',
                            'languages' => $languages,
                            'defaultLang' => (int) Configuration::get('PS_LANG_DEFAULT'),
                            'i18n' => [
                                'title' => $this->l('SEO Configuration'),
                                'googlePreview' => $this->l('Google Preview'),
                                'loading' => $this->l('Loading...'),
                                'noTitle' => $this->l('(no title)'),
                                'noDescription' => $this->l('(no description)'),
                                'focusKeywords' => $this->l('Focus Keywords'),
                                'keywordsPlaceholder' => $this->l('e.g. chaussures running, baskets sport'),
                                'keywordsHelp' => $this->l('Comma-separated keywords that this page should rank for.'),
                                'canonicalUrl' => $this->l('Canonical URL'),
                                'canonicalPlaceholder' => $this->l('Leave empty for default (current page URL)'),
                                'canonicalHelp' => $this->l('Custom canonical URL for this page. Cross-domain supported.'),
                                'indexation' => $this->l('Indexation'),
                                'defaultIndex' => $this->l('Default (index)'),
                                'noindex' => $this->l('Noindex — hide from search engines'),
                                'linkFollowing' => $this->l('Link following'),
                                'defaultFollow' => $this->l('Default (follow)'),
                                'nofollow' => $this->l('Nofollow — do not follow links'),
                                'save' => $this->l('Save SEO settings'),
                                'saved' => $this->l('Saved'),
                                'error' => $this->l('Error'),
                                'analyzing' => $this->l('Analyse SEO en cours...'),
                            ],
                        ],
                    ]);
                }
            }
        }

        // Score column — inject badge + batch audit + side panel in BO listings
        $gridControllerEntityMap = [
            'AdminProducts' => 'product',
            'AdminCategories' => 'category',
            'AdminManufacturers' => 'manufacturer',
            'AdminSuppliers' => 'supplier',
            'AdminCmsContent' => 'cms',
        ];
        if (isset($gridControllerEntityMap[$currentController]) && (int) Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            $this->context->controller->addCSS($this->_path . 'views/css/entity-audit.css', 'all');
            $this->context->controller->addCSS($this->_path . 'views/css/front-audit.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/bo-score-column-grid.js');
            $this->context->controller->addJS($this->_path . 'views/js/bo-score-column.js');

            Media::addJsDef([
                'SeoOptimizerScoreColumn' => [
                    'ajaxUrl' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                    'entityType' => $gridControllerEntityMap[$currentController],
                    'moduleUrl' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]),
                ],
            ]);
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
            Adilis\SeoOptimizer\Form\FormMetaTemplates::class,

            Adilis\SeoOptimizer\Content\DataList\DataListRedirections::class,
            Adilis\SeoOptimizer\Content\DataList\DataListIndexationRules::class,
            Adilis\SeoOptimizer\Content\DataList\DataListPagesNotFound::class,
        ];

        // Pre-assign all Smarty variables with empty defaults to avoid
        // "Undefined array key" warnings in PS 9 if a process() fails
        $this->context->smarty->assign([
            'form_redirection_edit' => '',
            'form_redirection' => '',
            'form_redirection_import' => '',
            'form_robots_txt' => '',
            'form_llms_txt' => '',
            'form_canonical_urls' => '',
            'form_indexation' => '',
            'form_verification_code' => '',
            'form_settings' => '',
            'form_indexation_rule' => '',
            'form_rich_snippets' => '',
            'form_social' => '',
            'form_sitemap' => '',
            'form_social_meta_data' => '',
            'form_link_obfuscator' => '',
            'form_meta_templates' => '',
            'data_list_redirections' => '',
            'data_list_indexation_rules' => '',
            'data_list_pages_not_found' => '',
            'audit_heading_hierarchy' => '',
            'audit_missing_alt' => '',
            'audit_broken_links' => '',
            'audit_redirected_links' => '',
            'audit_page_load_time' => '',
            'audit_page_weight' => '',
            'audit_unsecured_links' => '',
            'audit_meta_tags' => '',
            'audit_internal_links' => '',
            'audit_text_ratio' => '',
            'audit_keyword_check' => '',
        ]);

        foreach ($content_class as $class) {
            try {
                (new $class())->process();
            } catch (\Throwable $e) {
                PrestaShopLogger::addLog(
                    'SeoOptimizer: ' . $class . ' process() failed: ' . $e->getMessage(),
                    3,
                    null,
                    'SeoOptimizer'
                );
            }
        }

        // Audits (crawler-based)
        foreach (Adilis\SeoOptimizer\Audit\AuditRegistry::getAll() as $audit) {
            try {
                (new Adilis\SeoOptimizer\Audit\AuditRunner($audit))->process();
            } catch (\Throwable $e) {
                PrestaShopLogger::addLog(
                    'SeoOptimizer: AuditRunner ' . $audit->getKey() . ' failed: ' . $e->getMessage(),
                    3,
                    null,
                    'SeoOptimizer'
                );
            }
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
     * AJAX handler for BO entity audit panel.
     */
    public function ajaxProcessEntityAudit()
    {
        $url = Tools::getValue('url');
        if (empty($url) || !Validate::isUrl($url)) {
            $this->returnJsonSuccess(['html' => '', 'score' => ['score' => 0, 'grade' => '-', 'color' => 'gray']]);
        }

        $analyzer = new Adilis\SeoOptimizer\FrontAudit\FrontPageAnalyzer();
        $result = $analyzer->analyze($url);

        if (isset($result['error'])) {
            $this->returnJsonSuccess(['html' => '<p class="seoo-fa-empty">' . htmlspecialchars($result['error']) . '</p>', 'score' => ['score' => 0, 'grade' => '-', 'color' => 'gray']]);
        }

        $this->context->smarty->assign([
            'seoo_audit' => $result,
            'seoo_module_path' => $this->_path,
        ]);

        $html = $this->context->smarty->fetch(
            $this->getLocalPath() . 'views/templates/hook/front-audit-panel.tpl'
        );

        echo json_encode([
            'status' => 'success',
            'data' => ['html' => $html, 'score' => $result['score']],
        ]);
        exit;
    }

    /**
     * AJAX handler: return scores for a batch of entity IDs.
     */
    public function ajaxProcessGetEntityScores()
    {
        $entityType = Tools::getValue('entity_type');
        $ids = Tools::getValue('ids');

        if (empty($entityType) || empty($ids)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        $scores = SeoOptimizerPage::getScoresByEntityType(pSQL($entityType));

        $result = [];
        foreach (array_map('intval', explode(',', $ids)) as $id) {
            if (isset($scores[$id])) {
                $result[$id] = $scores[$id];
                $result[$id]['color'] = SeoOptimizerPage::gradeToColor($scores[$id]['grade']);
            }
        }

        echo json_encode(['status' => 'success', 'data' => $result]);
        exit;
    }

    /**
     * AJAX handler: audit a batch of entities (max 10) and return their scores.
     * Called progressively from the BO listing grids for entities without scores.
     */
    public function ajaxProcessBatchEntityAudit()
    {
        $entityType = pSQL(Tools::getValue('entity_type'));
        $ids = Tools::getValue('ids');

        if (empty($entityType) || empty($ids)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit;
        }

        $idList = array_map('intval', explode(',', $ids));
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $analyzer = new Adilis\SeoOptimizer\FrontAudit\FrontPageAnalyzer();
        $results = [];

        foreach ($idList as $idEntity) {
            $url = $this->getEntityUrl($entityType, $idEntity, $idLang);
            if (empty($url)) {
                continue;
            }

            $analysis = $analyzer->analyze($url);
            if (isset($analysis['error'])) {
                continue;
            }

            $score = isset($analysis['score']) ? $analysis['score'] : ['score' => 0, 'grade' => '-', 'color' => 'gray'];
            $scoreValue = (float) $score['score'];
            $grade = $score['grade'];

            // Upsert into seooptimizer_page
            $now = date('Y-m-d H:i:s');
            $existing = Db::getInstance()->getValue(
                'SELECT id_seooptimizer_page FROM ' . _DB_PREFIX_ . 'seooptimizer_page
                WHERE entity_type = "' . pSQL($entityType) . '"
                AND id_entity = ' . (int) $idEntity . '
                AND id_lang = ' . (int) $idLang . '
                AND id_shop = ' . (int) $idShop
            );

            $countCritical = 0;
            $countWarning = 0;
            $countInfo = 0;
            $sections = ['meta', 'headings', 'content', 'images', 'links', 'structured_data', 'indexation', 'performance'];
            foreach ($sections as $section) {
                if (!isset($analysis[$section]['checks'])) {
                    continue;
                }
                foreach ($analysis[$section]['checks'] as $check) {
                    $status = isset($check['status']) ? $check['status'] : 'good';
                    if ($status === 'critical') {
                        $countCritical++;
                    } elseif ($status === 'warning') {
                        $countWarning++;
                    } elseif ($status === 'info') {
                        $countInfo++;
                    }
                }
            }

            $data = [
                'entity_type' => pSQL($entityType),
                'id_entity' => (int) $idEntity,
                'id_lang' => (int) $idLang,
                'id_shop' => (int) $idShop,
                'url' => pSQL($url, true),
                'count_critical' => (int) $countCritical,
                'count_warning' => (int) $countWarning,
                'count_info' => (int) $countInfo,
                'count_total' => (int) ($countCritical + $countWarning + $countInfo),
                'score' => round($scoreValue, 1),
                'grade' => pSQL($grade),
                'date_audit' => $now,
                'date_upd' => $now,
            ];

            if ($existing) {
                Db::getInstance()->update('seooptimizer_page', $data, 'id_seooptimizer_page = ' . (int) $existing);
            } else {
                $data['date_add'] = $now;
                $data['keywords'] = '';
                Db::getInstance()->insert('seooptimizer_page', $data);
            }

            $results[$idEntity] = [
                'score' => $scoreValue,
                'grade' => $grade,
                'color' => SeoOptimizerPage::gradeToColor($grade),
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $results]);
        exit;
    }

    /**
     * AJAX handler: return entity audit panel HTML for a given entity.
     * Used when clicking a score badge in the BO listing.
     */
    public function ajaxProcessGetEntityAuditPanel()
    {
        $entityType = pSQL(Tools::getValue('entity_type'));
        $idEntity = (int) Tools::getValue('id_entity');
        $idLang = (int) $this->context->language->id;

        $url = $this->getEntityUrl($entityType, $idEntity, $idLang);
        if (empty($url)) {
            echo json_encode(['status' => 'error', 'message' => 'Unable to resolve URL']);
            exit;
        }

        $analyzer = new Adilis\SeoOptimizer\FrontAudit\FrontPageAnalyzer();
        $result = $analyzer->analyze($url);

        if (isset($result['error'])) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'html' => '<p class="seoo-fa-empty">' . htmlspecialchars($result['error'], ENT_QUOTES, 'UTF-8') . '</p>',
                    'score' => ['score' => 0, 'grade' => '-', 'color' => 'gray'],
                    'url' => $url,
                ],
            ]);
            exit;
        }

        $this->context->smarty->assign([
            'seoo_audit' => $result,
            'seoo_module_path' => $this->_path,
        ]);

        $html = $this->context->smarty->fetch(
            $this->getLocalPath() . 'views/templates/hook/front-audit-panel.tpl'
        );

        echo json_encode([
            'status' => 'success',
            'data' => ['html' => $html, 'score' => $result['score'], 'url' => $url],
        ]);
        exit;
    }

    /**
     * Detect the entity ID from the current request (GET/POST params + Symfony route params).
     *
     * @param string $entityType
     * @return int
     */
    private function detectEntityId(string $entityType): int
    {
        // Parameter names per entity type (Legacy name + Symfony route name)
        $paramMap = [
            'product' => ['id_product', 'productId'],
            'category' => ['id_category', 'categoryId'],
            'manufacturer' => ['id_manufacturer', 'manufacturerId'],
            'supplier' => ['id_supplier', 'supplierId'],
            'cms' => ['id_cms', 'cmsPageId'],
        ];

        $params = isset($paramMap[$entityType]) ? $paramMap[$entityType] : [];

        // 1. Try GET/POST (Legacy admin)
        foreach ($params as $param) {
            $id = (int) Tools::getValue($param);
            if ($id > 0) {
                return $id;
            }
        }

        // 2. Try Symfony Request attributes (PS 8/9 route params)
        if (class_exists('Symfony\Component\HttpFoundation\Request')) {
            try {
                $request = call_user_func(['Symfony\Component\HttpFoundation\Request', 'createFromGlobals']);
                // In PS 8/9, the global kernel request has the route attributes
                if (method_exists($this, 'get') && $this->get('request_stack')) {
                    $request = $this->get('request_stack')->getCurrentRequest();
                }
                if ($request) {
                    foreach ($params as $param) {
                        $val = $request->attributes->getInt($param);
                        if ($val > 0) {
                            return $val;
                        }
                        // Also check query and request bags
                        $val = (int) $request->get($param, 0);
                        if ($val > 0) {
                            return $val;
                        }
                    }
                    // Generic "id"
                    $val = $request->attributes->getInt('id');
                    if ($val > 0) {
                        return $val;
                    }
                }
            } catch (\Throwable $e) {
                // Symfony not available, skip
            }
        }

        // 3. Fallback: generic "id" parameter
        $id = (int) Tools::getValue('id');
        if ($id > 0) {
            return $id;
        }

        // 4. Parse from URL path (last numeric segment: /edit/3 or /3/edit)
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (preg_match('/\/(\d+)(?:\/edit|\/update)?(?:\?|$)/', $uri, $matches)) {
            $id = (int) $matches[1];
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Resolve the front-office URL for a given entity type and ID.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param int $idLang
     * @return string
     */
    private function getEntityUrl(string $entityType, int $idEntity, int $idLang): string
    {
        $link = $this->context->link;

        switch ($entityType) {
            case 'product':
                return $link->getProductLink($idEntity, null, null, null, $idLang);
            case 'category':
                return $link->getCategoryLink($idEntity, null, $idLang);
            case 'manufacturer':
                return $link->getManufacturerLink($idEntity, null, $idLang);
            case 'supplier':
                return $link->getSupplierLink($idEntity, null, $idLang);
            case 'cms':
                return $link->getCMSLink($idEntity, null, $idLang);
            default:
                return '';
        }
    }

    /**
     * AJAX handler: get SEO configuration for an entity.
     */
    public function ajaxProcessGetSeoConfig()
    {
        $entityType = pSQL(Tools::getValue('entity_type'));
        $idEntity = (int) Tools::getValue('id_entity');

        if (empty($entityType) || $idEntity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $allLangs = (int) Tools::getValue('all_langs');

        if ($allLangs) {
            $data = [];
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $config = SeoOptimizerPage::getSeoConfig($entityType, $idEntity, $idLang);
                $data[$idLang] = $config;
            }
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            $idLang = (int) Tools::getValue('id_lang');
            $config = SeoOptimizerPage::getSeoConfig($entityType, $idEntity, $idLang > 0 ? $idLang : null);
            echo json_encode(['status' => 'success', 'data' => $config]);
        }
        exit;
    }

    /**
     * AJAX handler: save SEO configuration for an entity.
     */
    public function ajaxProcessSaveSeoConfig()
    {
        $entityType = pSQL(Tools::getValue('entity_type'));
        $idEntity = (int) Tools::getValue('id_entity');

        if (empty($entityType) || $idEntity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $noindex = (int) Tools::getValue('noindex');
        $nofollow = (int) Tools::getValue('nofollow');

        // Multi-lang save: keywords and canonical_url are per-language
        $langKeywords = Tools::getValue('keywords_lang');
        $langCanonical = Tools::getValue('canonical_url_lang');

        if (is_array($langKeywords) || is_array($langCanonical)) {
            // Save per-language fields
            $success = true;
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $data = [
                    'keywords' => is_array($langKeywords) && isset($langKeywords[$idLang])
                        ? (string) $langKeywords[$idLang]
                        : '',
                    'canonical_url' => is_array($langCanonical) && isset($langCanonical[$idLang])
                        ? (string) $langCanonical[$idLang]
                        : '',
                    'noindex' => $noindex,
                    'nofollow' => $nofollow,
                ];
                if (!SeoOptimizerPage::saveSeoConfig($entityType, $idEntity, $data, $idLang)) {
                    $success = false;
                }
            }
        } else {
            // Legacy single-lang save
            $idLang = (int) Tools::getValue('id_lang');
            $data = [
                'keywords' => (string) Tools::getValue('keywords'),
                'canonical_url' => (string) Tools::getValue('canonical_url'),
                'noindex' => $noindex,
                'nofollow' => $nofollow,
            ];
            $success = SeoOptimizerPage::saveSeoConfig($entityType, $idEntity, $data, $idLang > 0 ? $idLang : null);
        }

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'SEO configuration saved']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save']);
        }
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

        if ((int) Configuration::get('SEOO_FRONT_AUDIT_ENABLED') && $this->isEmployeeBrowsing()) {
            $this->context->smarty->assign([
                'seoo_module_path' => $this->_path,
            ]);

            return $this->display(__FILE__, 'views/templates/hook/front-audit-shell.tpl');
        }

        return '';
    }

    /**
     * @throws PrestaShopException
     */
    public function hookDisplayHeader($params)
    {
        // Apply meta templates for pages with empty meta title/description
        Adilis\SeoOptimizer\MetaTemplate\MetaTemplateEngine::apply();

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

        // Per-entity noindex/nofollow/canonical from SEO config panel
        // Applied AFTER CanonicalUrlGenerate so custom canonical overrides auto-generated
        $this->applyPerEntitySeoConfig();
        $this->context->smarty->assign([
            'seoo_verification_code_google' => Configuration::get('SEOO_CODE_VERIFICATION_GOOGLE'),
            'seoo_verification_code_bing' => Configuration::get('SEOO_CODE_VERIFICATION_BING'),
            'seoo_verification_code_pinterest' => Configuration::get('SEOO_CODE_VERIFICATION_PINTEREST'),
        ]);

        // Front audit panel
        if ((int) Configuration::get('SEOO_FRONT_AUDIT_ENABLED') && $this->isEmployeeBrowsing()) {
            $this->context->controller->registerStylesheet(
                'module-seooptimizer-front-audit',
                'modules/' . $this->name . '/views/css/front-audit.css',
                ['media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'module-seooptimizer-front-audit',
                'modules/' . $this->name . '/views/js/front-audit.js',
                ['position' => 'bottom', 'priority' => 200]
            );

            $currentUrl = Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            Media::addJsDef([
                'SeoOptimizerFrontAudit' => [
                    'ajaxUrl' => $this->context->link->getModuleLink($this->name, 'pageaudit'),
                    'currentUrl' => $currentUrl,
                ],
            ]);
        }

        return $this->display(__FILE__, 'header.tpl') . $html_content;
    }

    /**
     * Apply per-entity noindex/nofollow and custom canonical from the SEO config panel.
     */
    private function applyPerEntitySeoConfig()
    {
        try {
            $controller = Dispatcher::getInstance()->getController();
            $entityType = null;
            $idEntity = 0;

            switch ($controller) {
                case 'product':
                    $entityType = 'product';
                    $idEntity = (int) Tools::getValue('id_product');
                    break;
                case 'category':
                    $entityType = 'category';
                    $idEntity = (int) Tools::getValue('id_category');
                    break;
                case 'manufacturer':
                    $entityType = 'manufacturer';
                    $idEntity = (int) Tools::getValue('id_manufacturer');
                    break;
                case 'supplier':
                    $entityType = 'supplier';
                    $idEntity = (int) Tools::getValue('id_supplier');
                    break;
                case 'cms':
                    $entityType = 'cms';
                    $idEntity = (int) Tools::getValue('id_cms');
                    break;
            }

            if (!$entityType || $idEntity <= 0) {
                return;
            }

            $config = SeoOptimizerPage::getSeoConfig($entityType, $idEntity);

            // Apply noindex
            if ((int) $config['noindex'] === 1) {
                header('X-Robots-Tag: noindex');
                $page = $this->context->smarty->getTemplateVars('page');
                $robots = isset($page['meta']['robots']) ? $page['meta']['robots'] : '';
                if (strpos($robots, 'noindex') === false) {
                    $page['meta']['robots'] = $robots ? $robots . ', noindex' : 'noindex';
                }
                $this->context->smarty->assign('page', $page);
            }

            // Apply nofollow
            if ((int) $config['nofollow'] === 1) {
                $page = $this->context->smarty->getTemplateVars('page');
                $robots = isset($page['meta']['robots']) ? $page['meta']['robots'] : '';
                if (strpos($robots, 'nofollow') === false) {
                    $page['meta']['robots'] = $robots ? $robots . ', nofollow' : 'nofollow';
                }
                $this->context->smarty->assign('page', $page);
            }

            // Apply custom canonical URL (overrides the auto-generated one)
            if (!empty($config['canonical_url'])) {
                $page = $this->context->smarty->getTemplateVars('page');
                $page['canonical'] = $config['canonical_url'];
                $this->context->smarty->assign('page', $page);
            }
        } catch (\Throwable $e) {
            // Columns may not exist yet (pre-upgrade) — silently skip
        }
    }

    /**
     * @return bool
     */
    private function isEmployeeBrowsing(): bool
    {
        $adminCookie = new Cookie('psAdmin');

        return !empty($adminCookie->id_employee);
    }

    public function hookActionFrontControllerInitBefore($params)
    {
        $redirect = Db::getInstance()->getRow('
            SELECT *
            FROM ' . _DB_PREFIX_ . 'seooptimizer_redirect WHERE
            redirect_from = "' . pSQL($_SERVER['REQUEST_URI']) . '"'
        );

        if ($redirect) {
            $type = (int) $redirect['redirect_type'];

            if ($type === 410) {
                header('HTTP/1.1 410 Gone');
                exit;
            }

            if ($type === 404) {
                header('HTTP/1.1 404 Not Found');
                $_GET['controller'] = 'pagenotfound';
                return;
            }

            if (!empty($redirect['redirect_to']) && Validate::isUrl($redirect['redirect_to'])) {
                Tools::redirect(
                    $redirect['redirect_to'],
                    __PS_BASE_URI__,
                    $this->context->link,
                    $type === 301 ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 302 Moved Temporarily'
                );
            }
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

    /**
     * Save custom SEO config fields submitted with the entity form.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionObjectUpdateAfter(array $params)
    {
        $this->processSeoConfigFromPost($params);
    }

    /**
     * Save custom SEO config fields when a new entity is created.
     *
     * @param array<string, mixed> $params
     */
    public function hookActionObjectAddAfter(array $params)
    {
        $this->processSeoConfigFromPost($params);
    }

    /**
     * Process SEO config fields submitted with a BO entity form.
     *
     * Called by actionObjectUpdateAfter / actionObjectAddAfter.
     * Only acts when the hidden field seoo_config_submitted is present in POST,
     * indicating our SEO tab was part of the form submission.
     *
     * @param array<string, mixed> $params
     */
    private function processSeoConfigFromPost(array $params)
    {
        // Only process if our form fields were submitted
        if (!(int) Tools::getValue('seoo_config_submitted')) {
            return;
        }

        if (!isset($params['object']) || !($params['object'] instanceof ObjectModel)) {
            return;
        }

        $object = $params['object'];
        $idEntity = (int) $object->id;
        if ($idEntity <= 0) {
            return;
        }

        // Map ObjectModel class names to entity types
        // Category is handled by actionCategoryFormBuilderModifier + FormHandler
        $classMap = [
            // 'Product'      => 'product',
            // 'Manufacturer' => 'manufacturer',
            // 'Supplier'     => 'supplier',
            // 'CMS'          => 'cms',
        ];

        $className = get_class($object);
        // Handle namespaced class names (PS 9)
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }

        if (!isset($classMap[$className])) {
            return;
        }

        $noindex = (int) Tools::getValue('seoo_noindex');
        $nofollow = (int) Tools::getValue('seoo_nofollow');
        $langKeywords = Tools::getValue('seoo_keywords_lang');
        $langCanonical = Tools::getValue('seoo_canonical_url_lang');

        if (is_array($langKeywords) || is_array($langCanonical)) {
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                SeoOptimizerPage::saveSeoConfig($classMap[$className], $idEntity, [
                    'keywords' => is_array($langKeywords) && isset($langKeywords[$idLang])
                        ? (string) $langKeywords[$idLang]
                        : '',
                    'canonical_url' => is_array($langCanonical) && isset($langCanonical[$idLang])
                        ? (string) $langCanonical[$idLang]
                        : '',
                    'noindex' => $noindex,
                    'nofollow' => $nofollow,
                ], $idLang);
            }
        } else {
            SeoOptimizerPage::saveSeoConfig($classMap[$className], $idEntity, [
                'keywords' => (string) Tools::getValue('seoo_keywords'),
                'canonical_url' => (string) Tools::getValue('seoo_canonical_url'),
                'noindex' => $noindex,
                'nofollow' => $nofollow,
            ]);
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


    /**
     * @param array $params
     */
    public function hookActionCategoryFormBuilderModifier(array $params)
    {
        (new Adilis\SeoOptimizer\FormBuilderModifier\CategoryFormModifier($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionRootCategoryFormBuilderModifier(array $params)
    {
        (new Adilis\SeoOptimizer\FormBuilderModifier\CategoryFormModifier($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterUpdateCategoryFormHandler(array $params)
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterCreateCategoryFormHandler(array $params)
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterUpdateRootCategoryFormHandler(array $params)
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterCreateRootCategoryFormHandler(array $params)
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
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
     * @param array $params
     * @return string
     */
    public function hookDisplayAdminProductsSeoStepBottom(array $params): string
    {
        $idProduct = (int) ($params['id_product'] ?? Tools::getValue('id_product'));
        if (!$idProduct) {
            return '';
        }

        $url = $this->context->link->getProductLink(
            $idProduct,
            null,
            null,
            null,
            (int) $this->context->language->id
        );

        // SEO config panel is auto-injected by seo-config.js via SeoOptimizerConfig
        return $this->renderEntityAuditPanel($url);
    }

    /**
     * Output the SEO tab HTML in the BO footer for entity edit pages.
     * The JS (category-tabs.js / entity-form-tabs.js) moves it into the form.
     *
     * @return string
     */
    public function hookDisplayBackOfficeFooter(): string
    {
        $entityTabEntities = [
            'AdminCategories' => 'category',
        ];

        $currentController = Tools::getValue('controller');
        if (!isset($entityTabEntities[$currentController])) {
            return '';
        }

        $entityType = $entityTabEntities[$currentController];
        $idEntity = $this->detectEntityId($entityType);
        if ($idEntity <= 0) {
            return '';
        }

        return $this->renderEntitySeoTab($entityType, $idEntity);
    }

    /**
     * Render the SEO configuration panel for an entity.
     *
     * @param string $entityType
     * @param int $idEntity
     * @param string $url
     * @return string
     */
    private function renderSeoConfigPanel(string $entityType, int $idEntity, string $url): string
    {
        $config = SeoOptimizerPage::getSeoConfig($entityType, $idEntity);

        $languages = Language::getLanguages(false);
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $keywordsLang = [];
        $canonicalUrlLang = [];
        foreach ($languages as $lang) {
            $lid = (int) $lang['id_lang'];
            $langConfig = SeoOptimizerPage::getSeoConfig($entityType, $idEntity, $lid);
            $keywordsLang[$lid] = $langConfig['keywords'];
            $canonicalUrlLang[$lid] = $langConfig['canonical_url'];
        }

        $this->context->smarty->assign([
            'seoo_config_entity_type' => $entityType,
            'seoo_config_id_entity' => $idEntity,
            'seoo_config_url' => $url,
            'seoo_config_keywords' => $config['keywords'],
            'seoo_config_canonical_url' => $config['canonical_url'],
            'seoo_config_noindex' => $config['noindex'],
            'seoo_config_nofollow' => $config['nofollow'],
            'seoo_config_ajax_url' => $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => $this->name]
            ),
            'seoo_module_path' => $this->_path,
            'seoo_languages' => $languages,
            'seoo_default_lang' => $defaultLang,
            'seoo_keywords_lang' => $keywordsLang,
            'seoo_canonical_url_lang' => $canonicalUrlLang,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/seo-config.tpl');
    }

    /**
     * Render the complete server-rendered SEO tab for an entity.
     *
     * Used by entities that have dedicated tab JS (e.g. category).
     * Includes: Google Preview, native fields placeholder, custom SEO fields,
     * save button, and audit panel — all in a single template.
     *
     * @param string $entityType
     * @param int $idEntity
     * @return string
     */
    private function renderEntitySeoTab(string $entityType, int $idEntity): string
    {
        $idLang = (int) $this->context->language->id;
        $url = $this->getEntityUrl($entityType, $idEntity, $idLang);
        $ajaxUrl = $this->context->link->getAdminLink(
            'AdminModules',
            true,
            [],
            ['configure' => $this->name]
        );

        $this->context->smarty->assign([
            'seoo_entity_type' => $entityType,
            'seoo_id_entity' => $idEntity,
            'seoo_entity_url' => $url,
            'seoo_ajax_url' => $ajaxUrl,
            'seoo_module_path' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/' . $entityType . '-seo-tab.tpl');
    }

    /**
     * Render the inline entity audit panel for a given front-office URL.
     *
     * @param string $url
     * @return string
     */
    private function renderEntityAuditPanel(string $url): string
    {
        $this->context->smarty->assign([
            'seoo_entity_audit_url' => $url,
            'seoo_entity_audit_ajax_url' => $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => $this->name]
            ),
            'seoo_module_path' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/entity-audit.tpl');
    }

    // ──────────────────────────────────────────────
    // Symfony Grid hooks — SEO score column
    // ──────────────────────────────────────────────

    /**
     * Add a SEO grade DataColumn to a Symfony grid.
     *
     * @param array $params
     * @param string $afterColumn Column ID after which to insert
     */
    private function addSeoGradeColumnToGrid(array $params, string $afterColumn = 'active')
    {
        if (!(int) Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            return;
        }

        $columnClass = 'PrestaShop\\PrestaShop\\Core\\Grid\\Column\\Type\\DataColumn';
        if (!class_exists($columnClass)) {
            return;
        }

        $params['definition']->getColumns()->addAfter(
            $afterColumn,
            (new $columnClass('seo_grade'))
                ->setName('SEO')
                ->setOptions(['field' => 'seo_grade'])
        );
    }

    /**
     * Add LEFT JOIN on seooptimizer_page to a Symfony grid query builder.
     *
     * @param array $params
     * @param string $entityType
     * @param string $tableAlias Main table alias in the grid query
     * @param string $primaryKey Primary key column name
     */
    private function addSeoGradeJoinToGrid(array $params, string $entityType, string $tableAlias, string $primaryKey)
    {
        if (!(int) Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            return;
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $joinCondition = 'seoo_p.entity_type = "' . pSQL($entityType) . '"'
            . ' AND seoo_p.id_entity = ' . $tableAlias . '.' . $primaryKey
            . ' AND seoo_p.id_lang = ' . $idLang
            . ' AND seoo_p.id_shop = ' . $idShop;

        foreach (['search_query_builder', 'count_query_builder'] as $builder) {
            if (isset($params[$builder])) {
                $params[$builder]->leftJoin(
                    $tableAlias,
                    _DB_PREFIX_ . 'seooptimizer_page',
                    'seoo_p',
                    $joinCondition
                );
            }
        }

        if (isset($params['search_query_builder'])) {
            $params['search_query_builder']->addSelect(
                'IFNULL(seoo_p.grade, "-") AS seo_grade'
            );
        }
    }

    /** @param array $params */
    public function hookActionProductGridDefinitionModifier(array $params)
    {
        $this->addSeoGradeColumnToGrid($params, 'active');
    }

    /** @param array $params */
    public function hookActionProductGridQueryBuilderModifier(array $params)
    {
        $this->addSeoGradeJoinToGrid($params, 'product', 'p', 'id_product');
    }

    /** @param array $params */
    public function hookActionCategoryGridDefinitionModifier(array $params)
    {
        $this->addSeoGradeColumnToGrid($params, 'active');
    }

    /** @param array $params */
    public function hookActionCategoryGridQueryBuilderModifier(array $params)
    {
        $this->addSeoGradeJoinToGrid($params, 'category', 'c', 'id_category');
    }

    /** @param array $params */
    public function hookActionManufacturerGridDefinitionModifier(array $params)
    {
        $this->addSeoGradeColumnToGrid($params, 'active');
    }

    /** @param array $params */
    public function hookActionManufacturerGridQueryBuilderModifier(array $params)
    {
        $this->addSeoGradeJoinToGrid($params, 'manufacturer', 'm', 'id_manufacturer');
    }

    /** @param array $params */
    public function hookActionSupplierGridDefinitionModifier(array $params)
    {
        $this->addSeoGradeColumnToGrid($params, 'active');
    }

    /** @param array $params */
    public function hookActionSupplierGridQueryBuilderModifier(array $params)
    {
        $this->addSeoGradeJoinToGrid($params, 'supplier', 's', 'id_supplier');
    }

    /** @param array $params */
    public function hookActionCmsPageGridDefinitionModifier(array $params)
    {
        $this->addSeoGradeColumnToGrid($params, 'active');
    }

    /** @param array $params */
    public function hookActionCmsPageGridQueryBuilderModifier(array $params)
    {
        $this->addSeoGradeJoinToGrid($params, 'cms', 'c', 'id_cms');
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
