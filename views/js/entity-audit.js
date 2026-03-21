(function () {
    var panel = document.getElementById('seoo-ea');
    if (!panel) {
        return;
    }

    var entityUrl = panel.getAttribute('data-url');
    var ajaxBaseUrl = panel.getAttribute('data-ajax-url');

    function loadAudit() {
        var body = document.getElementById('seoo-ea-body');
        var badge = document.getElementById('seoo-ea-badge');

        // Show loading state
        body.innerHTML = '<div class="seoo-fa-loading"><div class="seoo-fa-spinner seoo-fa-spinner--large"></div><p>Analyse SEO en cours\u2026</p></div>';
        badge.className = 'seoo-ea__badge seoo-ea__badge--loading';
        badge.innerHTML = '<span class="seoo-fa-spinner"></span>';

        var separator = ajaxBaseUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = ajaxBaseUrl + separator + 'ajax=1&action=entityAudit&url=' + encodeURIComponent(entityUrl);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success' && response.data) {
                        onLoaded(response.data);
                    } else {
                        onError(response.message || 'Audit failed');
                    }
                } catch (e) {
                    onError('Parse error');
                }
            } else {
                onError('HTTP ' + xhr.status);
            }
        };
        xhr.send();
    }

    function onLoaded(data) {
        var body = document.getElementById('seoo-ea-body');
        var badge = document.getElementById('seoo-ea-badge');

        if (data.html) {
            body.innerHTML = data.html;
        }

        if (data.score && badge) {
            badge.className = 'seoo-ea__badge seoo-ea__badge--' + data.score.color;
            badge.textContent = data.score.grade + ' ' + data.score.score + '/100';
        }
    }

    function onError(message) {
        var body = document.getElementById('seoo-ea-body');
        var badge = document.getElementById('seoo-ea-badge');

        body.innerHTML = '<div class="seoo-fa-loading"><p>' + message + '</p></div>';
        badge.className = 'seoo-ea__badge seoo-ea__badge--gray';
        badge.textContent = '-';
    }

    // Refresh button
    var refreshBtn = document.getElementById('seoo-ea-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function (e) {
            e.preventDefault();
            loadAudit();
        });
    }

    // Auto-load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAudit);
    } else {
        loadAudit();
    }
})();
