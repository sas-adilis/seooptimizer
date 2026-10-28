<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerIndexationRule.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerLog404.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerPage.php';
require_once dirname(__FILE__) . '/classes/SeoOptimizerRedirect.php';

use Adilis\SeoOptimizer\Actions;
use Adilis\SeoOptimizer\Admin\AjaxHandler;
use Adilis\SeoOptimizer\Admin\AssetsLoader;
use Adilis\SeoOptimizer\Admin\ConfigurePage;
use Adilis\SeoOptimizer\Admin\EntityHelper;
use Adilis\SeoOptimizer\Admin\EntityPanel;
use Adilis\SeoOptimizer\Admin\GridScoreColumn;
use Adilis\SeoOptimizer\Constants;
use Adilis\SeoOptimizer\Routes;

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
        $this->version = '1.8.0';
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
            'SEOO_PERF_THRESHOLD_GOOD' => 800,
            'SEOO_PERF_THRESHOLD_SLOW' => 1800,
            'SEOO_WEIGHT_THRESHOLD_LIGHT' => 1024,
            'SEOO_WEIGHT_THRESHOLD_HEAVY' => 3072,
            'SEOO_TEXT_THRESHOLD_LOW' => 100,
            'SEOO_TEXT_THRESHOLD_GOOD' => 300,
            'SEOO_FRONT_AUDIT_ENABLED' => 0,
            'SEOO_BO_SCORE_COLUMN' => 0,
            'SEOO_PSI_API_KEY' => '',
            'SEOO_PSI_PAGES_PER_TYPE' => 3,

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

    // ──────────────────────────────────────────────
    // Back office
    // ──────────────────────────────────────────────

    public function hookBackOfficeHeader(): void
    {
        $assets = new AssetsLoader($this);
        $currentController = (string) Tools::getValue('controller');

        if (Tools::getValue('configure') === $this->name) {
            $assets->loadConfigurePage($this->getRobotsI18n(), $this->getLlmsI18n());
        }

        $assets->loadEntityPage($currentController, function () {
            return $this->getSeoConfigI18n();
        });
        $assets->loadScoreColumn($currentController);
    }

    /**
     * Returns the page HTML, or the result of an AJAX action (which may be null or exit).
     *
     * @return string|null
     *
     * @throws PrestaShopException
     */
    public function getContent()
    {
        // Dispatch AJAX calls
        if ((int) Tools::getValue('ajax')) {
            // Buffered so that a stray notice cannot break the JSON answers (see JsonResponse)
            ob_start();
            $action = Tools::getValue('action');
            if (!empty($action)) {
                $ajax = new AjaxHandler($this);
                if ($ajax->has($action)) {
                    return $ajax->dispatch($action);
                }
            }
        }

        return (new ConfigurePage($this))->render();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function hookDisplayAdminProductsSeoStepBottom(array $params): string
    {
        $idProduct = (int) ($params['id_product'] ?? Tools::getValue('id_product'));
        if (!$idProduct) {
            return '';
        }

        $url = EntityHelper::getEntityUrl('product', $idProduct, (int) $this->context->language->id);

        // SEO config panel is auto-injected by seo-config.js via SeoOptimizerConfig
        return (new EntityPanel($this))->renderAuditPanel($url);
    }

    /**
     * Output the SEO tab HTML in the BO footer for entity edit pages.
     * The JS (category-tabs.js / entity-form-tabs.js) moves it into the form.
     */
    public function hookDisplayBackOfficeFooter(): string
    {
        return (new EntityPanel($this))->renderFooterTab((string) Tools::getValue('controller'));
    }

    public function hookActionSupplierFormBuilderModifier(array $params): void
    {
        (new Adilis\SeoOptimizer\FormBuilderModifier\SupplierFormModifier($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionCategoryFormBuilderModifier(array $params): void
    {
        (new Adilis\SeoOptimizer\FormBuilderModifier\CategoryFormModifier($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionRootCategoryFormBuilderModifier(array $params): void
    {
        (new Adilis\SeoOptimizer\FormBuilderModifier\CategoryFormModifier($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterUpdateCategoryFormHandler(array $params): void
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterCreateCategoryFormHandler(array $params): void
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterUpdateRootCategoryFormHandler(array $params): void
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    /**
     * @param array $params
     */
    public function hookActionAfterCreateRootCategoryFormHandler(array $params): void
    {
        (new Adilis\SeoOptimizer\FormHandler\CategorySeoFormHandler($params))->process();
    }

    // Symfony grid hooks — SEO score column

    /** @param array $params */
    public function hookActionProductGridDefinitionModifier(array $params): void
    {
        GridScoreColumn::addColumn($params);
    }

    /** @param array $params */
    public function hookActionProductGridQueryBuilderModifier(array $params): void
    {
        GridScoreColumn::addJoin($params, 'product', 'p', 'id_product');
    }

    /** @param array $params */
    public function hookActionCategoryGridDefinitionModifier(array $params): void
    {
        GridScoreColumn::addColumn($params);
    }

    /** @param array $params */
    public function hookActionCategoryGridQueryBuilderModifier(array $params): void
    {
        GridScoreColumn::addJoin($params, 'category', 'c', 'id_category');
    }

    /** @param array $params */
    public function hookActionManufacturerGridDefinitionModifier(array $params): void
    {
        GridScoreColumn::addColumn($params);
    }

    /** @param array $params */
    public function hookActionManufacturerGridQueryBuilderModifier(array $params): void
    {
        GridScoreColumn::addJoin($params, 'manufacturer', 'm', 'id_manufacturer');
    }

    /** @param array $params */
    public function hookActionSupplierGridDefinitionModifier(array $params): void
    {
        GridScoreColumn::addColumn($params);
    }

    /** @param array $params */
    public function hookActionSupplierGridQueryBuilderModifier(array $params): void
    {
        GridScoreColumn::addJoin($params, 'supplier', 's', 'id_supplier');
    }

    /** @param array $params */
    public function hookActionCmsPageGridDefinitionModifier(array $params): void
    {
        GridScoreColumn::addColumn($params);
    }

    /** @param array $params */
    public function hookActionCmsPageGridQueryBuilderModifier(array $params): void
    {
        GridScoreColumn::addJoin($params, 'cms', 'c', 'id_cms');
    }

    // ──────────────────────────────────────────────
    // Front office
    // ──────────────────────────────────────────────

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayBeforeBodyClosingTag(): string
    {
        (new Actions\WatchPageNotFound())->run();
        (new Actions\RichSnippetsGenerate())->run();

        if (Actions\FrontAuditPanel::isActive()) {
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
    public function hookDisplayHeader(array $params): string
    {
        // Apply meta templates for pages with empty meta title/description
        Adilis\SeoOptimizer\MetaTemplate\MetaTemplateEngine::apply();

        (new Actions\PageIndexation())->run();

        $html_content = (new Actions\CanonicalUrlGenerate())->run();
        $html_content .= (new Actions\SocialMetadata())->run();

        // Applied AFTER CanonicalUrlGenerate so a custom canonical overrides the auto-generated one
        (new Actions\PerEntitySeoConfig())->run();

        $this->context->smarty->assign([
            'seoo_verification_code_google' => Configuration::get('SEOO_CODE_VERIFICATION_GOOGLE'),
            'seoo_verification_code_bing' => Configuration::get('SEOO_CODE_VERIFICATION_BING'),
            'seoo_verification_code_pinterest' => Configuration::get('SEOO_CODE_VERIFICATION_PINTEREST'),
        ]);

        if (Actions\FrontAuditPanel::isActive()) {
            Actions\FrontAuditPanel::registerAssets($this);
        }

        return $this->display(__FILE__, 'header.tpl') . $html_content;
    }

    public function hookActionFrontControllerInitBefore(array $params): void
    {
        (new Actions\RedirectRequest())->run();
    }

    /**
     * @throws PrestaShopException
     */
    public function hookActionObjectDeleteBefore(array $params): void
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

    public function hookActionOutputHTMLBefore(array $params): void
    {
        (new Adilis\SeoOptimizer\HtmlOutputBefore\LinkObfuscator($params))->process($params['html']);
    }

    public function hookModuleRoutes(array $params): array
    {
        return Routes::get();
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

    // ──────────────────────────────────────────────
    // Translated strings passed to the JS
    //
    // Kept here (not in src/) so the translation keys stay attached to this file.
    // ──────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    private function getRobotsI18n(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getLlmsI18n(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getSeoConfigI18n(): array
    {
        return [
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
        ];
    }
}
