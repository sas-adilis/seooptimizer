<div id="seooptimizer">
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li class="active">
                        <a href="#tab-dashboard" role="button" data-toggle="collapse">
                            <i class="icon-dashboard"></i>
                            {l s='Dashboard' mod='seooptimizer'}
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-search"></i>
                            {l s='Audits' mod='seooptimizer'} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="#tab-audit-heading-hierarchy" role="button" data-toggle="collapse"><i class="icon-header"></i> {l s='Heading hierarchy (H1-H6)' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-missing-alt" role="button" data-toggle="collapse"><i class="icon-picture"></i> {l s='Missing image alt' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-broken-links" role="button" data-toggle="collapse"><i class="icon-unlink"></i> {l s='Broken links (404)' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-page-load-time" role="button" data-toggle="collapse"><i class="icon-dashboard"></i> {l s='Page load time' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-page-weight" role="button" data-toggle="collapse"><i class="icon-hdd-o"></i> {l s='Page weight' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-unsecured-links" role="button" data-toggle="collapse"><i class="icon-unlock"></i> {l s='Unsecured links (HTTP)' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-meta-tags" role="button" data-toggle="collapse"><i class="icon-align-left"></i> {l s='Meta tags (title & description)' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-internal-links" role="button" data-toggle="collapse"><i class="icon-link"></i> {l s='Internal links' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-text-ratio" role="button" data-toggle="collapse"><i class="icon-font"></i> {l s='Text content ratio' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-audit-keyword-check" role="button" data-toggle="collapse"><i class="icon-bullseye"></i> {l s='Keyword check' mod='seooptimizer'}</a></li>
                        </ul>
                    </li>
                    <li><a href="#tab-pages" role="button" data-toggle="collapse"><i class="icon-file-text"></i> {l s='Pages' mod='seooptimizer'}</a></li>
                    <li class="seoo-navbar-separator"></li>
                    <li><a href="#tab-redirects" role="button" data-toggle="collapse"><i class="icon-share"></i> {l s='Redirections' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-indexations" role="button" data-toggle="collapse"><i class="icon-database"></i> {l s='Indexations' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-not-found" role="button" data-toggle="collapse"><i class="icon-unlink"></i> {l s='Pages not found' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-robots-txt" role="button" data-toggle="collapse"><i class="icon-file-text"></i> {l s='Robots.txt' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-llms-txt" role="button" data-toggle="collapse"><i class="icon-file-text"></i> {l s='AI Search' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-canonical-urls" role="button" data-toggle="collapse"><i class="icon-code"></i> {l s='Canonical Urls' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-sitemap" role="button" data-toggle="collapse"><i class="icon-sitemap"></i> {l s='Sitemap' mod='seooptimizer'}</a></li>
                    <li><a href="#tab-rich-snippets" role="button" data-toggle="collapse"><i class="icon-star"></i> {l s='Rich snippets' mod='seooptimizer'}</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-wrench"></i>
                            {l s='Tools' mod='seooptimizer'} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="#tab-verification-code" role="button" data-toggle="collapse"><i class="icon-code"></i> {l s='Verification code' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-social-configuration" role="button" data-toggle="collapse"><i class="icon-cloud"></i> {l s='Social addresses' mod='seooptimizer'}</a></li>
                            <li><a href="#tab-link-obfuscator" role="button" data-toggle="collapse"><i class="icon-link"></i> {l s='Links obfuscation' mod='seooptimizer'}</a></li>
                        </ul>
                    </li>
                    <li class="seoo-navbar-separator"></li>
                    <li><a href="#tab-settings" role="button" data-toggle="collapse"><i class="icon-cogs"></i> {l s='Configuration' mod='seooptimizer'}</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="tabs">
        {* ── Dashboard ── *}
        <div id="tab-dashboard" class="tab collapse">
            {if isset($seoo_scores) && $seoo_scores.has_data}
                <div class="seoo-dashboard-score">
                    <div class="seoo-dashboard-score__global">
                        <div class="seoo-score-ring seoo-score-ring--{$seoo_scores.global.grade_color|escape:'htmlall':'UTF-8'}">
                            <span class="seoo-score-ring__grade">{$seoo_scores.global.grade|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-score-ring__value">{$seoo_scores.global.score|escape:'htmlall':'UTF-8'}</span>
                            <span class="seoo-score-ring__label">{l s='SEO Score' mod='seooptimizer'}</span>
                        </div>
                    </div>
                    <div class="seoo-dashboard-score__audits">
                        {foreach $seoo_scores.audits as $audit_key => $audit_data}
                            <div class="seoo-dashboard-score__audit seoo-dashboard-score__audit--{$audit_data.grade_color|escape:'htmlall':'UTF-8'}">
                                <div class="seoo-dashboard-score__audit-grade">{$audit_data.grade|escape:'htmlall':'UTF-8'}</div>
                                <div class="seoo-dashboard-score__audit-info">
                                    <span class="seoo-dashboard-score__audit-title"><i class="{$audit_data.icon|escape:'htmlall':'UTF-8'}"></i> {$audit_data.title|escape:'htmlall':'UTF-8'}</span>
                                    <span class="seoo-dashboard-score__audit-value">{$audit_data.score|escape:'htmlall':'UTF-8'}/100</span>
                                </div>
                                <div class="seoo-dashboard-score__audit-bar">
                                    <div class="seoo-dashboard-score__audit-bar-fill" style="width: {$audit_data.score|escape:'htmlall':'UTF-8'}%"></div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>

                {if $seoo_scores.pages|count > 0}
                    <div class="seoo-screen">
                        <div class="seoo-panel-intro">
                            <div class="seoo-panel-intro__content">
                                <h3 class="seoo-panel-intro__title"><i class="icon-list"></i> {l s='Score by page' mod='seooptimizer'}</h3>
                                <p class="seoo-panel-intro__desc">{l s='Detailed SEO score for each page of your website, sorted by score ascending.' mod='seooptimizer'}</p>
                            </div>
                        </div>
                        <div class="panel-body">
                            <table class="table seoo-pages-score-table">
                                <thead>
                                    <tr>
                                        <th class="seoo-pages-score-table__th-grade">{l s='Grade' mod='seooptimizer'}</th>
                                        <th>{l s='Page' mod='seooptimizer'}</th>
                                        <th class="seoo-pages-score-table__th-score">{l s='Score' mod='seooptimizer'}</th>
                                        {foreach $seoo_scores.audits as $audit_key => $audit_data}
                                            <th class="seoo-pages-score-table__th-audit text-center" title="{$audit_data.title|escape:'htmlall':'UTF-8'}"><i class="{$audit_data.icon|escape:'htmlall':'UTF-8'}"></i></th>
                                        {/foreach}
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $seoo_scores.pages as $page_url => $page_data}
                                        <tr>
                                            <td class="seoo-pages-score-table__grade"><span class="seoo-grade-badge seoo-grade-badge--{$page_data.grade_color|escape:'htmlall':'UTF-8'}">{$page_data.grade|escape:'htmlall':'UTF-8'}</span></td>
                                            <td class="seoo-pages-score-table__url"><a href="{$page_data.url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">{$page_data.url|escape:'htmlall':'UTF-8'}</a></td>
                                            <td class="seoo-pages-score-table__score">{$page_data.score|escape:'htmlall':'UTF-8'}</td>
                                            {foreach $seoo_scores.audits as $audit_key => $audit_data_col}
                                                <td class="seoo-pages-score-table__cell-audit text-center">
                                                    {if isset($page_data.audits.$audit_key)}
                                                        {assign var="cell_score" value=$page_data.audits.$audit_key}
                                                        <span class="seoo-mini-score {if $cell_score >= 85}seoo-mini-score--good{elseif $cell_score >= 50}seoo-mini-score--warning{else}seoo-mini-score--critical{/if}">{$cell_score|escape:'htmlall':'UTF-8'}</span>
                                                    {else}
                                                        <span class="seoo-mini-score seoo-mini-score--na">-</span>
                                                    {/if}
                                                </td>
                                            {/foreach}
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                {/if}
            {else}
                <div class="seoo-screen">
                    <div class="panel-body text-center" style="padding: 40px">
                        <p>{l s='Run at least one audit to see your SEO score.' mod='seooptimizer'}</p>
                    </div>
                </div>
            {/if}
        </div>

        {* ── Pages ── *}
        <div id="tab-pages" class="tab collapse">{$seoo_pages_html nofilter}</div>

        {* ── Redirections ── *}
        <div id="tab-redirects" class="tab collapse">
            <div class="seoo-screen">
                <div class="seoo-panel-intro">
                    <div class="seoo-panel-intro__visual">
                        <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-redirections.png" alt="{l s='Redirections' mod='seooptimizer'}">
                    </div>
                    <div class="seoo-panel-intro__content">
                        <h3 class="seoo-panel-intro__title"><i class="icon-share"></i> {l s='Redirections' mod='seooptimizer'}</h3>
                        <p class="seoo-panel-intro__desc">{l s='Manage 301 and 302 redirections. Redirect old URLs to new ones to preserve your SEO ranking and avoid 404 errors.' mod='seooptimizer'}</p>
                    </div>
                    <div class="seoo-panel-intro__actions">
                        <button class="btn btn-default" data-toggle="collapse" data-target="#form-redirection-edit"><i class="icon-plus"></i> {l s='Add new redirection' mod='seooptimizer'}</button>
                        <button class="btn btn-default" data-toggle="collapse" data-target="#form-redirection-import"><i class="icon-upload"></i> {l s='Import' mod='seooptimizer'}</button>
                        <a href="{$seoo_export_redirections_url|escape:'htmlall':'UTF-8'}" class="btn btn-default"><i class="icon-download"></i> {l s='Export' mod='seooptimizer'}</a>
                    </div>
                </div>
                <div id="form-redirection-edit" class="collapse {if isset($show_form_redirection_edit)}in{/if}">{$form_redirection_edit}</div>
                <div id="form-redirection-import" class="collapse {if isset($show_form_redirection_import)}in{/if}">{$form_redirection_import}</div>
                {$data_list_redirections}
                {$form_redirection}
            </div>
        </div>

        {* ── Pages not found ── *}
        <div id="tab-not-found" class="tab collapse">
            <div class="seoo-screen">
                <div class="seoo-panel-intro">
                    <div class="seoo-panel-intro__visual">
                        <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-broken-links.png" alt="{l s='Pages not found' mod='seooptimizer'}">
                    </div>
                    <div class="seoo-panel-intro__content">
                        <h3 class="seoo-panel-intro__title"><i class="icon-unlink"></i> {l s='Pages not found' mod='seooptimizer'}</h3>
                        <p class="seoo-panel-intro__desc">{l s='Monitor 404 errors on your website. Identify broken URLs and create redirections to preserve your SEO ranking.' mod='seooptimizer'}</p>
                    </div>
                </div>
                {$data_list_pages_not_found}
            </div>
        </div>

        {* ── Robots.txt ── *}
        <div id="tab-robots-txt" class="tab collapse">{$form_robots_txt}</div>

        {* ── llms.txt ── *}
        <div id="tab-llms-txt" class="tab collapse">{$form_llms_txt}</div>

        {* ── Canonical URLs ── *}
        <div id="tab-canonical-urls" class="tab collapse"><div class="seoo-screen">{$form_canonical_urls}</div></div>

        {* ── Indexations ── *}
        <div id="tab-indexations" class="tab collapse">
            <div class="seoo-screen">
                <div class="seoo-panel-intro">
                    <div class="seoo-panel-intro__visual">
                        <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-indexation.png" alt="{l s='Indexations' mod='seooptimizer'}">
                    </div>
                    <div class="seoo-panel-intro__content">
                        <h3 class="seoo-panel-intro__title"><i class="icon-database"></i> {l s='Indexations' mod='seooptimizer'}</h3>
                        <p class="seoo-panel-intro__desc">{l s='Control how search engines index your pages. Define custom rules and configure indexation behavior for supplier, manufacturer, store and sitemap pages.' mod='seooptimizer'}</p>
                    </div>
                    <div class="seoo-panel-intro__actions">
                        <button class="btn btn-default" data-toggle="collapse" data-target="#form-indexation-rule"><i class="icon-plus"></i> {l s='Add new rule' mod='seooptimizer'}</button>
                    </div>
                </div>
                <div id="form-indexation-rule" class="collapse {if isset($show_form_indexation_rule)}in{/if}">{$form_indexation_rule}</div>
                {$data_list_indexation_rules}
                {$form_indexation}
            </div>
        </div>

        {* ── Simple form tabs (single HelperForm = single .seoo-screen) ── *}
        <div id="tab-verification-code" class="tab collapse"><div class="seoo-screen">{$form_verification_code}</div></div>
        <div id="tab-settings" class="tab collapse"><div class="seoo-screen">{$form_settings}</div></div>
        <div id="tab-rich-snippets" class="tab collapse"><div class="seoo-screen">{$form_rich_snippets}{$form_social_meta_data}</div></div>
        <div id="tab-social-configuration" class="tab collapse"><div class="seoo-screen">{$form_social}</div></div>
        <div id="tab-sitemap" class="tab collapse"><div class="seoo-screen">{$form_sitemap}</div></div>
        <div id="tab-link-obfuscator" class="tab collapse"><div class="seoo-screen">{$form_link_obfuscator}</div></div>

        {* ── Audit tabs ── *}
        <div id="tab-audit-heading-hierarchy" class="tab collapse">{$audit_heading_hierarchy}</div>
        <div id="tab-audit-missing-alt" class="tab collapse">{$audit_missing_alt}</div>
        <div id="tab-audit-broken-links" class="tab collapse">{$audit_broken_links}</div>
        <div id="tab-audit-page-load-time" class="tab collapse">{$audit_page_load_time}</div>
        <div id="tab-audit-page-weight" class="tab collapse">{$audit_page_weight}</div>
        <div id="tab-audit-unsecured-links" class="tab collapse">{$audit_unsecured_links}</div>
        <div id="tab-audit-meta-tags" class="tab collapse">{$audit_meta_tags}</div>
        <div id="tab-audit-internal-links" class="tab collapse">{$audit_internal_links}</div>
        <div id="tab-audit-text-ratio" class="tab collapse">{$audit_text_ratio}</div>
        <div id="tab-audit-keyword-check" class="tab collapse">{$audit_keyword_check}</div>
    </div>
</div>
