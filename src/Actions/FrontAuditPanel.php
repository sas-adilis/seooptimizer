<?php

namespace Adilis\SeoOptimizer\Actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Front-office audit panel, shown to employees browsing the shop.
 */
class FrontAuditPanel
{
    public static function isActive(): bool
    {
        if (!(int) \Configuration::get('SEOO_FRONT_AUDIT_ENABLED')) {
            return false;
        }

        $adminCookie = new \Cookie('psAdmin');

        return !empty($adminCookie->id_employee);
    }

    /**
     * @param \Module $module
     */
    public static function registerAssets(\Module $module)
    {
        $controller = \Context::getContext()->controller;

        $controller->registerStylesheet(
            'module-seooptimizer-front-audit',
            'modules/' . $module->name . '/views/css/front-audit.css',
            ['media' => 'all', 'priority' => 200]
        );
        $controller->registerJavascript(
            'module-seooptimizer-front-audit',
            'modules/' . $module->name . '/views/js/front-audit.js',
            ['position' => 'bottom', 'priority' => 200]
        );

        \Media::addJsDef([
            'SeoOptimizerFrontAudit' => [
                'ajaxUrl' => \Context::getContext()->link->getModuleLink($module->name, 'pageaudit'),
                'currentUrl' => \Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            ],
        ]);
    }
}
