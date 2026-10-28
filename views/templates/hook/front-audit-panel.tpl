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

    {* -- Evolution -- *}
    {if isset($seoo_audit.history) && $seoo_audit.history.chart}
        <div class="seoo-fa-section seoo-fa-section--open seoo-fa-section--no-toggle">
            <div class="seoo-fa-section__body">
                <div class="seoo-fa-check__title">
                    {l s='Score evolution' mod='seooptimizer'}{if $seoo_audit.history.delta} — {$seoo_audit.history.delta|escape:'htmlall':'UTF-8'}{/if}
                </div>
                {$seoo_audit.history.chart nofilter}
            </div>
        </div>
    {/if}

    {* -- Evolution by audit -- *}
    {if isset($seoo_audit.history) && $seoo_audit.history.audits|count > 0}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/chart-bar.svg"}</span>
                    {l s='EVOLUTION BY AUDIT' mod='seooptimizer'}
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {foreach $seoo_audit.history.audits as $history_audit}
                    <div class="seoo-fa-check__title">
                        {$history_audit.title|escape:'htmlall':'UTF-8'}{if $history_audit.delta} — {$history_audit.delta|escape:'htmlall':'UTF-8'}{/if}
                    </div>
                    {$history_audit.chart nofilter}
                {/foreach}
            </div>
        </div>
    {/if}

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
                            <span class="seoo-fa-perf__label">{l s='Server response' mod='seooptimizer'}</span>
                        </div>
                    {/if}
                    {if $seoo_audit.performance.lighthouse !== null}
                        <div class="seoo-fa-perf__item seoo-fa-perf__item--{$seoo_audit.performance.lighthouse_severity|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/gauge.svg"}</span>
                            <span class="seoo-fa-perf__value">{$seoo_audit.performance.lighthouse|intval}/100</span>
                            <span class="seoo-fa-perf__label">{l s='Lighthouse' mod='seooptimizer'}</span>
                        </div>
                    {/if}
                    {foreach $seoo_audit.performance.lighthouse_metrics as $metric}
                        <div class="seoo-fa-perf__item seoo-fa-perf__item--{$metric.severity|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/gauge.svg"}</span>
                            <span class="seoo-fa-perf__value">{$metric.display|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-fa-perf__label">{$metric.label|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    {/foreach}
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
                    {l s='GOOGLE PREVIEW' mod='seooptimizer'}
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
                        {assign var='seoo_kw_upper' value=$seoo_audit.keywords.keywords[0].keyword|upper|escape:'htmlall':'UTF-8'}{l s='TARGET KEYWORD: "%s"' sprintf=[$seoo_kw_upper] mod='seooptimizer'}
                    {else}
                        {l s='TARGET KEYWORD' mod='seooptimizer'}
                    {/if}
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {if !$seoo_audit.keywords.available}
                    <p class="seoo-fa-empty">{l s='No target keyword defined for this page.' mod='seooptimizer'}</p>
                {else}
                    {foreach $seoo_audit.keywords.keywords as $kw}
                        <div class="seoo-fa-kw-grid">
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.title}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='Title' mod='seooptimizer'}
                            </div>
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.h1}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='H1' mod='seooptimizer'}
                            </div>
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.url}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='URL' mod='seooptimizer'}
                            </div>
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.content}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='Content' mod='seooptimizer'}
                            </div>
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.alt_image}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='Image alt' mod='seooptimizer'}
                            </div>
                            <div class="seoo-fa-kw-zone seoo-fa-kw-zone--{if $kw.zones.meta_description}found{else}missing{/if}">
                                <span class="seoo-fa-kw-zone__dot"></span> {l s='Meta description' mod='seooptimizer'}
                            </div>
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
                    {l s='CONTENT — %d WORDS' sprintf=[$seoo_audit.content.word_count|intval] mod='seooptimizer'}
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {assign var='bar_pct' value=min(100, ($seoo_audit.content.word_count / $seoo_audit.content.bar_max) * 100)|intval}
                {assign var='bar_color' value=($seoo_audit.content.status == 'good') ? '#22c55e' : '#f59e0b'}
                <div class="seoo-fa-bar"><div class="seoo-fa-bar__fill" style="width:{$bar_pct}%;background:{$bar_color}"></div></div>
                <div class="seoo-fa-bar__labels"><span>0</span><span>{$seoo_audit.content.threshold_low|intval}</span><span>{$seoo_audit.content.threshold_good|intval}</span><span>{$seoo_audit.content.bar_max|intval}+</span></div>
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
                    {l s='HEADING STRUCTURE' mod='seooptimizer'}
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
                    {l s='STRUCTURED DATA (SCHEMA.ORG)' mod='seooptimizer'}
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
                    {l s='URL & INDEXATION' mod='seooptimizer'}
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

    {* -- Social & technical tags -- *}
    {if isset($seoo_audit.social)}
        <div class="seoo-fa-section" onclick="this.classList.toggle('seoo-fa-section--open')">
            <div class="seoo-fa-section__header">
                <span class="seoo-fa-section__title">
                    <span class="seoo-fa-icon-svg">{include file="module:seooptimizer/views/icons/link.svg"}</span>
                    {l s='SOCIAL & TECHNICAL TAGS' mod='seooptimizer'}
                </span>
                <span class="seoo-fa-section__toggle">{include file="module:seooptimizer/views/icons/caret-down.svg"}</span>
            </div>
            <div class="seoo-fa-section__body" onclick="event.stopPropagation()">
                {foreach $seoo_audit.social.checks as $check}
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
                    {l s='IMAGES (%d DETECTED)' sprintf=[$seoo_audit.images.total|intval] mod='seooptimizer'}
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
                    {l s='LINKS (%d DETECTED)' sprintf=[$seoo_audit.links.total|intval] mod='seooptimizer'}
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
