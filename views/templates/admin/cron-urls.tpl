<div class="panel">
    <div class="panel-heading">
        <span class="seoo-icon">{include file="module:seooptimizer/views/icons/clock.svg"}</span> {l s='Cron URLs' mod='seooptimizer'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            {l s='Use the URLs below to schedule automated audits via cron. Each call processes URLs within a 2-minute window. For large catalogs, schedule the cron to run repeatedly (e.g. every 5 minutes) until the audit completes.' mod='seooptimizer'}
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Audit' mod='seooptimizer'}</th>
                    <th>{l s='Cron URL' mod='seooptimizer'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach $seoo_cron_urls as $cron}
                    <tr>
                        <td><strong>{$cron.label|escape:'htmlall':'UTF-8'}</strong></td>
                        <td>
                            <code class="seoo-cron-url" style="word-break:break-all;font-size:11px;">{$cron.url|escape:'htmlall':'UTF-8'}</code>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-default btn-xs seoo-copy-btn" data-url="{$cron.url|escape:'htmlall':'UTF-8'}">
                                <span class="seoo-icon">{include file="module:seooptimizer/views/icons/copy.svg"}</span>
                            </button>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <div class="well" style="margin-top:15px;">
            <strong>{l s='Example crontab (full audit every day at 3:00 AM):' mod='seooptimizer'}</strong>
            <pre style="margin-top:8px;font-size:12px;">0 3 * * * curl -s "{$seoo_cron_urls[0].url|escape:'htmlall':'UTF-8'}" > /dev/null 2>&1</pre>
            <p style="margin-top:8px;color:#6b7280;font-size:12px;">
                {l s='For large catalogs, run every 5 minutes until complete:' mod='seooptimizer'}
            </p>
            <pre style="font-size:12px;">*/5 * * * * curl -s "{$seoo_cron_urls[0].url|escape:'htmlall':'UTF-8'}" > /dev/null 2>&1</pre>
        </div>
    </div>
</div>
