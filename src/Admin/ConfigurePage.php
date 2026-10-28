<?php

namespace Adilis\SeoOptimizer\Admin;

use Adilis\SeoOptimizer\Audit\AuditRegistry;
use Adilis\SeoOptimizer\Audit\AuditRunner;
use Adilis\SeoOptimizer\Pages\PagesAggregator;
use Adilis\SeoOptimizer\Score\SeoScoreCalculator;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Renders the module configuration page (forms, data lists, audits, pages overview).
 */
class ConfigurePage
{
    /** @var \Module */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $context = \Context::getContext();
        $smarty = $context->smarty;

        // Pre-assign all Smarty variables with empty defaults to avoid
        // "Undefined array key" warnings in PS 9 if a process() fails
        $smarty->assign([
            'form_redirection_edit' => '',
            'form_redirection' => '',
            'form_redirection_import' => '',
            'form_robots_txt' => '',
            'form_llms_txt' => '',
            'form_page_speed' => '',
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
            'audit_indexation' => '',
            'audit_social_tags' => '',
        ]);

        foreach ($this->getContentClasses() as $class) {
            try {
                (new $class())->process();
            } catch (\Throwable $e) {
                $this->log($class . ' process() failed: ' . $e->getMessage());
            }
        }

        // Audits (crawler-based)
        foreach (AuditRegistry::getAll() as $audit) {
            try {
                (new AuditRunner($audit))->process();
            } catch (\Throwable $e) {
                $this->log('AuditRunner ' . $audit->getKey() . ' failed: ' . $e->getMessage());
            }
        }

        $seoScores = (new SeoScoreCalculator())->compute();

        // Pages overview
        $pagesAggregator = new PagesAggregator();
        $pagesData = $pagesAggregator->aggregate();
        $totalCritical = 0;
        $totalWarnings = 0;
        $pagesWithIssues = 0;
        foreach ($pagesData as $p) {
            $totalCritical += $p['critical'];
            $totalWarnings += $p['warning'];
            if ($p['total'] > 0) {
                ++$pagesWithIssues;
            }
        }

        // Assign pages variables first so pages.tpl can be rendered
        $smarty->assign([
            'seoo_module_path' => $this->module->getPathUri(),
            'seoo_pages_data' => $pagesData,
            'seoo_pages_has_data' => $pagesAggregator->hasData(),
            'seoo_pages_total' => count($pagesData),
            'seoo_pages_with_issues' => $pagesWithIssues,
            'seoo_pages_critical' => $totalCritical,
            'seoo_pages_warnings' => $totalWarnings,
            'seoo_pages_clean' => count($pagesData) - $pagesWithIssues,
        ]);

        $smarty->assign([
            'seoo_scores' => $seoScores,
            'seoo_export_redirections_url' => $context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => $this->module->name, 'exportdata_list_redirections' => 1]
            ),
            'seoo_pages_html' => $smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/pages.tpl'),
        ]);

        return $smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/configure.tpl');
    }

    /**
     * @return string[]
     */
    private function getContentClasses(): array
    {
        $ns = 'Adilis\\SeoOptimizer\\';

        return [
            $ns . 'Form\\FormRedirectionEdit',
            $ns . 'Form\\FormRedirection',
            $ns . 'Form\\FormRedirectionImport',
            $ns . 'Form\\FormRobotsTxt',
            $ns . 'Form\\FormLlmsTxt',
            $ns . 'Form\\FormPageSpeed',
            $ns . 'Form\\FormCanonicalUrls',
            $ns . 'Form\\FormIndexation',
            $ns . 'Form\\FormVerificationCode',
            $ns . 'Form\\FormSettings',
            $ns . 'Form\\FormIndexationRule',
            $ns . 'Form\\FormRichSnippets',
            $ns . 'Form\\FormSocial',
            $ns . 'Form\\FormSitemap',
            $ns . 'Form\\FormSocialMetaData',
            $ns . 'Form\\FormLinkObfuscator',
            $ns . 'Form\\FormMetaTemplates',

            $ns . 'Content\\DataList\\DataListRedirections',
            $ns . 'Content\\DataList\\DataListIndexationRules',
            $ns . 'Content\\DataList\\DataListPagesNotFound',
        ];
    }

    private function log(string $message)
    {
        \PrestaShopLogger::addLog('SeoOptimizer: ' . $message, 3, null, 'SeoOptimizer');
    }
}
