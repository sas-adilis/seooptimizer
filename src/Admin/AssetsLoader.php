<?php

namespace Adilis\SeoOptimizer\Admin;

use Adilis\SeoOptimizer\Entity\EntityRegistry;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Back-office CSS/JS and JS definitions loaded from hookBackOfficeHeader.
 *
 * Translated strings are built by the module (so that translation keys stay
 * attached to the main module file) and passed in.
 */
class AssetsLoader
{
    /** @var \Module */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * Assets of the module configuration page.
     *
     * @param array<string, string> $robotsI18n
     * @param array<string, string> $llmsI18n
     */
    public function loadConfigurePage(array $robotsI18n, array $llmsI18n)
    {
        $path = $this->module->getPathUri();
        $controller = \Context::getContext()->controller;

        $controller->addCSS($path . 'views/css/tabs.17.css', 'all');
        $controller->addCSS($path . 'views/css/table.17.css', 'all');
        $controller->addCSS($path . 'views/css/seooptimizer.css', 'all');

        // Chart.js is no longer provided by the PS 9 back office
        $controller->addJS($path . 'views/js/vendor/chart.umd.js');
        $controller->addJS($path . 'views/js/seooptimizer.js');
        $controller->addJS($path . 'views/js/audit.js');
        $controller->addJS($path . 'views/js/pages.js');
        $controller->addJS($path . 'views/js/robots-txt.js');
        $controller->addJS($path . 'views/js/llms-txt.js');

        $shopUrl = rtrim(\Context::getContext()->shop->getBaseURL(true), '/');

        \Media::addJsDef([
            'SeoOptimizerAjaxUrl' => $this->getAjaxUrl(),
            'SeoOptimizerRobots' => [
                'shopUrl' => $shopUrl,
                'presets' => (new \Adilis\SeoOptimizer\Form\FormRobotsTxt())->getPresetsContentPublic(),
                'i18n' => $robotsI18n,
            ],
            'SeoOptimizerLlms' => [
                'presets' => (new \Adilis\SeoOptimizer\Form\FormLlmsTxt())->getPresetsContentPublic(),
                'i18n' => $llmsI18n,
            ],
        ]);
    }

    /**
     * Assets of the entity edit pages (SEO config panel + entity audit).
     *
     * @param string $currentController Current admin controller name
     * @param callable $seoConfigI18n Returns the i18n strings of the legacy SEO config panel
     */
    public function loadEntityPage(string $currentController, callable $seoConfigI18n)
    {
        $path = $this->module->getPathUri();
        $controller = \Context::getContext()->controller;

        if ($currentController === 'AdminSuppliers') {
            $controller->addJS($path . 'views/js/supplier-form.js');
        }

        if (!isset(EntityHelper::CONTROLLER_MAP[$currentController])) {
            return;
        }

        $entityType = EntityHelper::CONTROLLER_MAP[$currentController];

        // Common CSS for all entities
        $controller->addCSS($path . 'views/css/front-audit.css', 'all');
        $controller->addCSS($path . 'views/css/entity-audit.css', 'all');
        $controller->addCSS($path . 'views/css/seo-config.css', 'all');

        if (in_array($entityType, EntityHelper::TAB_ENTITIES, true)) {
            // Server-rendered HTML, JS only handles tab + DOM moves
            $controller->addJS($path . 'views/js/entity-audit.js');
            $controller->addJS($path . 'views/js/entity-form-tabs.js');
            $controller->addJS($path . 'views/js/' . $entityType . '-tabs.js');

            // Pass available tags for meta template insertion
            $entity = EntityRegistry::get($entityType);
            if ($entity) {
                \Media::addJsDef([
                    'SeoOptimizerTags' => $entity->getAvailableTags(),
                ]);
            }

            return;
        }

        // Legacy approach: JS-built panel
        $controller->addJS($path . 'views/js/entity-audit.js');
        $controller->addJS($path . 'views/js/seo-config.js');

        $idEntity = EntityHelper::detectEntityId($this->module, $entityType);
        if ($idEntity <= 0) {
            return;
        }

        $context = \Context::getContext();
        $idLang = (int) $context->language->id;

        $languages = [];
        foreach (\Language::getLanguages(false) as $lang) {
            $languages[] = [
                'id_lang' => (int) $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
            ];
        }

        \Media::addJsDef([
            'SeoOptimizerConfig' => [
                'ajaxUrl' => $this->getAjaxUrl(),
                'entityType' => $entityType,
                'idEntity' => $idEntity,
                'entityUrl' => EntityHelper::getEntityUrl($entityType, $idEntity, $idLang),
                'logoUrl' => $path . 'logo.png',
                'languages' => $languages,
                'defaultLang' => (int) \Configuration::get('PS_LANG_DEFAULT'),
                'i18n' => $seoConfigI18n(),
            ],
        ]);
    }

    /**
     * Score column: badge + batch audit + side panel in BO listings.
     *
     * @param string $currentController Current admin controller name
     */
    public function loadScoreColumn(string $currentController)
    {
        if (!isset(EntityHelper::CONTROLLER_MAP[$currentController]) || !(int) \Configuration::get('SEOO_BO_SCORE_COLUMN')) {
            return;
        }

        $path = $this->module->getPathUri();
        $controller = \Context::getContext()->controller;

        $controller->addCSS($path . 'views/css/entity-audit.css', 'all');
        $controller->addCSS($path . 'views/css/front-audit.css', 'all');
        $controller->addJS($path . 'views/js/bo-score-column-grid.js');
        $controller->addJS($path . 'views/js/bo-score-column.js');

        \Media::addJsDef([
            'SeoOptimizerScoreColumn' => [
                'ajaxUrl' => $this->getAjaxUrl(),
                'entityType' => EntityHelper::CONTROLLER_MAP[$currentController],
                'moduleUrl' => $this->getAjaxUrl(),
            ],
        ]);
    }

    private function getAjaxUrl(): string
    {
        return \Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => $this->module->name]);
    }
}
