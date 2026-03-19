(function() {
    if (typeof SeoOptimizerRobots === 'undefined') return;

    var config = SeoOptimizerRobots;
    var presets = config.presets;
    var i18n = config.i18n;

    for (var key in presets) {
        if (presets.hasOwnProperty(key)) {
            presets[key] = presets[key].replace(/__SHOP_URL__/g, config.shopUrl);
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

        if (/^Disallow:\s*\/$/m.test(content) && content.indexOf('Disallow: /\n') !== -1) {
            var lines = content.split('\n');
            for (var i = 0; i < lines.length; i++) {
                if (lines[i].trim() === 'Disallow: /') {
                    items.push({ type: 'err', text: i18n.disallowDetected });
                    level = 'error';
                    break;
                }
            }
        } else {
            items.push({ type: 'ok', text: i18n.noDisallow });
        }

        if (/^Sitemap:/m.test(content)) {
            items.push({ type: 'ok', text: i18n.sitemapDeclared });
        } else {
            items.push({ type: 'warn', text: i18n.noSitemap });
            if (level === 'ok') level = 'warn';
        }

        if (/Disallow:.*(?:panier|cart|commande|order|mon-compte|my-account)/m.test(content)) {
            items.push({ type: 'ok', text: i18n.cartBlocked });
        } else {
            items.push({ type: 'warn', text: i18n.cartNotBlocked });
            if (level === 'ok') level = 'warn';
        }

        if (/Disallow:.*\?\*?(?:order=|q=|page=)/m.test(content) || /Disallow:.*\*\?(?:order=|q=|page=)/m.test(content)) {
            items.push({ type: 'ok', text: i18n.paramsBlocked });
        } else {
            items.push({ type: 'warn', text: i18n.paramsNotBlocked });
            if (level === 'ok') level = 'warn';
        }

        if (/^User-agent:/m.test(content)) {
            items.push({ type: 'ok', text: i18n.userAgentPresent });
        } else {
            items.push({ type: 'err', text: i18n.noUserAgent });
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
            header.textContent = errCount + ' ' + i18n.errorsDetected;
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--error';
            statusText.textContent = errCount + ' ' + i18n.errors;
        } else if (level === 'warn') {
            header.textContent = warnCount + ' ' + i18n.warnings;
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--warn';
            statusText.textContent = warnCount + ' ' + i18n.warnings;
        } else {
            header.textContent = okCount + ' ' + i18n.checksPassed;
            dot.className = 'seoo-robots__status-dot seoo-robots__status-dot--ok';
            statusText.textContent = i18n.noErrors;
        }

        var html = '';
        items.forEach(function(i) {
            var cls = i.type === 'ok' ? 'seoo-validation-icon--ok' :
                      i.type === 'warn' ? 'seoo-validation-icon--warn' : 'seoo-validation-icon--err';
            html += '<div class="seoo-robots__validation-item"><span class="seoo-validation-icon ' + cls + '"></span> ' + i.text + '</div>';
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
            el.innerHTML = '<span class="seoo-validation-icon seoo-validation-icon--err"></span> <strong>' + i18n.blocked + '</strong> — ' + i18n.matchesRule + ' <code>Disallow: ' + matchedRule.replace(/</g, '&lt;') + '</code>';
        } else {
            el.className = 'seoo-robots__test-result seoo-robots__test-result--allowed';
            el.innerHTML = '<span class="seoo-validation-icon seoo-validation-icon--ok"></span> <strong>' + i18n.allowed + '</strong> — ' + i18n.willBeCrawled;
        }
    }

    document.getElementById('seooRobotsEditor').addEventListener('input', validateRobots);
    validateRobots();
})();
