{* SEO Audit Panel — body content rendered server-side via pageaudit AJAX controller *}

{* -- Reusable Smarty functions -- *}

{function name=seoo_check status='' title='' message=''}
    <div class="seoo-fa-check seoo-fa-check--{$status|escape:'htmlall':'UTF-8'}">
        <span class="seoo-fa-icon seoo-fa-icon--{$status|escape:'htmlall':'UTF-8'}">
            {if $status == 'good'}{include file="module:seooptimizer/views/icons/check-circle.svg"}
            {elseif $status == 'warning'}{include file="module:seooptimizer/views/icons/warning.svg"}
            {elseif $status == 'critical'}{include file="module:seooptimizer/views/icons/x-circle.svg"}
            {else}{include file="module:seooptimizer/views/icons/info.svg"}{/if}
        </span>
        <div class="seoo-fa-check__text">
            <div class="seoo-fa-check__title">{$title|escape:'htmlall':'UTF-8'}</div>
            {if $message}<div class="seoo-fa-check__msg">{$message|escape:'htmlall':'UTF-8'}</div>{/if}
        </div>
    </div>
{/function}

{if isset($seoo_audit) && !isset($seoo_audit.error)}

    {* -- Score -- *}
    <div class="seoo-fa-score seoo-fa-score--{$seoo_audit.score.color|escape:'htmlall':'UTF-8'}">
        <img class="seoo-fa-score__panda" src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-notation-{$seoo_audit.score.grade|lower|escape:'htmlall':'UTF-8'}.png" alt="Score {$seoo_audit.score.grade|escape:'htmlall':'UTF-8'}">
        <div class="seoo-fa-score__circle">
            <span class="seoo-fa-score__grade">{$seoo_audit.score.grade|escape:'htmlall':'UTF-8'}</span>
            <span class="seoo-fa-score__value">{$seoo_audit.score.score|intval}/100</span>
        </div>
    </div>

    {* -- Performance -- *}
    {if isset($seoo_audit.performance) && $seoo_audit.performance.checks|count > 0}
        <div class="seoo-fa-section seoo-fa-section--open seoo-fa-section--no-toggle">
            <div class="seoo-fa-section__body">
                <div class="seoo-fa-perf">
                    {if $seoo_audit.performance.load_time_ms !== null}
                        <div class="seoo-fa-perf__item seoo-fa-perf__item--{$seoo_audit.performance.load_time_severity|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/clock.svg"}</span>
                            <span class="seoo-fa-perf__value">
                                {if $seoo_audit.performance.load_time_ms < 1000}
                                    {$seoo_audit.performance.load_time_ms|intval} ms
                                {else}
                                    {($seoo_audit.performance.load_time_ms / 1000)|string_format:"%.2f"} s
                                {/if}
                            </span>
                            <span class="seoo-fa-perf__label">{l s='Load time' mod='seooptimizer'}</span>
                        </div>
                    {/if}
                    {if $seoo_audit.performance.total_kb !== null}
                        <div class="seoo-fa-perf__item seoo-fa-perf__item--{$seoo_audit.performance.weight_severity|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/hard-drives.svg"}</span>
                            <span class="seoo-fa-perf__value">
                                {if $seoo_audit.performance.total_kb < 1024}
                                    {$seoo_audit.performance.total_kb|intval} KB
                                {else}
                                    {($seoo_audit.performance.total_kb / 1024)|string_format:"%.1f"} MB
                                {/if}
                            </span>
                            <span class="seoo-fa-perf__label">{l s='Page weight' mod='seooptimizer'}</span>
                        </div>
                    {/if}
                </div>
                {if $seoo_audit.performance.weight_details}
                    <div class="seoo-fa-perf-breakdown">
                        <span>HTML {$seoo_audit.performance.weight_details.html_kb|intval} KB</span>
                        <span>IMG {$seoo_audit.performance.weight_details.images_kb|intval} KB</span>
                        <span>CSS {$seoo_audit.performance.weight_details.css_kb|intval} KB</span>
                        <span>JS {$seoo_audit.performance.weight_details.js_kb|intval} KB</span>
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    {* -- Google Preview -- *}
    {if isset($seoo_audit.google_preview)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/eye.svg"}</span>
                    APERCU GOOGLE
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                <div class="seoo-fa-serp">
                    <cite class="seoo-fa-serp__url">{$seoo_audit.google_preview.breadcrumb|escape:'htmlall':'UTF-8'}</cite>
                    <h3 class="seoo-fa-serp__title">{$seoo_audit.google_preview.title|escape:'htmlall':'UTF-8'}</h3>
                    <p class="seoo-fa-serp__desc">{$seoo_audit.google_preview.description|escape:'htmlall':'UTF-8'}</p>
                </div>
            </div>
        </div>
    {/if}

    {* -- Meta Tags -- *}
    {if isset($seoo_audit.meta.checks)}
        <div class="seoo-fa-section seoo-fa-section--open seoo-fa-section--no-toggle">
            <div class="seoo-fa-section__body">
                {foreach $seoo_audit.meta.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- Keywords -- *}
    {if isset($seoo_audit.keywords)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/crosshair.svg"}</span>
                    {if $seoo_audit.keywords.available && $seoo_audit.keywords.keywords|count > 0}
                        MOT-CL&Eacute; CIBLE : &laquo; {$seoo_audit.keywords.keywords[0].keyword|upper|escape:'htmlall':'UTF-8'} &raquo;
                    {else}
                        MOT-CL&Eacute; CIBLE
                    {/if}
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {if !$seoo_audit.keywords.available}
                    <p class="seoo-fa-empty">Aucun mot-cl&eacute; d&eacute;fini pour cette page.</p>
                {else}
                    {foreach $seoo_audit.keywords.keywords as $kw}
                        <div class="seoo-fa-kw-grid">
                            {foreach ['title' => 'Title', 'h1' => 'H1', 'url' => 'URL', 'content' => 'Contenu', 'alt_image' => 'Alt image', 'meta_description' => 'Meta desc.'] as $zone_key => $zone_label}
                                <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.$zone_key}found{else}missing{/if}">
                                    <span class="seoo-fa-kw-zone__dot"></span> {$zone_label}
                                </div>
                            {/foreach}
                        </div>
                        {foreach $kw.details as $detail}
                            {call name=seoo_check status=$detail.status title=$detail.title message=$detail.message}
                        {/foreach}
                    {/foreach}
                {/if}
            </div>
        </div>
    {/if}

    {* -- Content -- *}
    {if isset($seoo_audit.content)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/article.svg"}</span>
                    CONTENU &mdash; {$seoo_audit.content.word_count|intval} MOTS
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {assign var='bar_pct' value=min(100, ($seoo_audit.content.word_count / 500) * 100)|intval}
                {assign var='bar_color' value=($seoo_audit.content.status == 'good') ? '#22c55e' : '#f59e0b'}
                <div class="seoo-fa-bar"><div class="seoo-fa-bar__fill" style="width:{$bar_pct}%;background:{$bar_color}"></div></div>
                <div class="seoo-fa-bar__labels"><span>0</span><span>{$seoo_audit.content.threshold_low|intval}</span><span>{$seoo_audit.content.threshold_good|intval}</span><span>500+</span></div>
                {foreach $seoo_audit.content.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- Headings -- *}
    {if isset($seoo_audit.headings)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/list-numbers.svg"}</span>
                    STRUCTURE DES TITRES
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                <div class="seoo-fa-htree">
                    {foreach $seoo_audit.headings.tree as $h}
                        <div class="seoo-fa-htree__item" style="padding-left:{($h.level - 1) * 20}px">
                            <strong>H{$h.level|intval}</strong> {$h.text|escape:'htmlall':'UTF-8'}
                        </div>
                    {/foreach}
                </div>
                {foreach $seoo_audit.headings.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- Structured Data -- *}
    {if isset($seoo_audit.structured_data)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/brackets-curly.svg"}</span>
                    DONN&Eacute;ES STRUCTUR&Eacute;ES (SCHEMA.ORG)
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                <div class="seoo-fa-badges">
                    {foreach $seoo_audit.structured_data.schemas as $schema}
                        <span class="seoo-fa-badge seoo-fa-badge--{if $schema.found}found{else}missing{/if}">
                            {if $schema.found}{include file="module:seooptimizer/views/icons/check-circle.svg"}{else}{include file="module:seooptimizer/views/icons/x-circle.svg"}{/if}
                            {$schema.name|escape:'htmlall':'UTF-8'}
                        </span>
                    {/foreach}
                </div>
                {foreach $seoo_audit.structured_data.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- URL & Indexation -- *}
    {if isset($seoo_audit.indexation)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/link.svg"}</span>
                    URL &amp; INDEXATION
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {foreach $seoo_audit.indexation.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- Images -- *}
    {if isset($seoo_audit.images)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/image.svg"}</span>
                    IMAGES ({$seoo_audit.images.total|intval} D&Eacute;TECT&Eacute;ES)
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {foreach $seoo_audit.images.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

    {* -- Links -- *}
    {if isset($seoo_audit.links)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/globe.svg"}</span>
                    LIENS ({$seoo_audit.links.total|intval} D&Eacute;TECT&Eacute;S)
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {foreach $seoo_audit.links.checks as $check}
                    {call name=seoo_check status=$check.status title=$check.title message=$check.message}
                {/foreach}
            </div>
        </div>
    {/if}

{/if}
