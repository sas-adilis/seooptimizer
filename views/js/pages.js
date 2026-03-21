/**
 * SEO Optimizer — Pages
 *
 * Pages panel: search, severity filter, row expand/collapse,
 * and single-page re-audit.
 */
(function($) {

    /* ---------------------------------------------------------------
     * Filter logic
     * --------------------------------------------------------------- */
    function filterPagesTable() {
        var search = ($('#seoo-pages-search').val() || '').toLowerCase();
        var severity = $('#seoo-pages-severity-filter').val();

        $('#seoo-pages-table tbody .seoo-pages__row').each(function() {
            var $row = $(this);
            var url = ($row.data('url') || '').toLowerCase();
            var critical = parseInt($row.data('critical')) || 0;
            var warning = parseInt($row.data('warning')) || 0;
            var total = parseInt($row.data('total')) || 0;

            var matchSearch = !search || url.indexOf(search) !== -1;
            var matchSeverity = true;

            if (severity === 'critical') {
                matchSeverity = critical > 0;
            } else if (severity === 'warning') {
                matchSeverity = warning > 0;
            } else if (severity === 'clean') {
                matchSeverity = total === 0;
            }

            var show = matchSearch && matchSeverity;
            $row.toggle(show);

            var $detail = $('tr[data-detail-for="' + $row.data('url') + '"]');
            if (!show) {
                $detail.hide();
                $row.removeClass('seoo-pages__row--expanded');
            }
        });
    }

    /* ---------------------------------------------------------------
     * Pages panel initialization
     * --------------------------------------------------------------- */
    function initPagesPanel() {
        // Expand/collapse rows
        $(document).on('click', '.seoo-pages__row', function(e) {
            if ($(e.target).closest('.seoo-pages__reaudit-btn').length) {
                return;
            }
            if ($(e.target).closest('a').length) {
                return;
            }

            var $row = $(this);
            var url = $row.data('url');
            var $detail = $('tr[data-detail-for="' + url + '"]');

            if (!$detail.length) {
                return;
            }

            var isExpanded = $row.hasClass('seoo-pages__row--expanded');
            $row.toggleClass('seoo-pages__row--expanded', !isExpanded);
            $detail.toggle(!isExpanded);
        });

        // URL filter
        $(document).on('input', '#seoo-pages-search', function() {
            filterPagesTable();
        });

        // Severity filter
        $(document).on('change', '#seoo-pages-severity-filter', function() {
            filterPagesTable();
        });

        // Re-audit single page
        $(document).on('click', '.seoo-pages__reaudit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var url = $btn.data('url');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading');

            $.ajax({
                type: 'POST',
                url: window.SeoOptimizerAjaxUrl,
                data: {
                    ajax: 1,
                    action: 'reauditPage',
                    url: url
                },
                dataType: 'json',
                success: function(result) {
                    $btn.removeClass('loading');

                    if (result.status === 'success' && result.page) {
                        var page = result.page;
                        var $row = $btn.closest('.seoo-pages__row');

                        // Update badge counts
                        var cells = $row.find('td');
                        $(cells[2]).html(page.critical > 0
                            ? '<span class="seoo-pages__badge seoo-pages__badge--critical">' + page.critical + '</span>'
                            : '<span class="seoo-pages__badge seoo-pages__badge--none">0</span>');
                        $(cells[3]).html(page.warning > 0
                            ? '<span class="seoo-pages__badge seoo-pages__badge--warning">' + page.warning + '</span>'
                            : '<span class="seoo-pages__badge seoo-pages__badge--none">0</span>');
                        $(cells[4]).html(page.total > 0
                            ? '<strong>' + page.total + '</strong>'
                            : '<span style="color:#16a34a;">0</span>');

                        $row.data('critical', page.critical);
                        $row.data('warning', page.warning);
                        $row.data('total', page.total);

                        // Update detail row
                        var $detail = $('tr[data-detail-for="' + url + '"]');
                        if (page.issues && page.issues.length > 0) {
                            var html = '';
                            $.each(page.issues, function(index, issue) {
                                html += '<div class="seoo-pages__issue seoo-pages__issue--' + issue.severity + '">'
                                    + '<span class="seoo-pages__issue-severity"><span class="seoo-audit__severity-dot seoo-audit__severity-dot--' + issue.severity + '"></span></span>'
                                    + '<span class="seoo-pages__issue-audit"><i class="' + issue.audit_icon + '"></i> ' + issue.audit + '</span>'
                                    + '<span class="seoo-pages__issue-message">' + issue.message + '</span>'
                                    + '</div>';
                            });

                            if ($detail.length) {
                                $detail.find('.seoo-pages__issues').html(html);
                            } else {
                                $row.after('<tr class="seoo-pages__detail-row" data-detail-for="' + url + '" style="display:none;"><td colspan="6"><div class="seoo-pages__issues">' + html + '</div></td></tr>');
                            }

                            // Show chevron if not already there
                            if (!$row.find('.seoo-pages__chevron').length) {
                                $row.find('.seoo-pages__expand-cell').html('<i class="icon-chevron-right seoo-pages__chevron"></i>');
                            }
                        } else {
                            if ($detail.length) {
                                $detail.remove();
                            }
                            $row.find('.seoo-pages__expand-cell').html('');
                            $row.removeClass('seoo-pages__row--expanded');
                        }

                        // Flash green
                        $row.css('background', '#dcfce7');
                        setTimeout(function() {
                            $row.css('background', '');
                        }, 1500);
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                }
            });
        });
    }

    /* ---------------------------------------------------------------
     * Document ready
     * --------------------------------------------------------------- */
    $(document).ready(function() {
        initPagesPanel();
    });

})(jQuery);
