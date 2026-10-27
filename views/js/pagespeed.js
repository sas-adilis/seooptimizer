/**
 * PageSpeed tab: runs the Lighthouse analysis page by page (one AJAX call per page)
 * and shows the details of each analyzed page.
 */
(function($) {
    'use strict';

    var STEP_TIMEOUT = 180000;

    function call(firstProcess) {
        return $.ajax({
            type: 'POST',
            url: window.SeoOptimizerAjaxUrl,
            dataType: 'json',
            timeout: STEP_TIMEOUT,
            data: {
                ajax: 1,
                action: 'pageSpeedRun',
                first_process: firstProcess ? 'true' : 'false'
            }
        });
    }

    function showError(message) {
        $('#seoo-psi-error').text(message).prop('hidden', false);
    }

    function updateProgress(progress) {
        var percentage = progress.total > 0 ? Math.round(progress.processed / progress.total * 100) : 0;
        $('#seoo-psi-bar').css('width', percentage + '%').text(percentage + '%');
        $('#seoo-psi-status').text(progress.processed + ' / ' + progress.total);
    }

    function finish() {
        var url = new URL(window.location.href);
        url.searchParams.set('show_tab', 'tab-page-speed');
        window.location.href = url.toString();
    }

    function step() {
        call(false).done(function(result) {
            if (result.progress) {
                updateProgress(result.progress);
            }
            if (result.status === 'success') {
                step();
            } else if (result.status === 'done') {
                finish();
            } else {
                showError(result.message || 'Error');
            }
        }).fail(function() {
            showError('The analysis was interrupted. Click the button again to resume it.');
            $('#seoo-psi-run').prop('disabled', false);
        });
    }

    function start() {
        var $button = $('#seoo-psi-run');
        var firstProcess = String($button.data('first-process')) === '1';

        $button.prop('disabled', true);
        $('#seoo-psi-error').prop('hidden', true);
        $('#seoo-psi-progress').prop('hidden', false);

        if (!firstProcess) {
            step();
            return;
        }

        call(true).done(function(result) {
            if (result.status === 'success') {
                updateProgress(result.progress);
                step();
            } else {
                showError(result.message || 'Error');
                $button.prop('disabled', false);
            }
        }).fail(function() {
            showError('Unable to start the analysis.');
            $button.prop('disabled', false);
        });
    }

    $(document).on('click', '#seoo-psi-run', start);

    $(document).on('click', '#seoo-psi .seoo-psi-toggle', function() {
        var $details = $(this).closest('tr').next('.seoo-psi-details');
        $details.prop('hidden', !$details.prop('hidden'));
    });
})(jQuery);
