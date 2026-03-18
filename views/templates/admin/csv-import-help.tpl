<div class="seoo-csv-help">
    <strong>{l s='Expected CSV format' mod='seooptimizer'}</strong>
    <p>{l s='The CSV file must contain 3 columns, without a header row:' mod='seooptimizer'}</p>
    <table class="table table-bordered seoo-csv-help__table">
        <thead>
            <tr>
                <th>{l s='Column' mod='seooptimizer'}</th>
                <th>{l s='Description' mod='seooptimizer'}</th>
                <th>{l s='Example' mod='seooptimizer'}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1 — {l s='Source URL' mod='seooptimizer'}</td>
                <td>{l s='Relative URL (without domain)' mod='seooptimizer'}</td>
                <td><code>/old-page.html</code></td>
            </tr>
            <tr>
                <td>2 — {l s='Destination URL' mod='seooptimizer'}</td>
                <td>{l s='Full URL or relative URL' mod='seooptimizer'}</td>
                <td><code>https://example.com/new-page</code></td>
            </tr>
            <tr>
                <td>3 — {l s='Redirect type' mod='seooptimizer'}</td>
                <td><code>301</code> {l s='or' mod='seooptimizer'} <code>302</code></td>
                <td><code>301</code></td>
            </tr>
        </tbody>
    </table>
    <a href="{$csv_example_url|escape:'htmlall':'UTF-8'}" class="btn btn-default btn-sm" download>
        <i class="icon-download"></i> {l s='Download example CSV' mod='seooptimizer'}
    </a>
</div>
