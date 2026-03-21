(function () {
    if (typeof SeoOptimizerFrontAudit === 'undefined') {
        return;
    }

    var config = SeoOptimizerFrontAudit;

    function loadAudit() {
        var separator = config.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = config.ajaxUrl + separator + 'audit_url=' + encodeURIComponent(config.currentUrl);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        onAuditLoaded(response.data);
                    } else {
                        onAuditError(response.message || 'Audit failed');
                    }
                } catch (e) {
                    onAuditError('Parse error');
                }
            } else {
                onAuditError('HTTP ' + xhr.status);
            }
        };
        xhr.send();
    }

    function onAuditLoaded(data) {
        var btn = document.getElementById('seoo-fa-btn');
        var btnGrade = document.getElementById('seoo-fa-btn-grade');
        var btnScore = document.getElementById('seoo-fa-btn-score');

        if (btn && data.score) {
            btn.className = 'seoo-fa-btn seoo-fa-btn--' + data.score.color;
            btnGrade.textContent = data.score.grade;
            btnScore.textContent = data.score.score + '/100';
        }

        var panelBody = document.getElementById('seoo-fa-panel-body');
        if (panelBody && data.html) {
            panelBody.innerHTML = data.html;
        }
    }

    function onAuditError(message) {
        var btn = document.getElementById('seoo-fa-btn');
        if (btn) {
            btn.style.display = 'none';
        }

        var panel = document.getElementById('seoo-fa-panel');
        if (panel) {
            panel.style.display = 'none';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAudit);
    } else {
        loadAudit();
    }
})();
