/**
 * SEO Optimizer — Inject SEO score column into legacy BO listings.
 *
 * Works with AdminCategories, AdminManufacturers, AdminSuppliers, AdminCmsContent.
 * Reads entity IDs from the table checkboxes, fetches scores via AJAX,
 * and injects a "SEO" column with colored grade badges.
 */
(function () {
    if (typeof SeoOptimizerScoreColumn === 'undefined') {
        return;
    }

    var config = SeoOptimizerScoreColumn;

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

        // Insert placeholder cells in each row
        entityRows.forEach(function (item) {
            var td = document.createElement('td');
            td.className = 'text-center seoo-score-cell';
            td.setAttribute('data-entity-id', item.id);
            td.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray">...</span>';
            var lastTd = item.row.lastElementChild;
            item.row.insertBefore(td, lastTd);
        });

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

                entityRows.forEach(function (item) {
                    var td = item.row.querySelector('.seoo-score-cell');
                    if (!td) {
                        return;
                    }

                    var score = scores[item.id];
                    if (score && score.grade) {
                        td.innerHTML = '<a href="' + config.moduleUrl + '" class="seoo-grade-badge seoo-grade-badge--'
                            + score.color + '" title="Score: ' + Math.round(score.score) + '/100">'
                            + score.grade + '</a>';
                    } else {
                        td.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--gray" title="No audit data">-</span>';
                    }
                });
            } catch (e) {
                // Silently fail
            }
        };
        xhr.send();
    });
})();
