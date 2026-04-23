{* SEO Configuration — Inline panel for BO entity pages (product, category, etc.) *}
<div class="seoo-sc" id="seoo-sc"
     data-entity-type="{$seoo_config_entity_type|escape:'htmlall':'UTF-8'}"
     data-id-entity="{$seoo_config_id_entity|intval}"
     data-url="{$seoo_config_url|escape:'htmlall':'UTF-8'}"
     data-ajax-url="{$seoo_config_ajax_url|escape:'htmlall':'UTF-8'}">

    {* ── Header ── *}
    <div class="seoo-sc__header">
        <div class="seoo-sc__title-row">
            <img class="seoo-sc__logo" src="{$seoo_module_path|escape:'htmlall':'UTF-8'}logo.png" alt="SEO Optimizer">
            <h4 class="seoo-sc__title">{l s='SEO Configuration' mod='seooptimizer'}</h4>
            <span id="seoo-sc-status" class="seoo-sc__status seoo-sc__status--idle"></span>
        </div>
    </div>

    <div class="seoo-sc__body">

        {* ── Google Preview ── *}
        <div class="seoo-sc__section seoo-sc__section--preview">
            <div class="seoo-sc__section-label">
                <span class="seoo-sc__section-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M224,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48Zm0,144H32V64H224V192ZM136,112a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h48A8,8,0,0,1,136,112Zm64,0a8,8,0,0,1-8,8H168a8,8,0,0,1,0-16h24A8,8,0,0,1,200,112Zm-64,32a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h48A8,8,0,0,1,136,144Zm64,0a8,8,0,0,1-8,8H168a8,8,0,0,1,0-16h24A8,8,0,0,1,200,144Z"/></svg></span>
                {l s='Google Preview' mod='seooptimizer'}
            </div>
            <div class="seoo-sc__serp" id="seoo-sc-serp">
                <cite class="seoo-sc__serp-url" id="seoo-sc-serp-url">{$seoo_config_url|escape:'htmlall':'UTF-8'}</cite>
                <h3 class="seoo-sc__serp-title" id="seoo-sc-serp-title">{l s='Loading...' mod='seooptimizer'}</h3>
                <p class="seoo-sc__serp-desc" id="seoo-sc-serp-desc"></p>
            </div>
        </div>

        {* ── Focus Keywords (per language) ── *}
        <div class="seoo-sc__section">
            <label class="seoo-sc__label">
                <span class="seoo-sc__section-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M229.66,218.34l-50.06-50.06a88.21,88.21,0,1,0-11.32,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"/></svg></span>
                {l s='Focus Keywords' mod='seooptimizer'}
            </label>
            {if isset($seoo_languages) && count($seoo_languages) > 1}
                <div class="seoo-sc__lang-tabs" data-field="keywords">
                    {foreach $seoo_languages as $lang}
                        <button type="button"
                                class="seoo-sc__lang-tab{if $lang.id_lang == $seoo_default_lang} seoo-sc__lang-tab--active{/if}"
                                data-lang="{$lang.id_lang|intval}">
                            {$lang.iso_code|escape:'htmlall':'UTF-8'}
                        </button>
                    {/foreach}
                </div>
                {foreach $seoo_languages as $lang}
                    <div class="seoo-sc__lang-field{if $lang.id_lang != $seoo_default_lang} seoo-sc__lang-field--hidden{/if}"
                         data-lang-field="keywords-{$lang.id_lang|intval}">
                        <input type="text"
                               id="seoo_keywords_{$lang.id_lang|intval}"
                               class="seoo-sc__input"
                               value="{if isset($seoo_keywords_lang[$lang.id_lang])}{$seoo_keywords_lang[$lang.id_lang]|escape:'htmlall':'UTF-8'}{/if}"
                               placeholder="{l s='e.g. chaussures running, baskets sport' mod='seooptimizer'}">
                    </div>
                {/foreach}
            {else}
                <input type="text"
                       id="seoo_keywords"
                       class="seoo-sc__input"
                       value="{$seoo_config_keywords|escape:'htmlall':'UTF-8'}"
                       placeholder="{l s='e.g. chaussures running, baskets sport' mod='seooptimizer'}">
            {/if}
            <p class="seoo-sc__help">{l s='Comma-separated keywords that this page should rank for.' mod='seooptimizer'}</p>
        </div>

        {* ── Canonical URL (per language) ── *}
        <div class="seoo-sc__section">
            <label class="seoo-sc__label">
                <span class="seoo-sc__section-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M137.54,186.36a8,8,0,0,1,0,11.31l-9.94,10A56,56,0,0,1,48.38,128.4L72.5,104.28A56,56,0,0,1,149.31,102a8,8,0,1,1-10.64,12,40,40,0,0,0-54.85,1.63L59.7,139.72a40,40,0,0,0,56.58,56.58l9.94-9.94A8,8,0,0,1,137.54,186.36Zm70.08-138a56.06,56.06,0,0,0-79.22,0l-9.94,9.95a8,8,0,0,0,11.32,11.31l9.94-9.94a40,40,0,0,1,56.58,56.58L172.18,140.4A40,40,0,0,1,117.33,142,8,8,0,1,0,106.69,154a56,56,0,0,0,76.81-2.26l24.12-24.12A56.06,56.06,0,0,0,207.62,48.38Z"/></svg></span>
                {l s='Canonical URL' mod='seooptimizer'}
            </label>
            {if isset($seoo_languages) && count($seoo_languages) > 1}
                <div class="seoo-sc__lang-tabs" data-field="canonical">
                    {foreach $seoo_languages as $lang}
                        <button type="button"
                                class="seoo-sc__lang-tab{if $lang.id_lang == $seoo_default_lang} seoo-sc__lang-tab--active{/if}"
                                data-lang="{$lang.id_lang|intval}">
                            {$lang.iso_code|escape:'htmlall':'UTF-8'}
                        </button>
                    {/foreach}
                </div>
                {foreach $seoo_languages as $lang}
                    <div class="seoo-sc__lang-field{if $lang.id_lang != $seoo_default_lang} seoo-sc__lang-field--hidden{/if}"
                         data-lang-field="canonical-{$lang.id_lang|intval}">
                        <input type="url"
                               id="seoo_canonical_url_{$lang.id_lang|intval}"
                               class="seoo-sc__input"
                               value="{if isset($seoo_canonical_url_lang[$lang.id_lang])}{$seoo_canonical_url_lang[$lang.id_lang]|escape:'htmlall':'UTF-8'}{/if}"
                               placeholder="{l s='Leave empty for default (current page URL)' mod='seooptimizer'}">
                    </div>
                {/foreach}
            {else}
                <input type="url"
                       id="seoo_canonical_url"
                       class="seoo-sc__input"
                       value="{$seoo_config_canonical_url|escape:'htmlall':'UTF-8'}"
                       placeholder="{l s='Leave empty for default (current page URL)' mod='seooptimizer'}">
            {/if}
            <p class="seoo-sc__help">{l s='Custom canonical URL for this page. Cross-domain supported.' mod='seooptimizer'}</p>
        </div>

        {* ── Indexation & Follow ── *}
        <div class="seoo-sc__section seoo-sc__section--row">
            <div class="seoo-sc__field-group">
                <label class="seoo-sc__label" for="seoo_noindex">
                    <span class="seoo-sc__section-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM104,56h48a8,8,0,0,0,0-16H104a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z"/></svg></span>
                    {l s='Indexation' mod='seooptimizer'}
                </label>
                <select id="seoo_noindex" class="seoo-sc__select">
                    <option value="0"{if $seoo_config_noindex == 0} selected{/if}>{l s='Default (index)' mod='seooptimizer'}</option>
                    <option value="1"{if $seoo_config_noindex == 1} selected{/if}>{l s='Noindex — hide from search engines' mod='seooptimizer'}</option>
                </select>
            </div>

            <div class="seoo-sc__field-group">
                <label class="seoo-sc__label" for="seoo_nofollow">
                    <span class="seoo-sc__section-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M200,64V168a8,8,0,0,1-16,0V83.31L69.66,197.66a8,8,0,0,1-11.32-11.32L172.69,72H88a8,8,0,0,1,0-16H192A8,8,0,0,1,200,64Z"/></svg></span>
                    {l s='Link following' mod='seooptimizer'}
                </label>
                <select id="seoo_nofollow" class="seoo-sc__select">
                    <option value="0"{if $seoo_config_nofollow == 0} selected{/if}>{l s='Default (follow)' mod='seooptimizer'}</option>
                    <option value="1"{if $seoo_config_nofollow == 1} selected{/if}>{l s='Nofollow — do not follow links' mod='seooptimizer'}</option>
                </select>
            </div>
        </div>

        {* ── Save button ── *}
        <div class="seoo-sc__actions">
            <button type="button" id="seoo-sc-save" class="btn btn-primary btn-sm">
                <i class="icon-save"></i> {l s='Save SEO settings' mod='seooptimizer'}
            </button>
        </div>
    </div>
</div>
