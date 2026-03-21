/**
 * SEO Optimizer — Core
 *
 * Form field visibility toggles, chart initialization, tab navigation,
 * copy buttons, generic AJAX handlers, and shared utilities.
 */
(function($) {

    /* ---------------------------------------------------------------
     * Global variables (shared across files via window)
     * --------------------------------------------------------------- */
    window.isAjaxRunning = false;
    window.SeoOptimizerAjaxUrl = window.SeoOptimizerAjaxUrl || '';

    var process = null;
    var maxProcess = 9999;
    var shouldStopRunningAjax = false;

    /* ---------------------------------------------------------------
     * Form field visibility toggles
     * --------------------------------------------------------------- */
    function initIndexationUrlsFields() {
        var fields = [
            'SEOO_SUPPLIER_PAGE_REDIRECTION',
            'SEOO_MANUFACTURER_PAGE_REDIRECTION',
            'SEOO_STORE_PAGE_REDIRECTION',
            'SEOO_SITEMAP_PAGE_REDIRECTION'
        ];

        $.each(fields, function(index, field) {
            var $field = $('#' + field);
            var fieldIndexation = field.replace('_REDIRECTION', '_INDEXATION');
            var $fieldIndexation = $('input[name=' + fieldIndexation + ']');

            if ($fieldIndexation.length > 0) {
                $fieldIndexation.on('change', function() {
                    var value = parseInt($fieldIndexation.filter(':checked').val()) || 0;
                    if (value === 3 || value === 4) {
                        $field.prop('disabled', false);
                        $field.closest('.form-group').show();
                    } else {
                        $field.val('');
                        $field.prop('disabled', 'disabled');
                        $field.closest('.form-group').hide();
                    }
                });

                $fieldIndexation.first().trigger('change');
            }
        });
    }

    function initCanonicalUrlsFields() {
        var $fields = $('.show-if-enable-canonical-urls');
        var $fieldEnableCanonicalUrls = $('input[name=SEOO_ENABLE_CANONICAL_URLS]');

        if ($fieldEnableCanonicalUrls.length > 0) {
            $fieldEnableCanonicalUrls.on('change', function() {
                var value = parseInt($fieldEnableCanonicalUrls.filter(':checked').val()) || 0;
                $fields.toggle(value === 1);
            });

            $fieldEnableCanonicalUrls.first().trigger('change');
        }
    }

    function initSitemapFields() {
        var fieldMappings = [
            { fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES]', targetSelector: '.show-if-product-images' },
            { fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES]', targetSelector: '.show-if-category-images' },
            { fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES]', targetSelector: '.show-if-manufacturer-images' },
            { fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES]', targetSelector: '.show-if-supplier-images' },
            { fieldSelector: 'input[name=SEOO_ENABLE_SITEMAP]', targetSelector: '.show-if-enable-sitemap' }
        ];

        $.each(fieldMappings, function(index, mapping) {
            var $field = $(mapping.fieldSelector);
            var $targetFields = $(mapping.targetSelector);

            if ($field.length > 0) {
                $field.on('change', function() {
                    var value = parseInt($field.filter(':checked').val()) || 0;
                    $targetFields.toggle(value === 1);
                });

                $field.first().trigger('change');
            }
        });
    }

    function initRichSnippetsFields() {
        var $fields = $('.show-if-enable-rs');
        var isEnableRichSnippet = false;
        var isEnableReturnPolicy = false;
        var isEnableAdditionalProperty = false;
        var firstRun = true;

        var $fieldEnableRichSnippet = $('input[name=SEOO_ENABLE_RS]');
        var $fieldEnableReturnPolicy = $('input[name=SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY]');
        var $fieldEnableAdditionalProperty = $('input[name=SEOO_ENABLE_RS_ADDITIONAL_PROPERTY]');

        if ($fieldEnableReturnPolicy.length > 0) {
            $fieldEnableReturnPolicy.on('change', function() {
                var value = parseInt($fieldEnableReturnPolicy.filter(':checked').val()) || 0;
                isEnableReturnPolicy = value === 1;
                if (!firstRun) {
                    updateDisplay();
                }
            });
            $fieldEnableReturnPolicy.first().trigger('change');
        }

        if ($fieldEnableAdditionalProperty.length > 0) {
            $fieldEnableAdditionalProperty.on('change', function() {
                var value = parseInt($fieldEnableAdditionalProperty.filter(':checked').val()) || 0;
                isEnableAdditionalProperty = value === 1;
                if (!firstRun) {
                    updateDisplay();
                }
            });
            $fieldEnableAdditionalProperty.first().trigger('change');
        }

        if ($fieldEnableRichSnippet.length > 0) {
            $fieldEnableRichSnippet.on('change', function() {
                var value = parseInt($fieldEnableRichSnippet.filter(':checked').val()) || 0;
                isEnableRichSnippet = value === 1;
                if (!firstRun) {
                    updateDisplay();
                }
            });
            $fieldEnableRichSnippet.first().trigger('change');
        }

        function updateDisplay() {
            $.each($fields, function(index, field) {
                var $field = $(field);
                if ($field.hasClass('show-if-enable-rs-additional-property')) {
                    $field.toggle(isEnableRichSnippet && isEnableAdditionalProperty);
                } else if ($field.hasClass('show-if-enable-rs-merchant-return-policy')) {
                    $field.toggle(isEnableRichSnippet && isEnableReturnPolicy);
                } else {
                    $field.toggle(isEnableRichSnippet);
                }
            });
        }

        if (firstRun) {
            updateDisplay();
            firstRun = false;
        }
    }

    function initMissingImageLegendFix() {
        var $fields = $('.show-if-fix-image-legend-enable');
        var $fieldFixImageLegendMethod = $('select[name=SEOO_FIX_IMAGE_LEGEND_METHOD]');
        var $fieldEnableFixImageLegend = $('input[name=SEOO_FIX_IMAGE_LEGEND_ENABLE]');
        var firstRun = true;

        if ($fieldFixImageLegendMethod.length > 0) {
            $fieldFixImageLegendMethod.on('change', function() {
                if (!firstRun) {
                    updateDisplay();
                }
            });
            $fieldFixImageLegendMethod.trigger('change');
        }

        if ($fieldEnableFixImageLegend.length > 0) {
            $fieldEnableFixImageLegend.on('change', function() {
                if (!firstRun) {
                    updateDisplay();
                }
            });
            $fieldEnableFixImageLegend.trigger('change');
        }

        function updateDisplay() {
            $.each($fields, function(index, field) {
                var $field = $(field);
                var isEnabledFixImageLegend = parseInt($fieldEnableFixImageLegend.filter(':checked').val()) === 1;
                var fixImageLegendMethod = $fieldFixImageLegendMethod.val();
                if ($field.hasClass('show-if-fix-image-legend-method-text')) {
                    $field.toggle(isEnabledFixImageLegend && fixImageLegendMethod === 'text');
                } else if ($field.hasClass('show-if-fix-image-legend-method-ia')) {
                    $field.toggle(isEnabledFixImageLegend && fixImageLegendMethod === 'ia');
                } else {
                    $field.toggle(isEnabledFixImageLegend);
                }
            });
        }

        if (firstRun) {
            updateDisplay();
            firstRun = false;
        }
    }

    function initTagsFields() {
        $('.tags .label-tooltip').on('click', function() {
            var $input = $(this).closest('.form-group').find('input');
            var value = $(this).html().trim();
            $input.focus();
            if (typeof $input[0].selectionStart === 'number') {
                var startPos = $input[0].selectionStart;
                var endPos = $input[0].selectionEnd;
                var currentValue = $input.val();
                var newValue = currentValue.substring(0, startPos) + value + currentValue.substring(endPos);
                $input.val(newValue);
                $input[0].selectionStart = $input[0].selectionEnd = startPos + value.length;
            } else {
                $input.val($input.val() + value);
            }
        });
    }

    /* ---------------------------------------------------------------
     * Abort / stop handlers
     * --------------------------------------------------------------- */
    function initAbortConfirmMessages() {
        $('button.process-icon-stop').on('click', function(e) {
            e.preventDefault();
            if ($(this).closest('form').hasClass('loading')) {
                if (confirm('Are you sure you want to stop the process?')) {
                    shouldStopRunningAjax = true;
                }
            }
        });
    }

    /* ---------------------------------------------------------------
     * Copy buttons
     * --------------------------------------------------------------- */
    function initCopyButtons() {
        $(document).on('click', '.seoo-copy-btn', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url);
            } else {
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
            }
            var $btn = $(this);
            $btn.find('i').removeClass('icon-copy').addClass('icon-check');
            setTimeout(function() {
                $btn.find('i').removeClass('icon-check').addClass('icon-copy');
            }, 1500);
        });
    }

    /* ---------------------------------------------------------------
     * Chart initialization
     * --------------------------------------------------------------- */
    function initCharts() {
        $('canvas.chart-bar').each(function(index, element) {
            $.ajax({
                type: 'POST',
                url: window.SeoOptimizerAjaxUrl,
                async: true,
                data: {
                    ajax: 1,
                    action: 'getChart',
                    chart: element.getAttribute('data-chart')
                },
                dataType: 'json',
                success: function(result) {
                    if (result.status === 'success') {
                        new Chart(element, {
                            type: 'bar',
                            data: result.data,
                            interaction: {
                                intersect: false
                            },
                            options: {}
                        });
                    }
                },
                error: function() {}
            });
        });
    }

    /* ---------------------------------------------------------------
     * Tab navigation
     * --------------------------------------------------------------- */
    function initTabNavigation() {
        var $navbarItems = $('.navbar [data-toggle="collapse"]');
        var $tabItems = $('#tabs > .collapse');

        $navbarItems.on('click', function(e) {
            var currentSelection = e.target.getAttribute('href');
            $navbarItems.each(function(index, element) {
                $(element).closest('li').toggleClass('active', element === e.target);
            });

            $tabItems.each(function(index, element) {
                var $this = $(element);
                if ($this.hasClass('in') && '#' + element.id !== currentSelection) {
                    $this.collapse('hide');
                }
            });

            localStorage.setItem('SeoOptimizercurrentSelection', currentSelection);
        });

        $('#tab-dashboard').on('shown.bs.collapse', function() {
            for (var id in Chart.instances) {
                Chart.instances[id].resize();
            }
        });

        var urlParams = new URLSearchParams(window.location.search);
        var showTabValue = urlParams.get('show_tab');
        if (showTabValue) {
            localStorage.setItem('SeoOptimizercurrentSelection', '#' + showTabValue);
        }

        var currentSelection = localStorage.getItem('SeoOptimizercurrentSelection') || '#tab-dashboard';
        if (currentSelection) {
            $('a[href="' + currentSelection + '"]').click();
        }
    }

    /* ---------------------------------------------------------------
     * Generic AJAX button handlers
     * --------------------------------------------------------------- */
    function initAjaxActionButtons() {
        $(document).on('click', 'button[data-ajax-action]', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $target;
            var targetSelector = $btn.data('ajax-target');

            if (targetSelector && $(targetSelector).length > 0) {
                $target = $(targetSelector);
            }

            $btn.addClass('loading');
            if ($target) {
                $target.addClass('loading');
            }

            $.ajax({
                type: 'POST',
                url: window.SeoOptimizerAjaxUrl,
                async: true,
                data: {
                    ajax: 1,
                    action: $btn.data('ajax-action')
                },
                dataType: 'html',
                success: function(result) {
                    if ($target) {
                        $target.html($(result).html());
                        $target.removeClass('loading');
                    }
                    $btn.removeClass('loading');
                },
                error: function() {}
            });
        });
    }

    /* ---------------------------------------------------------------
     * Generic AJAX process runner (used by .runAjaxProcess buttons)
     * --------------------------------------------------------------- */
    window.runAjaxProcess = function(inputSubmit, firstProcess) {
        maxProcess--;
        if (maxProcess <= 0) {
            return false;
        }

        if (process === 'getResume' && window.isAjaxRunning) {
            alert('TEXT TO DEFINE');
            return false;
        }

        window.isAjaxRunning = true;

        var $form = inputSubmit.closest('form');

        if (firstProcess) {
            $form.addClass('loading');

            var $progressBar = $form.find('.progress-bar');
            $progressBar.css('width', '0%');
            $progressBar.attr('aria-valuenow', 0);
            $progressBar.removeClass('bg-success');
            $progressBar.addClass('bg-info');
            $progressBar.html('0%');
            $form.find('.report__progress-value').html('--');
            $form.find('button.process-icon-search').prop('disabled', true);

            var $badge = $form.find('.report__result .badge');
            $badge.removeClass('badge-danger');
            $badge.addClass('badge-success');
            $badge.html('0');
        }

        var formId = $form.attr('id');
        var href = $form.prop('action');

        var datas = {
            ajax: 1,
            action: inputSubmit.attr('name'),
            first_process: firstProcess
        };

        $.ajax({
            type: 'POST',
            url: href,
            async: true,
            data: datas,
            dataType: 'json',
            success: function(result) {
                window.isAjaxRunning = false;

                if (result.status === 'success') {
                    $.each(result.report.items, function(key, element) {
                        var $elementRow = $form.find('#' + formId + '_' + key);
                        var $progressBar = $elementRow.find('.progress-bar');

                        $elementRow.find('.report__progress-value').html(element.progress);

                        $progressBar.css('width', element.percentage + '%');
                        $progressBar.attr('aria-valuenow', element.percentage);

                        if (element.status === 'done') {
                            $progressBar.removeClass('progress-bar-animated progress-bar-striped bg-processing');
                            $progressBar.addClass('bg-success');
                        } else if (element.status === 'processing') {
                            $progressBar.addClass('progress-bar-striped progress-bar-animated bg-processing');
                            $progressBar.removeClass('bg-success');
                        }

                        var $statusLabel = $elementRow.find('.seoo-report__status-label');
                        if (element.status === 'done') {
                            $statusLabel.text('Done');
                        } else if (element.status === 'processing') {
                            $statusLabel.text('In progress');
                        }

                        $elementRow.find('.report__fixed').html(element.fixed_count);

                        var $badge = $elementRow.find('.report__result .seoo-report__badge');
                        $badge.toggleClass('seoo-report__badge--success', element.results_count === 0);
                        $badge.toggleClass('seoo-report__badge--danger', element.results_count > 0);
                        $badge.html(element.results_count);
                    });

                    // Update KPI cards
                    var $report = $form.closest('.panel').find('.seoo-report');
                    if ($report.length) {
                        var doneEntities = 0;
                        var totalEntities = 0;
                        var totalErrors = 0;
                        var totalAnalyzed = 0;
                        $.each(result.report.items, function(key, element) {
                            totalEntities++;
                            totalAnalyzed += element.treated || 0;
                            totalErrors += element.results_count || 0;
                            if (element.status === 'done') {
                                doneEntities++;
                            }
                        });
                        $report.find('[data-kpi="entities"]').text(doneEntities + ' / ' + totalEntities);
                        $report.find('[data-kpi="analyzed"]').text(totalAnalyzed);
                        var $errorsKpi = $report.find('[data-kpi="errors"]');
                        $errorsKpi.text(totalErrors);
                        $errorsKpi.closest('.seoo-report__kpi').toggleClass('seoo-report__kpi--danger', totalErrors > 0);
                    }

                    if (!shouldStopRunningAjax) {
                        setTimeout(function() {
                            window.runAjaxProcess(inputSubmit, false);
                        }, 200);
                    } else {
                        document.location.reload();
                    }
                }

                if (result.status === 'done') {
                    document.location.reload();
                }
            },
            error: function() {}
        });
    };

    /* ---------------------------------------------------------------
     * Window beforeunload handler
     * --------------------------------------------------------------- */
    window.addEventListener('beforeunload', function(e) {
        if (window.isAjaxRunning !== false) {
            (e || window.event).returnValue = 'Are you sure you want to stop the process?';
            return 'Are you sure you want to stop the process?';
        }
    });

    /* ---------------------------------------------------------------
     * ESC key handler
     * --------------------------------------------------------------- */
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && confirm('Are you sure you want to stop the process?')) {
            shouldStopRunningAjax = true;
        }
    });

    /* ---------------------------------------------------------------
     * Document ready
     * --------------------------------------------------------------- */
    $(document).ready(function() {
        $(document).on('click', '.runAjaxProcess', function(e) {
            e.stopPropagation();
            window.runAjaxProcess($(e.target), true);
            return false;
        });

        initTabNavigation();
        initCharts();
        initIndexationUrlsFields();
        initRichSnippetsFields();
        initCanonicalUrlsFields();
        initAbortConfirmMessages();
        initTagsFields();
        initMissingImageLegendFix();
        initSitemapFields();
        initCopyButtons();
        initAjaxActionButtons();
    });

})(jQuery);
