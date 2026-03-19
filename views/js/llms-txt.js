(function() {
    if (typeof SeoOptimizerLlms === 'undefined') return;

    var config = SeoOptimizerLlms;
    var presets = config.presets;
    var i18n = config.i18n;

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
            statusText.textContent = i18n.valid;
        }

        var html = '';
        items.forEach(function(i) {
            var cls = i.type === 'ok' ? 'seoo-validation-icon--ok' :
                      i.type === 'warn' ? 'seoo-validation-icon--warn' : 'seoo-validation-icon--err';
            html += '<div class="seoo-robots__validation-item"><span class="seoo-validation-icon ' + cls + '"></span> ' + i.text + '</div>';
        });
        container.innerHTML = html;
    }

    document.getElementById('seooLlmsEditor').addEventListener('input', validateLlms);
    validateLlms();
})();
