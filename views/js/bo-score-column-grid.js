/**
 * SEO Optimizer — Transform plain-text SEO grade cells in Symfony grids
 * into colored circle badges.
 *
 * Works with the product listing grid (PS 1.7.5+).
 * The DataColumn outputs the grade as text ("A+", "B", "F", "-").
 * This script finds those cells and wraps them in badge elements.
 */
(function () {
    var gradeColors = {
        'A+': 'excellent',
        'A': 'good',
        'B': 'good',
        'C': 'fair',
        'D': 'warning',
        'E': 'poor',
        'F': 'critical',
        '-': 'gray'
    };

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

        // Find all table bodies (handle both legacy and Symfony grids)
        var rows = document.querySelectorAll('#product_catalog_list tbody tr, .js-grid-table tbody tr, table.table tbody tr');

        rows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length <= colIndex) {
                return;
            }

            var cell = cells[colIndex];
            var grade = cell.textContent.trim();

            if (!grade || cell.querySelector('.seoo-grade-badge')) {
                return;
            }

            var color = gradeColors[grade] || 'gray';
            cell.innerHTML = '<span class="seoo-grade-badge seoo-grade-badge--' + color + '" title="SEO: ' + grade + '">' + grade + '</span>';
            cell.style.textAlign = 'center';
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // Delay slightly to let the grid render
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
