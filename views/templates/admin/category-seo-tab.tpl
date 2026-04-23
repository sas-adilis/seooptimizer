{*
 * SEO Optimizer — Entity SEO Block (injected at bottom of entity form)
 *
 * Rendered in the BO footer by hookDisplayBackOfficeFooter.
 * JS (category-tabs.js) moves this block into the entity form
 * and relocates native PS SEO fields into #seoo-native-seo-fields.
 *}
<div id="seoo-{$seoo_entity_type|escape:'htmlall':'UTF-8'}-seo-tab"
     class="seoo-entity-seo-block"
     style="display:none"
     data-ajax-url="{$seoo_ajax_url|escape:'htmlall':'UTF-8'}"
     data-entity-type="{$seoo_entity_type|escape:'htmlall':'UTF-8'}"
     data-id-entity="{$seoo_id_entity|intval}"
     data-entity-url="{$seoo_entity_url|escape:'htmlall':'UTF-8'}">

    {* ── Header SEO avec panda ── *}
    <div class="seoo-block__header">
        <img class="seoo-block__logo" src="{$seoo_module_path|escape:'htmlall':'UTF-8'}logo.png" alt="SEO Optimizer" width="32" height="32">
        <div class="seoo-block__header-text">
            <h3 class="seoo-block__title">SEO Optimizer</h3>
            <p class="seoo-block__subtitle">{l s='Search engine optimization settings' mod='seooptimizer'}</p>
        </div>
    </div>

    {* ── Google SERP Preview — rendered by SerpPreviewType via form builder ── *}
    <div id="seoo-serp-preview-slot"></div>

    {* ── Native PS SEO fields are moved here by JS ── *}
    <div id="seoo-native-seo-fields"></div>

    <div class="seoo-sc__separator"></div>

    {* ── SEO Optimizer custom fields (keywords, canonical, noindex, nofollow) ── *}
    {* All fields are added via actionCategoryFormBuilderModifier *}
    {* and moved here by JS from the Symfony form into #seoo-custom-seo-fields *}
    <div id="seoo-custom-seo-fields"></div>

    {* ── Audit panel ── *}
    <div class="seoo-ea" id="seoo-ea"
         data-url="{$seoo_entity_url|escape:'htmlall':'UTF-8'}"
         data-ajax-url="{$seoo_ajax_url|escape:'htmlall':'UTF-8'}">
        <div class="seoo-ea__header">
            <div class="seoo-ea__title-row">
                <h4 class="seoo-ea__title">{l s='SEO Audit' mod='seooptimizer'}</h4>
                <span id="seoo-ea-badge" class="seoo-ea__badge seoo-ea__badge--loading">
                    <span class="seoo-fa-spinner"></span>
                </span>
            </div>
            <div class="seoo-ea__actions">
                <a href="{$seoo_entity_url|escape:'htmlall':'UTF-8'}" target="_blank"
                   class="btn btn-default btn-sm"
                   title="{l s='View page' mod='seooptimizer'}">
                    <i class="icon-external-link"></i>
                </a>
                <button type="button" class="btn btn-default btn-sm" id="seoo-ea-refresh"
                        title="{l s='Re-audit' mod='seooptimizer'}">
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
</div>