/**
 * SEO Optimizer — Inject SEO score column into legacy BO listings.
 *
 * Works with AdminCategories, AdminManufacturers, AdminSuppliers, AdminCmsContent.
 * - Shows gray badge with spinner while loading
 * - Fetches existing scores via AJAX
 * - Audits unscored entities in batches of 10
 * - Click on a scored badge opens the SEO audit side panel
 */
(function () {
    if (typeof SeoOptimizerScoreColumn === 'undefined') {
        return;
    }

    var config = SeoOptimizerScoreColumn;
    var BATCH_SIZE = 10;

    document.addEventListener('DOMContentLoaded', function () {
        var table = document.querySelector('table.table');
        if (!table) {
            return;
        }

        // Collect entity IDs from row checkboxes
        var rows = table.querySelectorAll('tbody tr');
        var entityRows = [];
        var ids = [];

        rows.forEach(function (row) {
            var checkbox = row.querySelector('input[type="checkbox"][name*="Box"]');
            if (checkbox) {
                var id = parseInt(checkbox.value, 10);
                if (id > 0) {
                    ids.push(id);
                    entityRows.push({id: id, row: row});
                }
            }
        });

        if (ids.length === 0) {
            return;
        }

        // Insert column header before the last <th> (actions column)
        var headerRow = table.querySelector('thead tr:first-child');
        if (headerRow) {
            var th = document.createElement('th');
            th.textContent = 'SEO';
            th.className = 'text-center fixed-width-xs';
            var lastTh = headerRow.lastElementChild;
            headerRow.insertBefore(th, lastTh);
        }

        // Insert empty <th> in filter row if present
        var filterRow = table.querySelector('thead tr.filter, thead tr:nth-child(2)');
        if (filterRow && filterRow !== headerRow) {
            var filterTh = document.createElement('th');
            filterTh.innerHTML = '&nbsp;';
            var lastFilterTh = filterRow.lastElementChild;
            filterRow.insertBefore(filterTh, lastFilterTh);
        }

        // Insert placeholder cells with spinner in each row
        entityRows.forEach(function (item) {
            var td = document.createElement('td');
            td.className = 'text-center seoo-score-cell';
            td.setAttribute('data-entity-id', item.id);
            td.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray seoo-grade-badge--pending">'
                + '<span class="seoo-grade-spinner"></span></span>';
            var lastTd = item.row.lastElementChild;
            item.row.insertBefore(td, lastTd);
        });

        // Inject the side panel into the page (once)
        injectSidePanel();

        // Fetch scores via AJAX
        var separator = config.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = config.ajaxUrl + separator + 'ajax=1&action=getEntityScores&entity_type='
            + encodeURIComponent(config.entityType) + '&ids=' + ids.join(',');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4 || xhr.status !== 200) {
                return;
            }

            try {
                var response = JSON.parse(xhr.responseText);
                if (response.status !== 'success') {
                    return;
                }

                var scores = response.data;
                var unscoredIds = [];

                entityRows.forEach(function (item) {
                    var td = item.row.querySelector('.seoo-score-cell');
                    if (!td) {
                        return;
                    }

                    var score = scores[item.id];
                    if (score && score.grade && score.grade !== '-' && score.grade !== '') {
                        renderScoredBadge(td, item.id, score);
                    } else {
                        // Keep spinner — will be audited in batch
                        unscoredIds.push(item.id);
                    }
                });

                // Launch batch audit for unscored entities
                if (unscoredIds.length > 0) {
                    processBatchAudit(unscoredIds, 0, entityRows);
                }
            } catch (e) {
                // Silently fail
            }
        };
        xhr.send();
    });

    /**
     * Render a scored badge with click handler.
     */
    function renderScoredBadge(td, entityId, score) {
        var badge = document.createElement('a');
        badge.href = '#';
        badge.className = 'seoo-grade-badge seoo-grade-badge--' + score.color;
        badge.title = 'Score: ' + Math.round(score.score) + '/100';
        badge.textContent = score.grade;
        badge.addEventListener('click', function (e) {
            e.preventDefault();
            openAuditPanel(entityId);
        });
        td.innerHTML = '';
        td.appendChild(badge);
    }

    /**
     * Process batch audits for unscored entities, BATCH_SIZE at a time.
     */
    function processBatchAudit(unscoredIds, offset, entityRows) {
        var batch = unscoredIds.slice(offset, offset + BATCH_SIZE);
        if (batch.length === 0) {
            return;
        }

        var separator = config.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = config.ajaxUrl + separator + 'ajax=1&action=batchEntityAudit&entity_type='
            + encodeURIComponent(config.entityType) + '&ids=' + batch.join(',');

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
                        // Update badges for this batch
                        entityRows.forEach(function (item) {
                            if (response.data[item.id]) {
                                var td = item.row.querySelector('.seoo-score-cell');
                                if (td) {
                                    renderScoredBadge(td, item.id, response.data[item.id]);
                                }
                            }
                        });
                    }
                } catch (e) {
                    // Continue to next batch anyway
                }
            }

            // Mark failed ones as gray "-"
            batch.forEach(function (id) {
                var row = entityRows.find(function (item) { return item.id === id; });
                if (row) {
                    var td = row.row.querySelector('.seoo-score-cell');
                    if (td && td.querySelector('.seoo-grade-badge--pending')) {
                        td.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray" title="Audit failed">-</span>';
                    }
                }
            });

            // Process next batch
            processBatchAudit(unscoredIds, offset + BATCH_SIZE, entityRows);
        };
        xhr.send();
    }

    /**
     * Inject the SEO audit side panel into the page.
     */
    function injectSidePanel() {
        if (document.getElementById('seoo-bo-panel')) {
            return;
        }

        var panel = document.createElement('div');
        panel.id = 'seoo-bo-panel';
        panel.className = 'seoo-fa-panel';
        panel.innerHTML = '<div class="seoo-fa-panel__header">'
            + '<span class="seoo-fa-panel__title">SEO Audit</span>'
            + '<button class="seoo-fa-panel__close" type="button" onclick="document.getElementById(\'seoo-bo-panel\').classList.remove(\'seoo-fa-panel--open\');">&times;</button>'
            + '</div>'
            + '<div id="seoo-bo-panel-body" class="seoo-fa-panel__body">'
            + '<div class="seoo-fa-loading"><div class="seoo-fa-spinner seoo-fa-spinner--large"></div><p>Analyse SEO en cours\u2026</p></div>'
            + '</div>';

        document.body.appendChild(panel);
    }

    /**
     * Open the side panel and load the audit for a given entity.
     */
    function openAuditPanel(entityId) {
        var panel = document.getElementById('seoo-bo-panel');
        var panelBody = document.getElementById('seoo-bo-panel-body');
        if (!panel || !panelBody) {
            return;
        }

        panel.classList.add('seoo-fa-panel--open');
        panelBody.innerHTML = '<div class="seoo-fa-loading"><div class="seoo-fa-spinner seoo-fa-spinner--large"></div><p>Analyse SEO en cours\u2026</p></div>';

        var separator = config.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = config.ajaxUrl + separator + 'ajax=1&action=getEntityAuditPanel&entity_type='
            + encodeURIComponent(config.entityType) + '&id_entity=' + entityId;

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
                        panelBody.innerHTML = response.data.html || '';
                    } else {
                        panelBody.innerHTML = '<div class="seoo-fa-loading"><p>' + (response.message || 'Error') + '</p></div>';
                    }
                } catch (e) {
                    panelBody.innerHTML = '<div class="seoo-fa-loading"><p>Parse error</p></div>';
                }
            } else {
                panelBody.innerHTML = '<div class="seoo-fa-loading"><p>HTTP ' + xhr.status + '</p></div>';
            }
        };
        xhr.send();
    }
})();
