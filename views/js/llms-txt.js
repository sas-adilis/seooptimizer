(function($) {
    if (typeof SeoOptimizerLlms === 'undefined') return;

    var config = SeoOptimizerLlms;
    var presets = config.presets;
    var i18n = config.i18n;
    var initialized = false;

    function init() {
        if (initialized) return;
        var $editor = $('#seooLlmsEditor');
        if (!$editor.length) return;
        initialized = true;

        $(document).on('click', '[data-llms-preset]', function() {
            var presetKey = $(this).data('llms-preset');
            if (!presets[presetKey]) return;

            $('[data-llms-preset]').removeClass('seoo-robots__preset--active');
            $(this).addClass('seoo-robots__preset--active');

            $editor.val(presets[presetKey]);
            validate();
        });

        $editor.on('input', validate);
        validate();
    }

    function validate() {
        var content = $('#seooLlmsEditor').val();
        var items = [];
        var level = 'ok';

        if (/^# .+/m.test(content)) {
            items.push({ type: 'ok', text: i18n.titlePresent });
        } else {
            items.push({ type: 'err', text: i18n.missingTitle });
            level = 'error';
        }

        if (/^> .+/m.test(content)) {
            items.push({ type: 'ok', text: i18n.descPresent });
        } else {
            items.push({ type: 'warn', text: i18n.missingDesc });
            if (level === 'ok') level = 'warn';
        }

        var sections = content.match(/^## .+/gm);
        if (sections && sections.length > 0) {
            items.push({ type: 'ok', text: sections.length + ' ' + i18n.sectionsFound });
        } else {
            items.push({ type: 'warn', text: i18n.noSections });
            if (level === 'ok') level = 'warn';
        }

        var links = content.match(/^- \[.+\]\(.+\)/gm);
        if (links && links.length > 0) {
            items.push({ type: 'ok', text: links.length + ' ' + i18n.linksDeclared });
        } else {
            items.push({ type: 'warn', text: i18n.noLinks });
            if (level === 'ok') level = 'warn';
        }

        var brokenLinks = content.match(/^- \[[^\]]*\]\(\s*\)/gm);
        if (brokenLinks && brokenLinks.length > 0) {
            items.push({ type: 'err', text: brokenLinks.length + ' ' + i18n.emptyLinks });
            level = 'error';
        }

        renderValidation(level, items);
    }

    function renderValidation(level, items) {
        var $header = $('#seooLlmsValidationHeader');
        var $container = $('#seooLlmsValidationItems');
        var $dot = $('#seooLlmsStatusDot');
        var $status = $('#seooLlmsStatusText');

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
            $status.text(i18n.valid);
        }

        var html = '';
        $.each(items, function(_, i) {
            var cls = i.type === 'ok' ? 'seoo-validation-icon--ok' :
                      i.type === 'warn' ? 'seoo-validation-icon--warn' : 'seoo-validation-icon--err';
            html += '<div class="seoo-robots__validation-item"><span class="seoo-validation-icon ' + cls + '"></span> ' + i.text + '</div>';
        });
        $container.html(html);
    }

    // Init on tab open
    $(document).on('click', 'a[href="#tab-llms-txt"]', function() {
        setTimeout(init, 50);
    });

    init();

})(jQuery);
