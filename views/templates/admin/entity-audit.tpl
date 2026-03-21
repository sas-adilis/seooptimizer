{* SEO Audit — Inline panel for BO entity pages (product, category, etc.) *}
<div class="seoo-ea" id="seoo-ea"
     data-url="{$seoo_entity_audit_url|escape:'htmlall':'UTF-8'}"
     data-ajax-url="{$seoo_entity_audit_ajax_url|escape:'htmlall':'UTF-8'}">
    <div class="seoo-ea__header">
        <div class="seoo-ea__title-row">
            <img class="seoo-ea__logo" src="{$seoo_module_path|escape:'htmlall':'UTF-8'}logo.png" alt="SEO Optimizer">
            <h4 class="seoo-ea__title">SEO Audit</h4>
            <span id="seoo-ea-badge" class="seoo-ea__badge seoo-ea__badge--loading">
                <span class="seoo-fa-spinner"></span>
            </span>
        </div>
        <div class="seoo-ea__actions">
            <a href="{$seoo_entity_audit_url|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-default btn-sm" title="{l s='View page' mod='seooptimizer'}">
                <i class="icon-external-link"></i>
            </a>
            <button type="button" class="btn btn-default btn-sm" id="seoo-ea-refresh" title="{l s='Re-audit' mod='seooptimizer'}">
                <i class="icon-refresh"></i>
            </button>
        </div>
    </div>
    <div id="seoo-ea-body" class="seoo-ea__body">
        <div class="seoo-fa-loading">
            <div class="seoo-fa-spinner seoo-fa-spinner--large"></div>
            <p>{l s='Analyse SEO en cours...' mod='seooptimizer'}</p>
        </div>
    </div>
</div>
