<div class="seoo-audit" id="audit_{$audit_key|escape:'htmlall':'UTF-8'}">
    <div class="panel">
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
                {if $audit_is_complete && $audit_results|count > 0}
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
                    {foreach $audit_kpis as $kpi}
                        <div class="seoo-report__kpi {if $kpi.danger}seoo-report__kpi--danger{elseif $kpi.warning}seoo-report__kpi--warning{/if}">
                            <span class="seoo-report__kpi-label">{$kpi.label|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-report__kpi-value" data-audit-kpi="{$kpi.key|escape:'htmlall':'UTF-8'}">{$kpi.value|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    {/foreach}
                </div>

                {if $audit_items|count > 0}
                    <div class="seoo-report__table">
                        <div class="seoo-report__thead">
                            <div class="seoo-report__th seoo-report__th--entity">{l s='Entity' mod='seooptimizer'}</div>
                            <div class="seoo-report__th seoo-report__th--progress">{l s='Progression' mod='seooptimizer'}</div>
                            <div class="seoo-report__th seoo-report__th--result">{l s='Result' mod='seooptimizer'}</div>
                        </div>
                        {foreach $audit_items as $type_key => $item}
                            <div class="seoo-report__row" data-audit-item="{$type_key|escape:'htmlall':'UTF-8'}">
                                <div class="seoo-report__cell seoo-report__cell--entity">
                                    <span class="seoo-report__icon"><i class="{$item.icon|escape:'htmlall':'UTF-8'}"></i></span>
                                    <span class="seoo-report__entity-info">
                                        <strong class="seoo-report__entity-name">{$item.label|escape:'htmlall':'UTF-8'}</strong>
                                        <span class="seoo-report__entity-count">{$item.total|escape:'htmlall':'UTF-8'} {l s='pages' mod='seooptimizer'}</span>
                                    </span>
                                </div>
                                <div class="seoo-report__cell seoo-report__cell--progress">
                                    <div class="seoo-report__bar-wrap">
                                        <div class="progress report__progress-percentage">
                                            <div class="progress-bar {if $item.percentage == 100}bg-success{elseif $item.percentage > 0}bg-processing{/if}"
                                                 role="progressbar"
                                                 aria-valuenow="{$item.percentage|escape:'htmlall':'UTF-8'}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 style="width: {$item.percentage|escape:'htmlall':'UTF-8'}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="seoo-report__status-line">
                                        <span class="seoo-report__status-label">
                                            {if $item.status == 'done'}
                                                {l s='Done' mod='seooptimizer'}
                                            {elseif $item.status == 'processing'}
                                                {l s='In progress' mod='seooptimizer'}
                                            {else}
                                                {l s='Waiting' mod='seooptimizer'}
                                            {/if}
                                        </span>
                                        <span class="seoo-report__progress-value">{$item.crawled|escape:'htmlall':'UTF-8'} / {$item.total|escape:'htmlall':'UTF-8'}</span>
                                    </div>
                                </div>
                                <div class="seoo-report__cell seoo-report__cell--result">
                                    <span class="seoo-report__badge {if $item.issues_count > 0}seoo-report__badge--danger{else}seoo-report__badge--success{/if}">
                                        {$item.issues_count|escape:'htmlall':'UTF-8'}
                                    </span>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                {/if}
            </div>

            {if $audit_results|count > 0}
                <div class="seoo-audit__results">
                    <div class="seoo-audit__results-header">
                        <img src="{$audit_module_path|escape:'htmlall':'UTF-8'}views/img/panda-details.png" alt="" class="seoo-audit__results-img">
                        <h4 class="seoo-audit__results-title">{l s='Audit details' mod='seooptimizer'}</h4>
                    </div>
                    <table class="table seoo-audit__table">
                        <thead>
                        <tr>
                            <th class="seoo-audit__th-severity"></th>
                            <th>{l s='Page' mod='seooptimizer'}</th>
                            {foreach $audit_columns as $col_key => $col_label}
                                <th>{$col_label|escape:'htmlall':'UTF-8'}</th>
                            {/foreach}
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $audit_results as $row}
                            <tr class="seoo-audit__result-row seoo-audit__result-row--{$row.severity|escape:'htmlall':'UTF-8'}">
                                <td class="seoo-audit__severity-cell">
                                    <span class="seoo-audit__severity-dot seoo-audit__severity-dot--{$row.severity|escape:'htmlall':'UTF-8'}"></span>
                                </td>
                                <td class="seoo-audit__url-cell">
                                    <a href="{$row.url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">{$row.url|escape:'htmlall':'UTF-8'}</a>
                                </td>
                                {foreach $audit_columns as $col_key => $col_label}
                                    <td>{if isset($row[$col_key])}{$row[$col_key]|escape:'htmlall':'UTF-8'}{/if}</td>
                                {/foreach}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
</div>
