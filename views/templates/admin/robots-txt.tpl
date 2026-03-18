<div class="seoo-robots" id="seoo-robots">
    <div class="panel">
        <div class="seoo-panel-intro">
            <div class="seoo-panel-intro__visual">
                <img src="{$seoo_module_path|escape:'htmlall':'UTF-8'}views/img/panda-robots.png" alt="{l s='Robots.txt' mod='seooptimizer'}">
            </div>
            <div class="seoo-panel-intro__content">
                <h3 class="seoo-panel-intro__title">
                    <i class="icon-file-text"></i>
                    {l s='Robots.txt' mod='seooptimizer'}
                </h3>
                <p class="seoo-panel-intro__desc">{l s='Edit the content of your robots.txt file. Choose a preset adapted to your shop, then customize it if needed.' mod='seooptimizer'}</p>
            </div>
            <div class="seoo-panel-intro__actions">
                <a href="{$seoo_robots_live_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">
                    <i class="icon-external-link"></i> {l s='View live file' mod='seooptimizer'}
                </a>
            </div>
        </div>

        <div class="panel-body">
            {if isset($seoo_robots_success) && $seoo_robots_success}
                <div class="alert alert-success">{l s='The robots.txt file has been saved successfully.' mod='seooptimizer'}</div>
            {/if}
            {if isset($seoo_robots_error) && $seoo_robots_error}
                <div class="alert alert-danger">{$seoo_robots_error|escape:'htmlall':'UTF-8'}</div>
            {/if}

            <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">{l s='Choose a preset adapted to your shop, then customize it if needed.' mod='seooptimizer'}</p>

            <div class="seoo-robots__presets">
                {foreach $seoo_robots_presets as $preset_key => $preset}
                    <div class="seoo-robots__preset {if $preset_key == 'standard'}seoo-robots__preset--active{/if}"
                         data-preset="{$preset_key|escape:'htmlall':'UTF-8'}">
                        {if isset($preset.recommended) && $preset.recommended}
                            <span class="seoo-robots__preset-badge">{l s='Recommended' mod='seooptimizer'}</span>
                        {/if}
                        <div class="seoo-robots__preset-icon">{$preset.icon nofilter}</div>
                        <div class="seoo-robots__preset-name">{$preset.name|escape:'htmlall':'UTF-8'}</div>
                        <div class="seoo-robots__preset-desc">{$preset.desc|escape:'htmlall':'UTF-8'}</div>
                    </div>
                {/foreach}
            </div>

            <form method="post" action="{$seoo_robots_form_action|escape:'htmlall':'UTF-8'}" id="seoo-robots-form">
                <input type="hidden" name="token" value="{$seoo_robots_token|escape:'htmlall':'UTF-8'}">

                <div class="seoo-robots__editor-toolbar">
                    <span class="seoo-robots__editor-label"><i class="icon-pencil"></i> {l s='robots.txt editor' mod='seooptimizer'}</span>
                    <div class="seoo-robots__editor-status">
                        <span class="seoo-robots__status-dot seoo-robots__status-dot--ok" id="seooRobotsStatusDot"></span>
                        <span id="seooRobotsStatusText">{l s='No errors detected' mod='seooptimizer'}</span>
                    </div>
                </div>
                <textarea name="SEOO_ROBOTS_TXT" id="seooRobotsEditor" class="seoo-robots__editor" spellcheck="false">{$seoo_robots_content|escape:'htmlall':'UTF-8'}</textarea>

                <div class="seoo-robots__validation" id="seooRobotsValidation">
                    <div class="seoo-robots__validation-header seoo-robots__validation-header--ok" id="seooRobotsValidationHeader">
                    </div>
                    <div class="seoo-robots__validation-items" id="seooRobotsValidationItems">
                    </div>
                </div>

                <div class="seoo-robots__url-tester">
                    <h4><i class="icon-search"></i> {l s='URL Tester' mod='seooptimizer'}</h4>
                    <p class="seoo-robots__url-tester-desc">{l s='Check if a URL will be blocked or allowed by your current rules.' mod='seooptimizer'}</p>
                    <div class="seoo-robots__url-tester-row">
                        <input type="text" id="seooRobotsTestUrl" placeholder="/my-product.html" value="/recherche?q=chaussures">
                        <button type="button" class="btn btn-default" id="seooRobotsTestBtn">
                            <i class="icon-search"></i> {l s='Test' mod='seooptimizer'}
                        </button>
                    </div>
                    <div id="seooRobotsTestResult" class="seoo-robots__test-result" style="display:none;"></div>
                </div>

                <div class="seoo-robots__actions">
                    <button type="submit" name="submitFormRobotsTxt" class="btn btn-default pull-right" style="background:#05808B;border-color:#05808B;color:#fff;">
                        <i class="icon-save"></i> {l s='Save' mod='seooptimizer'}
                    </button>
                    <button type="submit" name="submitFormRobotsTxtReset" class="btn btn-default">
                        <i class="icon-refresh"></i> {l s='Reset to PrestaShop default' mod='seooptimizer'}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {if $seoo_robots_history|count > 0}
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
                    <p class="seoo-panel-intro__desc">{l s='Previous versions of your robots.txt file. You can restore any backup with one click.' mod='seooptimizer'}</p>
                </div>
            </div>
            <div class="panel-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Date' mod='seooptimizer'}</th>
                            <th>{l s='File' mod='seooptimizer'}</th>
                            <th class="text-right">{l s='Actions' mod='seooptimizer'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $seoo_robots_history as $entry}
                            <tr>
                                <td>{$entry.date|escape:'htmlall':'UTF-8'}</td>
                                <td><code>{$entry.file|escape:'htmlall':'UTF-8'}</code></td>
                                <td class="text-right">
                                    <a href="{$seoo_robots_form_action|escape:'htmlall':'UTF-8'}&restoreRobots={$entry.file|escape:'url':'UTF-8'}&token={$seoo_robots_token|escape:'htmlall':'UTF-8'}" class="btn btn-default btn-xs">
                                        <i class="icon-undo"></i> {l s='Restore' mod='seooptimizer'}
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
</div>

<script>
(function() {
    var shopUrl = '{$seoo_shop_url|escape:"javascript":"UTF-8"}';

    var presets = {$seoo_robots_presets_js nofilter};

    for (var key in presets) {
        if (presets.hasOwnProperty(key)) {
            presets[key] = presets[key].replace(/__SHOP_URL__/g, shopUrl);
        }
    }

    // Preset selection
    var presetCards = document.querySelectorAll('.seoo-robots__preset');
    presetCards.forEach(function(card) {
        card.addEventListener('click', function() {
            var presetKey = card.getAttribute('data-preset');
            if (!presets[presetKey]) return;

            presetCards.forEach(function(c) { c.classList.remove('seoo-robots__preset--active'); });
            card.classList.add('seoo-robots__preset--active');

            document.getElementById('seooRobotsEditor').value = presets[presetKey];
            validateRobots();
        });
    });

    // Validation
    function validateRobots() {
        var content = document.getElementById('seooRobotsEditor').value;
        var items = [];
        var level = 'ok';

        // Check Disallow: /
        if (/^Disallow:\s*\/$/m.test(content) && content.indexOf('Disallow: /\n') !== -1) {
            var hasOnlyRoot = true;
            var lines = content.split('\n');
            for (var i = 0; i < lines.length; i++) {
                if (lines[i].trim() === 'Disallow: /' ) {
                    hasOnlyRoot = true;
                    break;
                }
            }
            if (hasOnlyRoot) {
                items.push({ type: 'err', text: '{l s="Disallow: / detected — no page will be indexed!" mod="seooptimizer" js=1}' });
                level = 'error';
            }
        } else {
            items.push({ type: 'ok', text: '{l s="No Disallow: / in production" mod="seooptimizer" js=1}' });
        }

        // Check Sitemap
        if (/^Sitemap:/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="Sitemap declared" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="No Sitemap directive found" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check cart/account blocked
        if (/Disallow:.*(?:panier|cart|commande|order|mon-compte|my-account)/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="Cart/account/order pages blocked" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="Cart/account pages not blocked — may create duplicate content" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check sort/filter params
        if (/Disallow:.*\?\*?(?:order=|q=|page=)/m.test(content) || /Disallow:.*\*\?(?:order=|q=|page=)/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="Sort/filter parameters blocked" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'warn', text: '{l s="Sort/filter URL parameters not blocked" mod="seooptimizer" js=1}' });
            if (level === 'ok') level = 'warn';
        }

        // Check User-agent
        if (/^User-agent:/m.test(content)) {
            items.push({ type: 'ok', text: '{l s="User-agent directive present" mod="seooptimizer" js=1}' });
        } else {
            items.push({ type: 'err', text: '{l s="No User-agent directive — file will be ignored by crawlers" mod="seooptimizer" js=1}' });
            level = 'error';
        }

        updateValidation(level, items);
    }

    function updateValidation(level, items) {
        var header = document.getElementById('seooRobotsValidationHeader');
        var container = document.getElementById('seooRobotsValidationItems');
        var dot = document.getElementById('seooRobotsStatusDot');
        var statusText = document.getElementById('seooRobotsStatusText');

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
            statusText.textContent = '{l s="No errors detected" mod="seooptimizer" js=1}';
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

    // URL Tester
    document.getElementById('seooRobotsTestBtn').addEventListener('click', function() {
        testUrl();
    });

    document.getElementById('seooRobotsTestUrl').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); testUrl(); }
    });

    function testUrl() {
        var url = document.getElementById('seooRobotsTestUrl').value.trim();
        if (!url) return;

        var content = document.getElementById('seooRobotsEditor').value;
        var lines = content.split('\n');
        var blocked = false;
        var matchedRule = '';

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (line.indexOf('Disallow:') !== 0) continue;
            var rule = line.substring(9).trim();
            if (!rule) continue;

            var regexStr = rule
                .replace(/[.+^${}()|[\]\\]/g, '\\$&')
                .replace(/\*/g, '.*')
                .replace(/\\\?/g, '\\?');

            try {
                if (new RegExp(regexStr).test(url)) {
                    blocked = true;
                    matchedRule = rule;
                    break;
                }
            } catch(e) { }
        }

        var el = document.getElementById('seooRobotsTestResult');
        el.style.display = 'block';
        if (blocked) {
            el.className = 'seoo-robots__test-result seoo-robots__test-result--blocked';
            el.innerHTML = '<i class="icon-ban"></i> <strong>{l s="Blocked" mod="seooptimizer" js=1}</strong> — {l s="Matches rule:" mod="seooptimizer" js=1} <code>Disallow: ' + matchedRule.replace(/</g, '&lt;') + '</code>';
        } else {
            el.className = 'seoo-robots__test-result seoo-robots__test-result--allowed';
            el.innerHTML = '<i class="icon-check"></i> <strong>{l s="Allowed" mod="seooptimizer" js=1}</strong> — {l s="This URL will be crawled by robots." mod="seooptimizer" js=1}';
        }
    }

    // Auto-validate on load and on edit
    document.getElementById('seooRobotsEditor').addEventListener('input', validateRobots);
    validateRobots();
})();
</script>
