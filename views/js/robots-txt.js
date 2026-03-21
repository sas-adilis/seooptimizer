(function($) {
    if (typeof SeoOptimizerRobots === 'undefined') return;

    var config = SeoOptimizerRobots;
    var presets = config.presets;
    var i18n = config.i18n;
    var initialized = false;

    $.each(presets, function(key, val) {
        presets[key] = val.replace(/__SHOP_URL__/g, config.shopUrl);
    });

    function init() {
        if (initialized) return;
        var $editor = $('#seooRobotsEditor');
        if (!$editor.length) return;
        initialized = true;

        // Preset selection
        $(document).on('click', '.seoo-robots__preset', function() {
            var presetKey = $(this).data('preset');
            if (!presets[presetKey]) return;

            $('.seoo-robots__preset').removeClass('seoo-robots__preset--active');
            $(this).addClass('seoo-robots__preset--active');

            $editor.val(presets[presetKey]);
            validateRobots();
        });

        // URL Tester
        $(document).on('click', '#seooRobotsTestBtn', testUrl);
        $(document).on('keypress', '#seooRobotsTestUrl', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); testUrl(); }
        });

        $editor.on('input', validateRobots);
        validateRobots();
    }

    function validateRobots() {
        var content = $('#seooRobotsEditor').val();
        var items = [];
        var level = 'ok';

        if (/^Disallow:\s*\/$/m.test(content) && content.indexOf('Disallow: /\n') !== -1) {
            items.push({ type: 'err', text: i18n.disallowDetected });
            level = 'error';
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

        renderValidation('#seooRobotsValidationHeader', '#seooRobotsValidationItems', '#seooRobotsStatusDot', '#seooRobotsStatusText', level, items);
    }

    function renderValidation(headerSel, containerSel, dotSel, statusSel, level, items) {
        var $header = $(headerSel);
        var $container = $(containerSel);
        var $dot = $(dotSel);
        var $status = $(statusSel);

        if (!$header.length) return;

        var ok = 0, warn = 0, err = 0;
        $.each(items, function(_, i) {
            if (i.type === 'ok') ok++;
            else if (i.type === 'warn') warn++;
            else err++;
        });

        $header.attr('class', 'seoo-robots__validation-header seoo-robots__validation-header--' + level);

        if (level === 'error') {
            $header.text(err + ' ' + i18n.errorsDetected);
            $dot.attr('class', 'seoo-robots__status-dot seoo-robots__status-dot--error');
            $status.text(err + ' ' + i18n.errors);
        } else if (level === 'warn') {
            $header.text(warn + ' ' + i18n.warnings);
            $dot.attr('class', 'seoo-robots__status-dot seoo-robots__status-dot--warn');
            $status.text(warn + ' ' + i18n.warnings);
        } else {
            $header.text(ok + ' ' + i18n.checksPassed);
            $dot.attr('class', 'seoo-robots__status-dot seoo-robots__status-dot--ok');
            $status.text(i18n.noErrors);
        }

        var html = '';
        $.each(items, function(_, i) {
            var cls = i.type === 'ok' ? 'seoo-validation-icon--ok' :
                      i.type === 'warn' ? 'seoo-validation-icon--warn' : 'seoo-validation-icon--err';
            html += '<div class="seoo-robots__validation-item"><span class="seoo-validation-icon ' + cls + '"></span> ' + i.text + '</div>';
        });
        $container.html(html);
    }

    function testUrl() {
        var url = $.trim($('#seooRobotsTestUrl').val());
        if (!url) return;

        var content = $('#seooRobotsEditor').val();
        var lines = content.split('\n');
        var blocked = false;
        var matchedRule = '';

        for (var idx = 0; idx < lines.length; idx++) {
            var line = $.trim(lines[idx]);
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

        var $el = $('#seooRobotsTestResult');
        $el.show();
        if (blocked) {
            $el.attr('class', 'seoo-robots__test-result seoo-robots__test-result--blocked')
               .html('<span class="seoo-validation-icon seoo-validation-icon--err"></span> <strong>' + i18n.blocked + '</strong> — ' + i18n.matchesRule + ' <code>Disallow: ' + $('<span>').text(matchedRule).html() + '</code>');
        } else {
            $el.attr('class', 'seoo-robots__test-result seoo-robots__test-result--allowed')
               .html('<span class="seoo-validation-icon seoo-validation-icon--ok"></span> <strong>' + i18n.allowed + '</strong> — ' + i18n.willBeCrawled);
        }
    }

    // Init on tab open
    $(document).on('click', 'a[href="#tab-robots-txt"]', function() {
        setTimeout(init, 50);
    });

    // Try immediate init
    init();

})(jQuery);
