<div class="seoo-psi seoo-screen" id="seoo-psi">
    <div class="seoo-panel-intro">
        <div class="seoo-panel-intro__visual">
            <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-pagespeed.png" alt="{l s='PageSpeed' mod='seooptimizer'}">
        </div>
        <div class="seoo-panel-intro__content">
            <h3 class="seoo-panel-intro__title">
                <span class="seoo-icon">{include file="module:seooptimizer/views/icons/gauge.svg"}</span>
                {l s='PageSpeed (Lighthouse)' mod='seooptimizer'}
            </h3>
            <p class="seoo-panel-intro__desc">{l s='Runs Google Lighthouse on a sample of your pages (the home page and a few pages of each type) with the mobile strategy. You get the performance, accessibility, best practices and SEO scores, the Core Web Vitals and the checks to fix.' mod='seooptimizer'}</p>
        </div>
        {if $seoo_psi_has_key}
            <div class="seoo-panel-intro__actions">
                <button type="button" class="btn btn-default" id="seoo-psi-run" data-first-process="{if $seoo_psi_pending}0{else}1{/if}">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/play.svg"}</span>
                    {if $seoo_psi_pending}{l s='Resume the analysis' mod='seooptimizer'}{else}{l s='Run the analysis' mod='seooptimizer'}{/if}
                </button>
            </div>
        {/if}
    </div>

    <div class="panel-body">
        {if !$seoo_psi_has_key}
            <div class="alert alert-warning">
                {l s='A PageSpeed Insights API key is required.' mod='seooptimizer'}
                <a href="#tab-settings" role="button" data-toggle="collapse">{l s='Enter your key in the Configuration tab.' mod='seooptimizer'}</a>
            </div>
        {else}
            <div class="seoo-psi-progress" id="seoo-psi-progress" hidden>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" id="seoo-psi-bar" role="progressbar" style="width: 0%">0%</div>
                </div>
                <p class="text-muted" id="seoo-psi-status"></p>
            </div>
            <div class="alert alert-danger" id="seoo-psi-error" hidden></div>

            {if $seoo_psi_rows}
                {if $seoo_psi_last_date}
                    <p class="text-muted">{l s='Last analysis:' mod='seooptimizer'} {$seoo_psi_last_date|escape:'htmlall':'UTF-8'} - {l s='Mobile' mod='seooptimizer'}</p>
                {/if}
                <div class="table-responsive">
                    <table class="table seoo-psi-table">
                        <thead>
                            <tr>
                                <th>{l s='Page' mod='seooptimizer'}</th>
                                <th class="seoo-psi-center">{l s='Performance' mod='seooptimizer'}</th>
                                <th class="seoo-psi-center">{l s='Accessibility' mod='seooptimizer'}</th>
                                <th class="seoo-psi-center">{l s='Best practices' mod='seooptimizer'}</th>
                                <th class="seoo-psi-center">{l s='SEO' mod='seooptimizer'}</th>
                                <th title="{l s='Largest Contentful Paint' mod='seooptimizer'}" class="seoo-psi-center">LCP</th>
                                <th title="{l s='Cumulative Layout Shift' mod='seooptimizer'}" class="seoo-psi-center">CLS</th>
                                <th title="{l s='Total Blocking Time' mod='seooptimizer'}" class="seoo-psi-center">TBT</th>
                                <th title="{l s='Server response time' mod='seooptimizer'}" class="seoo-psi-center">TTFB</th>
                                <th class="seoo-psi-center">{l s='Weight' mod='seooptimizer'}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$seoo_psi_rows item=row}
                                <tr class="seoo-psi-row">
                                    <td class="seoo-psi-page">
                                        <a href="{$row.url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" title="{$row.url|escape:'htmlall':'UTF-8'}">{$row.url|escape:'htmlall':'UTF-8'}</a>
                                        <span class="seoo-psi-type">{$row.type|escape:'htmlall':'UTF-8'}</span>
                                    </td>
                                    {if $row.status == 'done'}
                                        {foreach from=$row.scores item=score}
                                            <td class="seoo-psi-center"><span class="seoo-psi-score seoo-psi-score--{$score.class|escape:'htmlall':'UTF-8'}">{if $score.value !== null}{$score.value|intval}{else}-{/if}</span></td>
                                        {/foreach}
                                        {foreach from=['lcp', 'cls', 'tbt', 'ttfb', 'weight'] item=key}
                                            <td class="seoo-psi-center">
                                                {if isset($row.metrics[$key])}
                                                    <span class="seoo-psi-metric seoo-psi-metric--{$row.metrics[$key].class|escape:'htmlall':'UTF-8'}">{$row.metrics[$key].display|escape:'htmlall':'UTF-8'}</span>
                                                {else}-{/if}
                                            </td>
                                        {/foreach}
                                        <td>
                                            <button type="button" class="btn btn-default btn-xs seoo-psi-toggle">{l s='Details' mod='seooptimizer'}</button>
                                        </td>
                                    {elseif $row.status == 'error'}
                                        <td colspan="10" class="seoo-psi-error-cell">
                                            <span class="label label-danger">{l s='Error' mod='seooptimizer'}</span>
                                            {$row.error|escape:'htmlall':'UTF-8'}
                                        </td>
                                    {else}
                                        <td colspan="10" class="text-muted">{l s='Pending' mod='seooptimizer'}</td>
                                    {/if}
                                </tr>
                                {if $row.status == 'done'}
                                    <tr class="seoo-psi-details" hidden>
                                        <td colspan="11">
                                            {if $row.field}
                                                <h5>{l s='Real-user data (Chrome UX Report)' mod='seooptimizer'}</h5>
                                                <p class="seoo-psi-field">
                                                    {foreach from=$row.field key=key item=metric}
                                                        <span class="seoo-psi-metric seoo-psi-metric--{$metric.class|escape:'htmlall':'UTF-8'}">{$key|upper|escape:'htmlall':'UTF-8'} {$metric.display|escape:'htmlall':'UTF-8'}</span>
                                                    {/foreach}
                                                </p>
                                            {/if}
                                            {if $row.audits}
                                                {foreach from=$row.audits key=category item=audits}
                                                    <h5>{if $category == 'performance'}{l s='Performance' mod='seooptimizer'}{elseif $category == 'accessibility'}{l s='Accessibility' mod='seooptimizer'}{elseif $category == 'best-practices'}{l s='Best practices' mod='seooptimizer'}{else}{l s='SEO' mod='seooptimizer'}{/if}</h5>
                                                    <ul class="seoo-psi-audits">
                                                        {foreach from=$audits item=audit}
                                                            <li>
                                                                {$audit.title|escape:'htmlall':'UTF-8'}
                                                                {if $audit.display}<span class="text-muted"> - {$audit.display|escape:'htmlall':'UTF-8'}</span>{/if}
                                                            </li>
                                                        {/foreach}
                                                    </ul>
                                                {/foreach}
                                            {else}
                                                <p class="text-success">{l s='No check to fix on this page.' mod='seooptimizer'}</p>
                                            {/if}
                                        </td>
                                    </tr>
                                {/if}
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {else}
                <p class="text-muted">{l s='No analysis yet. Click "Run the analysis" to start.' mod='seooptimizer'}</p>
            {/if}
        {/if}
    </div>
</div>
