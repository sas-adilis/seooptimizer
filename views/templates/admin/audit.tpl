<div class="seoo-audit seoo-screen" id="audit_{$audit_key|escape:'htmlall':'UTF-8'}">
        <div class="seoo-panel-intro">
            {if $audit_visual}
                <div class="seoo-panel-intro__visual">
                    <img src="{$audit_module_path|escape:'htmlall':'UTF-8'}views/img/{$audit_visual|escape:'htmlall':'UTF-8'}" alt="{$audit_title|escape:'htmlall':'UTF-8'}">
                </div>
            {/if}
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <i class="{$audit_icon|escape:'htmlall':'UTF-8'}"></i>
                    {$audit_title|escape:'htmlall':'UTF-8'}
                </h3>
                <p class="seoo-panel-intro__desc">{$audit_description|escape:'htmlall':'UTF-8'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                <button type="button"
                        class="btn btn-default seoo-audit__start-btn"
                        data-audit-key="{$audit_key|escape:'htmlall':'UTF-8'}"
                        data-audit-action="runAudit{$audit_key|ucfirst|escape:'htmlall':'UTF-8'}">
                    <i class="process-icon-search"></i> {l s='Start audit' mod='seooptimizer'}
                </button>
                {if $audit_is_complete && $audit_results_count > 0}
                    <button type="button"
                            class="btn btn-default seoo-audit__csv-btn"
                            data-audit-action="exportCsvAudit{$audit_key|ucfirst|escape:'htmlall':'UTF-8'}">
                        <i class="icon-download"></i> {l s='Export CSV' mod='seooptimizer'}
                    </button>
                {/if}
            </div>
        </div>

        <div class="panel-body">
            <div class="seoo-report" id="{$audit_key|escape:'htmlall':'UTF-8'}_report">
                <div class="seoo-report__kpis">
                    {if isset($audit_score) && $audit_score.grade != '-'}
                        <div class="seoo-report__kpi seoo-report__kpi--score seoo-report__kpi--score-{$audit_score.grade_color|escape:'htmlall':'UTF-8'}"
                             data-audit-score="{$audit_key|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-report__kpi-label">{l s='Score' mod='seooptimizer'}</span>
                            <span class="seoo-report__kpi-value seoo-score__grade">{$audit_score.grade|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-report__kpi-sub">{$audit_score.score|escape:'htmlall':'UTF-8'}/100</span>
                        </div>
                    {/if}
                    {foreach $audit_kpis as $kpi}
                        <div class="seoo-report__kpi {if $kpi.danger}seoo-report__kpi--danger{elseif $kpi.warning}seoo-report__kpi--warning{/if}">
                            <span class="seoo-report__kpi-label">{$kpi.label|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-report__kpi-value" data-audit-kpi="{$kpi.key|escape:'htmlall':'UTF-8'}">{$kpi.value|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    {/foreach}
                </div>

                <div class="seoo-report__table seoo-audit__progress-table" style="display:none;">
                    <div class="seoo-report__thead">
                        <div class="seoo-report__th seoo-report__th--entity">{l s='Entity' mod='seooptimizer'}</div>
                        <div class="seoo-report__th seoo-report__th--progress">{l s='Progression' mod='seooptimizer'}</div>
                        <div class="seoo-report__th seoo-report__th--result">{l s='Result' mod='seooptimizer'}</div>
                    </div>
                </div>
            </div>

            {if $audit_results_count > 0 && $audit_result_list_html}
                <div class="seoo-audit__results">
                    {$audit_result_list_html nofilter}
                </div>
            {/if}
        </div>
</div>
