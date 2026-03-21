/**
 * SEO Optimizer — Audit
 *
 * Audit start/pause/resume, batch processing, full audit,
 * CSV export, and audit UI rendering.
 */
(function($) {

    /* ---------------------------------------------------------------
     * Audit state
     * --------------------------------------------------------------- */
    var auditPaused = {};

    /* ---------------------------------------------------------------
     * Audit UI helpers
     * --------------------------------------------------------------- */
    function startAuditUI($audit, $btn) {
        var auditKey = $btn.data('audit-key');
        auditPaused[auditKey] = false;

        $btn.prop('disabled', true).text('Crawling...');
        $audit.find('.seoo-audit__progress-table').show();
        $audit.find('.seoo-audit__results').hide();
        $audit.find('.seoo-audit__pause-btn').show();
        $audit.find('.seoo-audit__resume-btn').hide();
        $audit.find('.seoo-audit__csv-btn').hide();
    }

    function auditErrorUI($audit, $btn, httpStatus) {
        var auditKey = $btn.data('audit-key');
        auditPaused[auditKey] = true;

        $audit.find('.seoo-audit__pause-btn').hide();
        $btn.prop('disabled', false).html('<i class="icon-play"></i> Resume');

        var msg = 'Audit interrupted';
        if (httpStatus) {
            msg += ' (HTTP ' + httpStatus + ')';
        }
        var $table = $audit.find('.seoo-audit__progress-table');
        if (!$table.find('.seoo-audit__error-msg').length) {
            $table.append('<div class="seoo-audit__error-msg alert alert-warning">' + msg + '</div>');
        }
    }

    /* ---------------------------------------------------------------
     * Render / update audit item rows
     * --------------------------------------------------------------- */
    function renderAuditItems(items, $container) {
        var $table = $container.find('.seoo-audit__progress-table, .seoo-report__table').first();

        $.each(items, function(typeKey, item) {
            var $row = $table.find('[data-audit-item="' + typeKey + '"]');

            if (!$row.length) {
                var barClass = item.percentage === 100
                    ? 'bg-success'
                    : item.percentage > 0
                        ? 'bg-processing progress-bar-striped progress-bar-animated'
                        : '';
                var statusText = item.status === 'done'
                    ? 'Done'
                    : item.status === 'processing'
                        ? 'In progress'
                        : 'Waiting';
                var badgeClass = item.issues_count > 0
                    ? 'seoo-report__badge--danger'
                    : 'seoo-report__badge--success';

                var html = '<div class="seoo-report__row" data-audit-item="' + typeKey + '">'
                    + '<div class="seoo-report__cell seoo-report__cell--entity">'
                    + '<span class="seoo-report__icon"><i class="' + item.icon + '"></i></span>'
                    + '<span class="seoo-report__entity-info">'
                    + '<strong class="seoo-report__entity-name">' + item.label + '</strong>'
                    + '<span class="seoo-report__entity-count">' + item.total + ' pages</span>'
                    + '</span></div>'
                    + '<div class="seoo-report__cell seoo-report__cell--progress">'
                    + '<div class="seoo-report__bar-wrap"><div class="progress report__progress-percentage">'
                    + '<div class="progress-bar ' + barClass + '" role="progressbar" aria-valuenow="' + item.percentage + '" aria-valuemin="0" aria-valuemax="100" style="width:' + item.percentage + '%"></div>'
                    + '</div></div>'
                    + '<div class="seoo-report__status-line">'
                    + '<span class="seoo-report__status-label">' + statusText + '</span>'
                    + '<span class="seoo-report__progress-value">' + item.crawled + ' / ' + item.total + '</span>'
                    + '</div></div>'
                    + '<div class="seoo-report__cell seoo-report__cell--result">'
                    + '<span class="seoo-report__badge ' + badgeClass + '">' + item.issues_count + '</span>'
                    + '</div></div>';

                $table.append(html);
            } else {
                var $bar = $row.find('.progress-bar');
                $bar.css('width', item.percentage + '%').attr('aria-valuenow', item.percentage);

                if (item.status === 'done') {
                    $bar.removeClass('bg-processing progress-bar-striped progress-bar-animated').addClass('bg-success');
                } else if (item.status === 'processing') {
                    $bar.addClass('bg-processing progress-bar-striped progress-bar-animated').removeClass('bg-success');
                }

                var $statusLabel = $row.find('.seoo-report__status-label');
                if (item.status === 'done') {
                    $statusLabel.text('Done');
                } else if (item.status === 'processing') {
                    $statusLabel.text('In progress');
                }

                $row.find('.seoo-report__progress-value').text(item.crawled + ' / ' + item.total);

                var $badge = $row.find('.seoo-report__badge');
                $badge.text(item.issues_count);
                $badge.toggleClass('seoo-report__badge--danger', item.issues_count > 0);
                $badge.toggleClass('seoo-report__badge--success', item.issues_count === 0);
            }
        });
    }

    /* ---------------------------------------------------------------
     * Audit batch runner
     * --------------------------------------------------------------- */
    function runAuditBatch(action, firstProcess, $audit, $btn) {
        var auditKey = $btn.data('audit-key');

        if (auditPaused[auditKey]) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: window.SeoOptimizerAjaxUrl,
            data: {
                ajax: 1,
                action: action,
                first_process: firstProcess
            },
            dataType: 'json',
            success: function(result) {
                if (!result || !result.audit) {
                    auditErrorUI($audit, $btn);
                    return;
                }

                var audit = result.audit;

                // KPIs live update
                if (audit.kpis) {
                    $.each(audit.kpis, function(i, kpi) {
                        var $kpiValue = $audit.find('[data-audit-kpi="' + kpi.key + '"]');
                        if ($kpiValue.length) {
                            $kpiValue.text(kpi.value);
                            $kpiValue.closest('.seoo-report__kpi')
                                .toggleClass('seoo-report__kpi--danger', !!kpi.danger)
                                .toggleClass('seoo-report__kpi--warning', !!kpi.warning);
                        }
                    });
                }

                // Render/update per-type rows
                if (audit.items) {
                    renderAuditItems(audit.items, $audit);
                }

                if (auditPaused[auditKey]) {
                    return;
                }

                if (result.status === 'success') {
                    setTimeout(function() {
                        runAuditBatch(action, false, $audit, $btn);
                    }, 100);
                } else if (result.status === 'done') {
                    document.location.reload();
                }
            },
            error: function(xhr) {
                auditErrorUI($audit, $btn, xhr.status);
            }
        });
    }

    /* ---------------------------------------------------------------
     * Audit button handlers
     * --------------------------------------------------------------- */
    function initAuditButtons() {
        // Start or resume audit
        $(document).on('click', '.seoo-audit__start-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var action = $btn.data('audit-action');
            var $audit = $btn.closest('.seoo-audit');
            var auditKey = $btn.data('audit-key');
            var isResume = auditPaused[auditKey] === true;

            startAuditUI($audit, $btn);

            if (isResume) {
                runAuditBatch(action, false, $audit, $btn);
            } else {
                $audit.find('.seoo-audit__progress-table .seoo-report__row').remove();
                $audit.find('.seoo-audit__error-msg').remove();
                runAuditBatch(action, true, $audit, $btn);
            }
        });

        // Resume interrupted audit
        $(document).on('click', '.seoo-audit__resume-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var action = $btn.data('audit-action');
            var $audit = $btn.closest('.seoo-audit');
            var $startBtn = $audit.find('.seoo-audit__start-btn');

            startAuditUI($audit, $startBtn);
            $btn.hide();
            runAuditBatch(action, false, $audit, $startBtn);
        });

        // Pause audit
        $(document).on('click', '.seoo-audit__pause-btn', function(e) {
            e.preventDefault();
            var auditKey = $(this).data('audit-key');
            auditPaused[auditKey] = true;
            $(this).hide();

            var $audit = $(this).closest('.seoo-audit');
            var $startBtn = $audit.find('.seoo-audit__start-btn');
            $startBtn.prop('disabled', false).html('<i class="icon-play"></i> Resume');
        });
    }

    /* ---------------------------------------------------------------
     * CSV export buttons
     * --------------------------------------------------------------- */
    function initAuditCsvButtons() {
        $(document).on('click', '.seoo-audit__csv-btn', function(e) {
            e.preventDefault();
            var action = $(this).data('audit-action');
            window.location.href = window.SeoOptimizerAjaxUrl + '&ajax=1&action=' + action;
        });
    }

    /* ---------------------------------------------------------------
     * Full audit
     * --------------------------------------------------------------- */
    function initFullAudit() {
        $(document).on('click', '#seoo-full-audit-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $panel = $('#seoo-full-audit');
            var $itemsContainer = $('#seoo-full-audit-items');

            if ($btn.hasClass('loading')) {
                return;
            }

            $btn.addClass('loading').prop('disabled', true).html('<i class="icon-refresh" style="animation:seoo-spin 1s linear infinite"></i> Crawling...');
            $panel.show();
            $itemsContainer.html('');

            runFullAuditBatch(true, $btn, $panel);
        });

        function runFullAuditBatch(firstProcess, $btn, $panel) {
            $.ajax({
                type: 'POST',
                url: window.SeoOptimizerAjaxUrl,
                data: {
                    ajax: 1,
                    action: 'runFullAudit',
                    first_process: firstProcess
                },
                dataType: 'json',
                success: function(result) {
                    if (!result || !result.audit) {
                        resetBtn($btn);
                        return;
                    }

                    var audit = result.audit;

                    if (audit.items) {
                        renderAuditItems(audit.items, $panel);
                    }

                    if (result.status === 'success') {
                        setTimeout(function() {
                            runFullAuditBatch(false, $btn, $panel);
                        }, 100);
                    } else if (result.status === 'done') {
                        resetBtn($btn);
                        setTimeout(function() {
                            document.location.reload();
                        }, 1000);
                    }
                },
                error: function() {
                    resetBtn($btn);
                }
            });
        }

        function resetBtn($btn) {
            $btn.removeClass('loading').prop('disabled', false).html('<i class="process-icon-search"></i> Start full audit');
        }
    }

    /* ---------------------------------------------------------------
     * Document ready
     * --------------------------------------------------------------- */
    $(document).ready(function() {
        initAuditButtons();
        initAuditCsvButtons();
        initFullAudit();
    });

})(jQuery);
