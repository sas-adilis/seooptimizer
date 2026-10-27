<?php

namespace Adilis\SeoOptimizer\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Server-rendered SEO panels injected in the entity edit pages of the back office.
 */
class EntityPanel
{
    /** @var \Module */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * SEO tab HTML output in the BO footer for entity edit pages.
     * The JS (category-tabs.js / entity-form-tabs.js) moves it into the form.
     *
     * @param string $currentController Current admin controller name
     *
     * @return string
     */
    public function renderFooterTab(string $currentController): string
    {
        if (
            !isset(EntityHelper::CONTROLLER_MAP[$currentController])
            || !in_array(EntityHelper::CONTROLLER_MAP[$currentController], EntityHelper::TAB_ENTITIES, true)
        ) {
            return '';
        }

        $entityType = EntityHelper::CONTROLLER_MAP[$currentController];
        $idEntity = EntityHelper::detectEntityId($this->module, $entityType);
        if ($idEntity <= 0) {
            return '';
        }

        return $this->renderSeoTab($entityType, $idEntity);
    }

    /**
     * Complete server-rendered SEO tab for an entity (Google Preview, native fields
     * placeholder, custom SEO fields, save button and audit panel in a single template).
     *
     * @param string $entityType
     * @param int $idEntity
     *
     * @return string
     */
    public function renderSeoTab(string $entityType, int $idEntity): string
    {
        $idLang = (int) \Context::getContext()->language->id;

        \Context::getContext()->smarty->assign([
            'seoo_entity_type' => $entityType,
            'seoo_id_entity' => $idEntity,
            'seoo_entity_url' => EntityHelper::getEntityUrl($entityType, $idEntity, $idLang),
            'seoo_ajax_url' => $this->getAjaxUrl(),
            'seoo_module_path' => $this->module->getPathUri(),
        ]);

        return $this->display('views/templates/admin/' . $entityType . '-seo-tab.tpl');
    }

    /**
     * Inline entity audit panel for a given front-office URL.
     *
     * @param string $url
     *
     * @return string
     */
    public function renderAuditPanel(string $url): string
    {
        \Context::getContext()->smarty->assign([
            'seoo_entity_audit_url' => $url,
            'seoo_entity_audit_ajax_url' => $this->getAjaxUrl(),
            'seoo_module_path' => $this->module->getPathUri(),
        ]);

        return $this->display('views/templates/admin/entity-audit.tpl');
    }

    private function getAjaxUrl(): string
    {
        return \Context::getContext()->link->getAdminLink('AdminModules', true, [], ['configure' => $this->module->name]);
    }

    private function display(string $template): string
    {
        return (string) $this->module->display($this->module->getLocalPath() . $this->module->name . '.php', $template);
    }
}
