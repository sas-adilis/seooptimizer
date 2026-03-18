<div class="seoo-pages" id="seoo-pages">
    <div class="panel">
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-pages.png" alt="{l s='Pages' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <i class="icon-file-text"></i>
                    {l s='Pages overview' mod='seooptimizer'}
                </h3>
                <p class="seoo-panel-intro__desc">{l s='All crawled pages with a summary of detected issues. Click on a page to see its problems, or re-audit it individually.' mod='seooptimizer'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                <button type="button" class="btn btn-default" id="seoo-full-audit-btn">
                    <i class="process-icon-search"></i> {l s='Start full audit' mod='seooptimizer'}
                </button>
            </div>
        </div>

        <div class="panel-body">
            <div class="seoo-full-audit" id="seoo-full-audit" style="display:none;">
                <div class="seoo-report__table">
                    <div class="seoo-report__thead">
                        <div class="seoo-report__th seoo-report__th--entity">{l s='Entity' mod='seooptimizer'}</div>
                        <div class="seoo-report__th seoo-report__th--progress">{l s='Progression' mod='seooptimizer'}</div>
                        <div class="seoo-report__th seoo-report__th--result">{l s='Result' mod='seooptimizer'}</div>
                    </div>
                    <div id="seoo-full-audit-items">
                        {if $seoo_full_audit_items|count > 0}
                            {foreach $seoo_full_audit_items as $type_key => $item}
                                <div class="seoo-report__row" data-full-audit-item="{$type_key|escape:'htmlall':'UTF-8'}">
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
                        {/if}
                    </div>
                </div>
            </div>

            {if !$seoo_pages_has_data}
                <div class="text-center seoo-pages__empty" style="padding:40px">
                    <p>{l s='Run the full audit to see the pages overview.' mod='seooptimizer'}</p>
                </div>
            {else}
                <div class="seoo-pages__kpis">
                    <div class="seoo-report__kpi">
                        <span class="seoo-report__kpi-label">{l s='Total pages' mod='seooptimizer'}</span>
                        <span class="seoo-report__kpi-value">{$seoo_pages_total|escape:'htmlall':'UTF-8'}</span>
                    </div>
                    <div class="seoo-report__kpi {if $seoo_pages_with_issues > 0}seoo-report__kpi--danger{/if}">
                        <span class="seoo-report__kpi-label">{l s='Pages with issues' mod='seooptimizer'}</span>
                        <span class="seoo-report__kpi-value">{$seoo_pages_with_issues|escape:'htmlall':'UTF-8'}</span>
                    </div>
                    <div class="seoo-report__kpi {if $seoo_pages_critical > 0}seoo-report__kpi--danger{/if}">
                        <span class="seoo-report__kpi-label">{l s='Critical issues' mod='seooptimizer'}</span>
                        <span class="seoo-report__kpi-value">{$seoo_pages_critical|escape:'htmlall':'UTF-8'}</span>
                    </div>
                    <div class="seoo-report__kpi {if $seoo_pages_warnings > 0}seoo-report__kpi--warning{/if}">
                        <span class="seoo-report__kpi-label">{l s='Warnings' mod='seooptimizer'}</span>
                        <span class="seoo-report__kpi-value">{$seoo_pages_warnings|escape:'htmlall':'UTF-8'}</span>
                    </div>
                    <div class="seoo-report__kpi">
                        <span class="seoo-report__kpi-label">{l s='Clean pages' mod='seooptimizer'}</span>
                        <span class="seoo-report__kpi-value">{$seoo_pages_clean|escape:'htmlall':'UTF-8'}</span>
                    </div>
                </div>

                <div class="seoo-pages__filter">
                    <input type="text" id="seoo-pages-search" class="form-control" placeholder="{l s='Filter by URL...' mod='seooptimizer'}" style="max-width:400px;">
                    <select id="seoo-pages-severity-filter" class="form-control" style="max-width:200px;">
                        <option value="">{l s='All pages' mod='seooptimizer'}</option>
                        <option value="critical">{l s='Critical only' mod='seooptimizer'}</option>
                        <option value="warning">{l s='Warnings only' mod='seooptimizer'}</option>
                        <option value="clean">{l s='Clean only' mod='seooptimizer'}</option>
                    </select>
                </div>

                <table class="table seoo-pages__table" id="seoo-pages-table">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th>{l s='Page' mod='seooptimizer'}</th>
                            <th class="text-center" style="width:80px;">{l s='Critical' mod='seooptimizer'}</th>
                            <th class="text-center" style="width:80px;">{l s='Warning' mod='seooptimizer'}</th>
                            <th class="text-center" style="width:80px;">{l s='Total' mod='seooptimizer'}</th>
                            <th class="text-right" style="width:120px;">{l s='Actions' mod='seooptimizer'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $seoo_pages_data as $page_url => $page}
                            <tr class="seoo-pages__row" data-url="{$page.url|escape:'htmlall':'UTF-8'}" data-critical="{$page.critical|escape:'htmlall':'UTF-8'}" data-warning="{$page.warning|escape:'htmlall':'UTF-8'}" data-total="{$page.total|escape:'htmlall':'UTF-8'}">
                                <td class="seoo-pages__expand-cell">
                                    {if $page.total > 0}
                                        <i class="icon-chevron-right seoo-pages__chevron"></i>
                                    {/if}
                                </td>
                                <td class="seoo-pages__url-cell">
                                    <a href="{$page.url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" title="{$page.url|escape:'htmlall':'UTF-8'}">{$page.url|escape:'htmlall':'UTF-8'}</a>
                                </td>
                                <td class="text-center">
                                    {if $page.critical > 0}
                                        <span class="seoo-pages__badge seoo-pages__badge--critical">{$page.critical|escape:'htmlall':'UTF-8'}</span>
                                    {else}
                                        <span class="seoo-pages__badge seoo-pages__badge--none">0</span>
                                    {/if}
                                </td>
                                <td class="text-center">
                                    {if $page.warning > 0}
                                        <span class="seoo-pages__badge seoo-pages__badge--warning">{$page.warning|escape:'htmlall':'UTF-8'}</span>
                                    {else}
                                        <span class="seoo-pages__badge seoo-pages__badge--none">0</span>
                                    {/if}
                                </td>
                                <td class="text-center">
                                    {if $page.total > 0}
                                        <strong>{$page.total|escape:'htmlall':'UTF-8'}</strong>
                                    {else}
                                        <span style="color:#16a34a;">0</span>
                                    {/if}
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-default btn-xs seoo-pages__reaudit-btn" data-url="{$page.url|escape:'htmlall':'UTF-8'}" title="{l s='Re-audit this page' mod='seooptimizer'}">
                                        <i class="icon-refresh"></i>
                                    </button>
                                </td>
                            </tr>
                            {if $page.total > 0}
                                <tr class="seoo-pages__detail-row" data-detail-for="{$page.url|escape:'htmlall':'UTF-8'}" style="display:none;">
                                    <td colspan="6">
                                        <div class="seoo-pages__issues">
                                            {foreach $page.issues as $issue}
                                                <div class="seoo-pages__issue seoo-pages__issue--{$issue.severity|escape:'htmlall':'UTF-8'}">
                                                    <span class="seoo-pages__issue-severity">
                                                        <span class="seoo-audit__severity-dot seoo-audit__severity-dot--{$issue.severity|escape:'htmlall':'UTF-8'}"></span>
                                                    </span>
                                                    <span class="seoo-pages__issue-audit">
                                                        <i class="{$issue.audit_icon|escape:'htmlall':'UTF-8'}"></i>
                                                        {$issue.audit|escape:'htmlall':'UTF-8'}
                                                    </span>
                                                    <span class="seoo-pages__issue-message">{$issue.message|escape:'htmlall':'UTF-8'}</span>
                                                </div>
                                            {/foreach}
                                        </div>
                                    </td>
                                </tr>
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
            {/if}
        </div>
    </div>
</div>
