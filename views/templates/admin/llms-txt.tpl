<div class="seoo-robots seoo-screen" id="seoo-llms">
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-llms.png" alt="{l s='llms.txt' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/file-text.svg"}</span>
                    {l s='AI Visibility — Get found by AI assistants (GEO)' mod='seooptimizer'}
                </h3>
                <p class="seoo-panel-intro__desc">{l s='The llms.txt file describes your website structure for AI assistants and large language models (ChatGPT, Claude, Gemini...). It helps them understand your site and provide accurate answers about your products and services.' mod='seooptimizer'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#seooLlmsHelpModal">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/question.svg"}</span> {l s='Help' mod='seooptimizer'}
                </button>
                {if $seoo_llms_exists}
                    <a href="{$seoo_llms_form_action|escape:'htmlall':'UTF-8'}&submitFormLlmsTxtDelete=1&token={$seoo_llms_token|escape:'htmlall':'UTF-8'}" class="btn btn-default" onclick="return confirm('{l s="Delete the llms.txt file?" mod="seooptimizer" js=1}');" style="color:#dc2626;">
                        <span class="seoo-icon">{include file="module:seooptimizer/views/icons/trash.svg"}</span> {l s='Delete file' mod='seooptimizer'}
                    </a>
                {/if}
                <a href="{$seoo_llms_live_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">
                    <span class="seoo-icon">{include file="module:seooptimizer/views/icons/arrow-square-out.svg"}</span> {l s='View live file' mod='seooptimizer'}
                </a>
            </div>
        </div>

        <div class="panel-body">
            <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">{l s='Choose a preset adapted to your shop, then customize it with your actual page URLs.' mod='seooptimizer'}</p>

            <div class="seoo-robots__presets">
                {foreach $seoo_llms_presets as $preset_key => $preset}
                    <div class="seoo-robots__preset {if $preset_key == 'ecommerce'}seoo-robots__preset--active{/if}"
                         data-llms-preset="{$preset_key|escape:'htmlall':'UTF-8'}">
                        {if isset($preset.recommended) && $preset.recommended}
                            <span class="seoo-robots__preset-badge">{l s='Recommended' mod='seooptimizer'}</span>
                        {/if}
                        <div class="seoo-robots__preset-icon"><i class="{$preset.icon_class|escape:'htmlall':'UTF-8'}" style="font-size:28px;color:{$preset.icon_color|escape:'htmlall':'UTF-8'}"></i></div>
                        <div class="seoo-robots__preset-name">{$preset.name|escape:'htmlall':'UTF-8'}</div>
                        <div class="seoo-robots__preset-desc">{$preset.desc|escape:'htmlall':'UTF-8'}</div>
                    </div>
                {/foreach}
            </div>

            <form method="post" action="{$seoo_llms_form_action|escape:'htmlall':'UTF-8'}" id="seoo-llms-form">
                <input type="hidden" name="token" value="{$seoo_llms_token|escape:'htmlall':'UTF-8'}">

                <div class="seoo-robots__editor-toolbar">
                    <span class="seoo-robots__editor-label"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/pencil-simple.svg"}</span> {l s='llms.txt editor' mod='seooptimizer'}</span>
                    <div class="seoo-robots__editor-status">
                        <span class="seoo-robots__status-dot seoo-robots__status-dot--ok" id="seooLlmsStatusDot"></span>
                        <span id="seooLlmsStatusText">{l s='Valid' mod='seooptimizer'}</span>
                    </div>
                </div>
                <textarea name="SEOO_LLMS_TXT" id="seooLlmsEditor" class="seoo-robots__editor" spellcheck="false">{$seoo_llms_content|escape:'htmlall':'UTF-8'}</textarea>

                <div class="seoo-robots__save-bar">
                    <button type="submit" name="submitFormLlmsTxt" class="btn btn-default" style="background:#05808B;border-color:#05808B;color:#fff;">
                        <span class="seoo-icon">{include file="module:seooptimizer/views/icons/floppy-disk.svg"}</span> {l s='Save' mod='seooptimizer'}
                    </button>
                </div>

                <div class="seoo-robots__validation" id="seooLlmsValidation">
                    <div class="seoo-robots__validation-header seoo-robots__validation-header--ok" id="seooLlmsValidationHeader">
                    </div>
                    <div class="seoo-robots__validation-items" id="seooLlmsValidationItems">
                    </div>
                </div>
            </form>
        </div>

    {if $seoo_llms_history_html}
        <div class="seoo-panel-intro" style="border-top:1px solid #e8e8e8;">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-history.png" alt="{l s='History' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/clock.svg"}</span> {l s='History' mod='seooptimizer'}</h3>
                <p class="seoo-panel-intro__desc">{l s='Previous versions of your llms.txt file. You can restore any backup with one click.' mod='seooptimizer'}</p>
            </div>
        </div>
        {$seoo_llms_history_html nofilter}
    {/if}
</div>

{* ── Help Modal ── *}
<div class="modal fade" id="seooLlmsHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/question.svg"}</span> {l s='About llms.txt & AI crawlers' mod='seooptimizer'}</h4>
            </div>
            <div class="modal-body">
                <div class="seoo-llms__info" style="margin-bottom:24px;">
                    <h4><span class="seoo-icon">{include file="module:seooptimizer/views/icons/info.svg"}</span> {l s='What is llms.txt?' mod='seooptimizer'}</h4>
                    <p>{l s='The llms.txt file is a standard proposed to help AI models understand the structure and content of a website. Unlike robots.txt which controls crawling, llms.txt provides a curated description using Markdown format with links to your key pages.' mod='seooptimizer'}</p>
                    <p>{l s='When an AI assistant encounters your llms.txt file, it can use it to provide more accurate and relevant answers about your products and services to users.' mod='seooptimizer'}</p>
                    <div class="seoo-llms__format-example">
                        <strong>{l s='Format:' mod='seooptimizer'}</strong>
                        <code>
                            # Site name<br>
                            > Short description<br>
                            ## Section<br>
                            - [Link text](url): description
                        </code>
                    </div>
                </div>

                <h4 style="font-size:15px;font-weight:600;margin-bottom:12px;"><span class="seoo-icon">{include file="module:seooptimizer/views/icons/globe.svg"}</span> {l s='Known AI crawlers and their robots.txt directives' mod='seooptimizer'}</h4>
                <p style="font-size:13px;color:#6b7280;margin-bottom:10px;">{l s='To block or allow specific AI crawlers, add these User-Agent directives to your robots.txt file.' mod='seooptimizer'}</p>
                <table class="table" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>{l s='Bot' mod='seooptimizer'}</th>
                            <th>{l s='Company' mod='seooptimizer'}</th>
                            <th>{l s='Purpose' mod='seooptimizer'}</th>
                            <th>{l s='User-Agent' mod='seooptimizer'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>GPTBot</strong></td><td>OpenAI</td><td>{l s='Training & browsing' mod='seooptimizer'}</td><td><code>GPTBot</code></td></tr>
                        <tr><td><strong>ChatGPT-User</strong></td><td>OpenAI</td><td>{l s='Browsing (user queries)' mod='seooptimizer'}</td><td><code>ChatGPT-User</code></td></tr>
                        <tr><td><strong>OAI-SearchBot</strong></td><td>OpenAI</td><td>{l s='SearchGPT' mod='seooptimizer'}</td><td><code>OAI-SearchBot</code></td></tr>
                        <tr><td><strong>ClaudeBot</strong></td><td>Anthropic</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>anthropic-ai</code></td></tr>
                        <tr><td><strong>Google-Extended</strong></td><td>Google</td><td>{l s='Gemini training' mod='seooptimizer'}</td><td><code>Google-Extended</code></td></tr>
                        <tr><td><strong>Bytespider</strong></td><td>ByteDance</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>Bytespider</code></td></tr>
                        <tr><td><strong>CCBot</strong></td><td>Common Crawl</td><td>{l s='Open dataset' mod='seooptimizer'}</td><td><code>CCBot</code></td></tr>
                        <tr><td><strong>PerplexityBot</strong></td><td>Perplexity</td><td>{l s='Search & answers' mod='seooptimizer'}</td><td><code>PerplexityBot</code></td></tr>
                        <tr><td><strong>Cohere-ai</strong></td><td>Cohere</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>cohere-ai</code></td></tr>
                        <tr><td><strong>Meta-ExternalAgent</strong></td><td>Meta</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>Meta-ExternalAgent</code></td></tr>
                    </tbody>
                </table>

                <div style="background:#f0f5f5;border-radius:6px;padding:14px 18px;margin-top:16px;">
                    <strong style="font-size:13px;">{l s='Example robots.txt block for all AI bots:' mod='seooptimizer'}</strong>
                    <pre style="margin:8px 0 0;font-size:12px;background:#fff;padding:10px;border-radius:4px;border:1px solid #e0e3e9;">User-agent: GPTBot
User-agent: ChatGPT-User
User-agent: Google-Extended
User-agent: anthropic-ai
User-agent: CCBot
User-agent: Bytespider
User-agent: PerplexityBot
User-agent: cohere-ai
User-agent: Meta-ExternalAgent
Disallow: /</pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='seooptimizer'}</button>
            </div>
        </div>
    </div>
</div>
