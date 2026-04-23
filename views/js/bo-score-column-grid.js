/**
 * SEO Optimizer — Transform plain-text SEO grade cells in Symfony grids
 * into colored circle badges with batch audit and click-to-panel.
 *
 * Works with the product listing grid (PS 1.7.5+).
 * The DataColumn outputs the grade as text ("A+", "B", "F", "-").
 * This script:
 *   1. Wraps grades in colored badge elements
 *   2. Shows spinner for unscored ("-") entities
 *   3. Audits them in batches of 10
 *   4. Makes badges clickable to open the audit side panel
 */
(function () {
    if (typeof SeoOptimizerScoreColumn === 'undefined') {
        return;
    }

    var config = SeoOptimizerScoreColumn;
    var BATCH_SIZE = 10;

    var gradeColors = {
        'A+': 'excellent',
        'A': 'good',
        'B': 'fair',
        'C': 'warning',
        'D': 'poor',
        'E': 'critical',
        'F': 'critical',
        '-': 'gray'
    };

    /**
     * Extract entity ID from a grid row.
     * Symfony grids have a checkbox or data attribute with the entity ID.
     */
    function getEntityIdFromRow(row, colIndex) {
        // Try checkbox first
        var checkbox = row.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.value) {
            var val = parseInt(checkbox.value, 10);
            if (val > 0) {
                return val;
            }
        }

        // Try first cell (usually the ID column)
        var firstCell = row.querySelector('td:first-child');
        if (firstCell) {
            var val2 = parseInt(firstCell.textContent.trim(), 10);
            if (val2 > 0) {
                return val2;
            }
        }

        // Try data-id
        if (row.dataset && row.dataset.id) {
            return parseInt(row.dataset.id, 10);
        }

        return 0;
    }

    function processGrid() {
        // Find the SEO column index by header text
        var headers = document.querySelectorAll('#product_catalog_list th, .js-grid-table th, table.table th');
        var colIndex = -1;

        for (var i = 0; i < headers.length; i++) {
            if (headers[i].textContent.trim() === 'SEO') {
                colIndex = i;
                break;
            }
        }

        if (colIndex === -1) {
            return;
        }

        // Find all table rows
        var rows = document.querySelectorAll('#product_catalog_list tbody tr, .js-grid-table tbody tr, table.table tbody tr');
        var unscoredItems = [];

        rows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length <= colIndex) {
                return;
            }

            var cell = cells[colIndex];
            var grade = cell.textContent.trim();

            // Skip already processed cells
            if (cell.querySelector('.seoo-grade-badge')) {
                return;
            }

            var entityId = getEntityIdFromRow(row, colIndex);

            if (!grade || grade === '-') {
                // Unscored — show spinner
                cell.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray seoo-grade-badge--pending">'
                    + '<span class="seoo-grade-spinner"></span></span>';
                cell.style.textAlign = 'center';
                if (entityId > 0) {
                    unscoredItems.push({id: entityId, cell: cell});
                }
            } else {
                // Has a score — render clickable badge
                var color = gradeColors[grade] || 'gray';
                renderClickableBadge(cell, entityId, grade, color);
            }
        });

        // Inject panel and launch batch audits
        if (unscoredItems.length > 0) {
            injectSidePanel();
            processBatchAudit(unscoredItems, 0);
        } else {
            injectSidePanel();
        }
    }

    /**
     * Render a clickable scored badge.
     */
    function renderClickableBadge(cell, entityId, grade, color) {
        var badge = document.createElement('a');
        badge.href = '#';
        badge.className = 'seoo-grade-badge seoo-grade-badge--' + color;
        badge.title = 'SEO: ' + grade;
        badge.textContent = grade;
        if (entityId > 0) {
            badge.addEventListener('click', function (e) {
                e.preventDefault();
                openAuditPanel(entityId);
            });
        }
        cell.innerHTML = '';
        cell.appendChild(badge);
        cell.style.textAlign = 'center';
    }

    /**
     * Process batch audits, BATCH_SIZE at a time.
     */
    function processBatchAudit(unscoredItems, offset) {
        var batch = unscoredItems.slice(offset, offset + BATCH_SIZE);
        if (batch.length === 0) {
            return;
        }

        var batchIds = batch.map(function (item) { return item.id; });
        var separator = config.ajaxUrl.indexOf('?') !== -1 ? '&' : '?';
        var url = config.ajaxUrl + separator + 'ajax=1&action=batchEntityAudit&entity_type='
            + encodeURIComponent(config.entityType) + '&ids=' + batchIds.join(',');

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
                        batch.forEach(function (item) {
                            if (response.data[item.id]) {
                                var scoreData = response.data[item.id];
                                renderClickableBadge(item.cell, item.id, scoreData.grade, scoreData.color);
                            }
                        });
                    }
                } catch (e) {
                    // Continue
                }
            }

            // Mark remaining failed ones as gray "-"
            batch.forEach(function (item) {
                if (item.cell.querySelector('.seoo-grade-badge--pending')) {
                    item.cell.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray" title="Audit failed">-</span>';
                }
            });

            // Next batch
            processBatchAudit(unscoredItems, offset + BATCH_SIZE);
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

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(processGrid, 500);
        });
    } else {
        setTimeout(processGrid, 500);
    }

    // Re-process after AJAX grid reloads (pagination, sorting, filtering)
    document.addEventListener('click', function () {
        setTimeout(processGrid, 800);
    });
})();
