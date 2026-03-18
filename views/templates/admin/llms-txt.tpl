<div class="seoo-robots" id="seoo-llms">
    <div class="panel">
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-llms.png" alt="{l s='llms.txt' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <i class="icon-file-text"></i>
                    {l s='llms.txt — AI & LLM Configuration' mod='seooptimizer'}
                </h3>
                <p class="seoo-panel-intro__desc">{l s='The llms.txt file describes your website structure for AI assistants and large language models (ChatGPT, Claude, Gemini...). It helps them understand your site and provide accurate answers about your products and services.' mod='seooptimizer'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                {if $seoo_llms_exists}
                    <a href="{$seoo_llms_form_action|escape:'htmlall':'UTF-8'}&submitFormLlmsTxtDelete=1&token={$seoo_llms_token|escape:'htmlall':'UTF-8'}" class="btn btn-default" onclick="return confirm('{l s="Delete the llms.txt file?" mod="seooptimizer" js=1}');" style="color:#dc2626;">
                        <i class="icon-trash"></i> {l s='Delete file' mod='seooptimizer'}
                    </a>
                {/if}
                <a href="{$seoo_llms_live_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">
                    <i class="icon-external-link"></i> {l s='View live file' mod='seooptimizer'}
                </a>
            </div>
        </div>

        <div class="panel-body">
            <div class="seoo-llms__info">
                <h4><i class="icon-info-sign"></i> {l s='What is llms.txt?' mod='seooptimizer'}</h4>
                <p>{l s='The llms.txt file is a standard proposed to help AI models understand the structure and content of a website. Unlike robots.txt which controls crawling, llms.txt provides a curated description using Markdown format with links to your key pages.' mod='seooptimizer'}</p>
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

            <p style="font-size:13px;color:#6b7280;margin:16px 0;">{l s='Choose a preset adapted to your shop, then customize it with your actual page URLs.' mod='seooptimizer'}</p>

            <div class="seoo-robots__presets">
                {foreach $seoo_llms_presets as $preset_key => $preset}
                    <div class="seoo-robots__preset {if $preset_key == 'ecommerce'}seoo-robots__preset--active{/if}"
                         data-llms-preset="{$preset_key|escape:'htmlall':'UTF-8'}">
                        {if isset($preset.recommended) && $preset.recommended}
                            <span class="seoo-robots__preset-badge">{l s='Recommended' mod='seooptimizer'}</span>
                        {/if}
                        <div class="seoo-robots__preset-icon">{$preset.icon nofilter}</div>
                        <div class="seoo-robots__preset-name">{$preset.name|escape:'htmlall':'UTF-8'}</div>
                        <div class="seoo-robots__preset-desc">{$preset.desc|escape:'htmlall':'UTF-8'}</div>
                    </div>
                {/foreach}
            </div>

            <form method="post" action="{$seoo_llms_form_action|escape:'htmlall':'UTF-8'}" id="seoo-llms-form">
                <input type="hidden" name="token" value="{$seoo_llms_token|escape:'htmlall':'UTF-8'}">

                <div class="seoo-robots__editor-toolbar">
                    <span class="seoo-robots__editor-label"><i class="icon-pencil"></i> {l s='llms.txt editor' mod='seooptimizer'}</span>
                    <div class="seoo-robots__editor-status">
                        <span class="seoo-robots__status-dot seoo-robots__status-dot--ok" id="seooLlmsStatusDot"></span>
                        <span id="seooLlmsStatusText">{l s='Valid' mod='seooptimizer'}</span>
                    </div>
                </div>
                <textarea name="SEOO_LLMS_TXT" id="seooLlmsEditor" class="seoo-robots__editor" spellcheck="false">{$seoo_llms_content|escape:'htmlall':'UTF-8'}</textarea>

                <div class="seoo-robots__save-bar">
                    <button type="submit" name="submitFormLlmsTxt" class="btn btn-default" style="background:#05808B;border-color:#05808B;color:#fff;">
                        <i class="icon-save"></i> {l s='Save' mod='seooptimizer'}
                    </button>
                </div>

                <div class="seoo-robots__validation" id="seooLlmsValidation">
                    <div class="seoo-robots__validation-header seoo-robots__validation-header--ok" id="seooLlmsValidationHeader">
                    </div>
                    <div class="seoo-robots__validation-items" id="seooLlmsValidationItems">
                    </div>
                </div>
            </form>

            <div class="seoo-llms__ai-bots">
                <h4><i class="icon-globe"></i> {l s='Known AI crawlers and their robots.txt directives' mod='seooptimizer'}</h4>
                <p style="font-size:12px;color:#6b7280;margin-bottom:10px;">{l s='To block or allow specific AI crawlers, add these directives to your robots.txt file.' mod='seooptimizer'}</p>
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
                        <tr><td><strong>ClaudeBot</strong></td><td>Anthropic</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>anthropic-ai</code></td></tr>
                        <tr><td><strong>Google-Extended</strong></td><td>Google</td><td>{l s='Gemini training' mod='seooptimizer'}</td><td><code>Google-Extended</code></td></tr>
                        <tr><td><strong>Bytespider</strong></td><td>ByteDance</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>Bytespider</code></td></tr>
                        <tr><td><strong>CCBot</strong></td><td>Common Crawl</td><td>{l s='Open dataset' mod='seooptimizer'}</td><td><code>CCBot</code></td></tr>
                        <tr><td><strong>PerplexityBot</strong></td><td>Perplexity</td><td>{l s='Search & answers' mod='seooptimizer'}</td><td><code>PerplexityBot</code></td></tr>
                        <tr><td><strong>Cohere-ai</strong></td><td>Cohere</td><td>{l s='Training' mod='seooptimizer'}</td><td><code>cohere-ai</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {if $seoo_llms_history_html}
        <div class="panel">
            <div class="seoo-panel-intro">
                <div class="seoo-panel-intro__visual">
                    <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-history.png" alt="{l s='History' mod='seooptimizer'}">
                </div>
                <div class="seoo-panel-intro__content">
                    <h3 class="seoo-panel-intro__title">
                        <i class="icon-time"></i>
                        {l s='History' mod='seooptimizer'}
                    </h3>
                    <p class="seoo-panel-intro__desc">{l s='Previous versions of your llms.txt file. You can restore any backup with one click.' mod='seooptimizer'}</p>
                </div>
            </div>
            <div class="seoo-robots__history-list">
                {$seoo_llms_history_html nofilter}
            </div>
        </div>
    {/if}
</div>

<script>
(function() {
    var presets = {$seoo_llms_presets_js nofilter};

    // Preset selection
    var presetCards = document.querySelectorAll('[data-llms-preset]');
    presetCards.forEach(function(card) {
        card.addEventListener('click', function() {
            var presetKey = card.getAttribute('data-llms-preset');
            if (!presets[presetKey]) return;

            presetCards.forEach(function(c) { c.classList.remove('seoo-robots__preset--active'); });
            card.classList.add('seoo-robots__preset--active');

            document.getElementById('seooLlmsEditor').value = presets[presetKey];
            validateLlms();
        });
    });

    function validateLlms() {
        var content = document.getElementById('seooLlmsEditor').value;
        var items = [];
        var level = 'ok';

        // Check title (# ...)
        if (/^# .+/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="Title present (# ...)" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'err', text: '{l s="Missing title — add a line starting with # followed by your site name" mod="seooptimizer" js=1}' });
            level = 'error';
        }

        // Check description (> ...)
        if (/^> .+/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="Description present (> ...)" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="Missing description — add a line starting with > followed by a short description" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check sections (## ...)
        var sections = content.match(/^## .+/gm);
        if (sections && sections.length > 0) {
            items.push({ type: 'ok', text: sections.length + ' {l s="section(s) found" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="No sections found — add ## Section headings" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check links (- [...](...))
        var links = content.match(/^- \[.+\]\(.+\)/gm);
        if (links && links.length > 0) {
            items.push({ type: 'ok', text: links.length + ' {l s="link(s) declared" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="No links found — add links with - [Text](url): description" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check for broken markdown links
        var brokenLinks = content.match(/^- \[[^\]]*\]\(\s*\)/gm);
        if (brokenLinks && brokenLinks.length > 0) {
            items.push({ type: 'err', text: brokenLinks.length + ' {l s="link(s) with empty URL" mod="seooptimizer" js=1}' });
            level = 'error';
        }

        updateValidation(level, items);
    }

    function updateValidation(level, items) {
        var header = document.getElementById('seooLlmsValidationHeader');
        var container = document.getElementById('seooLlmsValidationItems');
        var dot = document.getElementById('seooLlmsStatusDot');
        var statusText = document.getElementById('seooLlmsStatusText');

        var okCount = 0, warnCount = 0, errCount = 0;
        items.forEach(function(i) {
            if (i.type === 'ok') okCount++;
            else if (i.type === 'warn') warnCount++;
            else errCount++;
        });

        header.className = 'seoo-robots__validation-header seoo-robots__validation-header--' + level;

        if (level === 'error') {
            header.textContent = errCount + ' {l s="error(s) detected" mod="seooptimizer" js=1}';
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--error';
            statusText.textContent = errCount + ' {l s="error(s)" mod="seooptimizer" js=1}';
        } else if (level === 'warn') {
            header.textContent = warnCount + ' {l s="warning(s)" mod="seooptimizer" js=1}';
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--warn';
            statusText.textContent = warnCount + ' {l s="warning(s)" mod="seooptimizer" js=1}';
        } else {
            header.textContent = okCount + ' {l s="checks passed" mod="seooptimizer" js=1}';
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--ok';
            statusText.textContent = '{l s="Valid" mod="seooptimizer" js=1}';
        }

        var html = '';
        items.forEach(function(i) {
            var icon = i.type === 'ok' ? '<i class="icon-check" style="color:#16a34a"></i>' :
                       i.type === 'warn' ? '<i class="icon-warning" style="color:#d97706"></i>' :
                       '<i class="icon-times" style="color:#dc2626"></i>';
            html += '<div class="seoo-robots__validation-item">' + icon + ' ' + i.text + '</div>';
        });
        container.innerHTML = html;
    }

    document.getElementById('seooLlmsEditor').addEventListener('input', validateLlms);
    validateLlms();
})();
</script>
