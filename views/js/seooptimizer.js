let isAjaxRunning = false;
let process = null;
let maxProcess = 9999;
let shouldStopRunningAjax = false;

function initIndexationUrlsFields() {
    const fields = [
        'SEOO_SUPPLIER_PAGE_REDIRECTION',
        'SEOO_MANUFACTURER_PAGE_REDIRECTION',
        'SEOO_STORE_PAGE_REDIRECTION',
        'SEOO_SITEMAP_PAGE_REDIRECTION',
    ];

    fields.forEach((field) => {
        const $field = $(`#${field}`);
        const fieldIndexation = field.replace('_REDIRECTION', '_INDEXATION');
        const $fieldIndexation = $(`input[name=${fieldIndexation}]`);

        if ($fieldIndexation.length > 0) {
            $fieldIndexation.on('change', function () {
                const value = parseInt($fieldIndexation.filter(':checked').val()) || 0;
                if (value === 3 || value === 4) {
                    $field.prop('disabled', false);
                    $field.closest('.form-group').show();
                } else {
                    $field.value = '';
                    $field.prop('disabled', 'disabled');
                    $field.closest('.form-group').hide();
                }
            });

            $fieldIndexation.first().trigger('change');
        }
    });
}
function initCanonicalUrlsFields() {
    const $fields = $('.show-if-enable-canonical-urls');
    const $fieldEnableCanonicalUrls = $('input[name=SEOO_ENABLE_CANONICAL_URLS]');

    if ($fieldEnableCanonicalUrls.length > 0) {
        $fieldEnableCanonicalUrls.on('change', function () {
            const value = parseInt($fieldEnableCanonicalUrls.filter(':checked').val()) || 0;
            $fields.toggle(value === 1);
        });

        $fieldEnableCanonicalUrls.first().trigger('change');
    }
}
function initSitemapFields() {
    const fieldMappings = [
        {
            fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_PRODUCT_IMAGES]',
            targetSelector: '.show-if-product-images'
        },
        {
            fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_CATEGORY_IMAGES]',
            targetSelector: '.show-if-category-images'
        },
        {
            fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_MANUFACTURER_IMAGES]',
            targetSelector: '.show-if-manufacturer-images'
        },
        {
            fieldSelector: 'input[name=SEOO_SITEMAP_ENABLE_SUPPLIER_IMAGES]',
            targetSelector: '.show-if-supplier-images'
        },
        {
            fieldSelector: 'input[name=SEOO_ENABLE_SITEMAP]',
            targetSelector: '.show-if-enable-sitemap'
        }
    ];

    fieldMappings.forEach(({ fieldSelector, targetSelector }) => {
        const $field = $(fieldSelector);
        const $targetFields = $(targetSelector);

        if ($field.length > 0) {
            $field.on('change', function () {
                const value = parseInt($field.filter(':checked').val()) || 0;
                $targetFields.toggle(value === 1);
            });

            $field.first().trigger('change');
        }
    });
}
function initRichSnippetsFields() {
    const $fields = $('.show-if-enable-rs');

    let isEnableRichSnippet = false;
    let isEnableReturnPolicy = false;
    let isEnableAdditionalProperty = false;
    let firstRun = true;

    const $fieldEnableRichSnippet = $('input[name=SEOO_ENABLE_RS]');
    const $fieldEnableReturnPolicy = $('input[name=SEOO_ENABLE_RS_MERCHANT_RETURN_POLICY]');
    const $fieldEnableAdditionalProperty = $('input[name=SEOO_ENABLE_RS_ADDITIONAL_PROPERTY]');

    if ($fieldEnableReturnPolicy.length > 0) {
        $fieldEnableReturnPolicy.on('change', function () {
            const value = parseInt($fieldEnableReturnPolicy.filter(':checked').val()) || 0;
            isEnableReturnPolicy = value === 1;
            if (!firstRun) {
                updateDisplay();
            }
        });
        $fieldEnableReturnPolicy.first().trigger('change');
    }

    if ($fieldEnableAdditionalProperty.length > 0) {
        $fieldEnableAdditionalProperty.on('change', function () {
            const value = parseInt($fieldEnableAdditionalProperty.filter(':checked').val()) || 0;
            isEnableAdditionalProperty = value === 1;
            if (!firstRun) {
                updateDisplay();
            }
        });
        $fieldEnableAdditionalProperty.first().trigger('change');
    }

    if ($fieldEnableRichSnippet.length > 0) {
        $fieldEnableRichSnippet.on('change', function () {
            const value = parseInt($fieldEnableRichSnippet.filter(':checked').val()) || 0;
            isEnableRichSnippet = value === 1;
            if (!firstRun) {
                updateDisplay();
            }
        });

        $fieldEnableRichSnippet.first().trigger('change');
    }

    function updateDisplay() {
        $.each($fields, function(index, field) {
            const $field = $(field);
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
        console.log('passe', firstRun);

        updateDisplay();
        firstRun = false;
        console.log(5, firstRun);
    }
}
function initMissingImageLegendFix() {
    const $fields = $('.show-if-fix-image-legend-enable');
    const $fieldFixImageLegendMethod = $('select[name=SEOO_FIX_IMAGE_LEGEND_METHOD]');
    const $fieldEnableFixImageLegend = $('input[name=SEOO_FIX_IMAGE_LEGEND_ENABLE]');
    let firstRun = true;

    if ($fieldFixImageLegendMethod.length > 0) {
        $fieldFixImageLegendMethod.on('change', function () {
            if (!firstRun) {
                updateDisplay();
            }
        });
        $fieldFixImageLegendMethod.trigger('change');
    }

    if ($fieldEnableFixImageLegend.length > 0) {
        $fieldEnableFixImageLegend.on('change', function () {
            if (!firstRun) {
                updateDisplay();
            }
        });
        $fieldEnableFixImageLegend.trigger('change');
    }

    function updateDisplay() {
        $.each($fields, function(index, field) {
            const $field = $(field);
            const isEnabledFixImageLegend = parseInt($fieldEnableFixImageLegend.filter(':checked').val()) === 1;
            const fixImageLegendMethod = $fieldFixImageLegendMethod.val();
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
function initTagsFields() {
    $('.tags .label-tooltip').on('click', function () {
        const $input = $(this).closest('.form-group').find('input');
        const value = $(this).html().trim();
        $input.focus();
        if (typeof $input[0].selectionStart === "number") {
            const startPos = $input[0].selectionStart;
            const endPos = $input[0].selectionEnd;
            const currentValue = $input.val();
            const newValue = currentValue.substring(0, startPos) + value + currentValue.substring(endPos);
            $input.val(newValue);
            $input[0].selectionStart = $input[0].selectionEnd = startPos + value.length;
        } else {
            $input.val($input.val() + value);
        }
    });
}

window.addEventListener("beforeunload", function (e) {
    if( isAjaxRunning !== false ){
        (e || window.event).returnValue = 'Are you sure you want to stop the process?'; //Gecko + IE
        return 'Are you sure you want to stop the process?'; //Gecko + Webkit, Safari, Chrome etc.
    }
});

$(document).ready(() => {
    $('.runAjaxProcess').click((event)  => {
        event.stopPropagation();
        runAjaxProcess($(event.target), true);
        return false;
    });

    const $navbarItems = $('.navbar [data-toggle="collapse"]');
    const $tabItems = $('#tabs > .collapse');
    $navbarItems.on('click', function(e) {
        const currentSelection = e.target.getAttribute('href');
        $navbarItems.each((index, element) => {
           $(element).closest('li').toggleClass('active', element === e.target);
        });

        $tabItems.each((index, element) => {
            const $this = $(element);
            if ($this.hasClass('in') && `#${element.id}` !== currentSelection) {
                $this.collapse('hide');
            }
        });

        // save currentSelection in local storage



        localStorage.setItem('SeoOptimizercurrentSelection', currentSelection);
    });

    $('#tab-dashboard').on('shown.bs.collapse', function () {
        for (let id in Chart.instances) {
            console.log(Chart.instances);
            Chart.instances[id].resize();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const showTabValue = urlParams.get('show_tab');
    if (showTabValue) {
        localStorage.setItem('SeoOptimizercurrentSelection', `#${showTabValue}`);
    }

    // get currentSelection from local storage
    const currentSelection = localStorage.getItem('SeoOptimizercurrentSelection') || '#tab-dashboard';
    if (currentSelection) {
        $(`a[href="${currentSelection}"]`).click();
    }

    $('canvas.chart-bar').each((index, element) => {
        $.ajax({
            type:"POST",
            url : SeoOptimizerAjaxUrl,
            async: true,
            data : {
                ajax: 1,
                action: 'getChart',
                chart: element.getAttribute('data-chart')
            },
            dataType : 'json',
            success : function(result, textStatus, jqXHR)
            {
                if (result.status === 'success') {
                    new Chart(element, {
                        type: 'bar',
                        data: result.data,
                        interaction: {
                            intersect: false,
                        },
                        options: {
                        }
                    });
                }
            },
            error: function ( jqXHR, textStatus, errorThrown ) {
                /*document.location.href = href+'&error';*/
            }
        });
    });

    initIndexationUrlsFields();
    initRichSnippetsFields();
    initCanonicalUrlsFields();
    initAbortConfirmMessages();
    initTagsFields();
    initMissingImageLegendFix();
    initSitemapFields();
    initAuditButtons();
    initAuditCsvButtons();

    $('button[data-ajax-action]').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        let $target;
        let targetSelector = $btn.data('ajax-target');
        if (targetSelector) {
            if ($(targetSelector).length > 0) {
                $target = $(targetSelector);
            }
        }
        console.log(targetSelector);
        console.log($target);
        $btn.addClass('loading');
        if ($target) {
            $target.addClass('loading');
        }
        $.ajax({
            type:"POST",
            url : SeoOptimizerAjaxUrl,
            async: true,
            data : {
                'ajax': 1,
                'action': $(this).data('ajax-action'),
            },
            dataType : 'html',
            success : function(result, textStatus, jqXHR)
            {
                if ($target) {
                    $target.html($(result).html());
                    $target.removeClass('loading');
                }
                $btn.removeClass('loading');
            },
            error: function ( jqXHR, textStatus, errorThrown ) {
            }
        });

    });
});

// on click on the button ESC
$(document).keydown(function(e) {
    if (e.keyCode === 27 && confirm('Are you sure you want to stop the process?')) {
        shouldStopRunningAjax = true;
    }
});

function runAjaxProcess(input_submit, first_process) {
    maxProcess--;
    if (maxProcess <= 0) {
        return false;
    }

    if (process === 'getResume' && isAjaxRunning) {
        alert('TEXT TO DEFINE');
        return false;
    }

    isAjaxRunning = true;

    const $form = input_submit.closest('form');

    if (first_process) {
        $form.addClass('loading');

        const $progressBar = $form.find('.progress-bar');
        $progressBar.css('width', '0%');
        $progressBar.attr('aria-valuenow', 0);
        $progressBar.removeClass('bg-success');
        $progressBar.addClass('bg-info');
        $progressBar.html('0%');
        $form.find('.report__progress-value').html('--');
        $form.find('button.process-icon-search').prop('disabled', true);

        const $badge = $form.find('.report__result .badge');
        $badge.removeClass('badge-danger');
        $badge.addClass('badge-success');
        $badge.html('0');
    }

    const formId = $form.attr('id');
    const href = $form.prop('action');
    //const form_datas = $form.serializeArray();

    let datas = {
        ajax: 1,
        action: input_submit.attr('name'),
        first_process
    };

    $.ajax({
        type:"POST",
        url : href,
        async: true,
        data : datas,
        dataType : 'json',
        success : function(result, textStatus, jqXHR)
        {
            isAjaxRunning = false;

            if (result.status === 'success') {

                $.each(result.report.items, function(key, element) {
                    const $elementRow = $form.find(`#${formId}_${key}`);
                    const $progressBar = $elementRow.find('.progress-bar');

                    $elementRow.find('.report__progress-value').html(element.progress);

                    $progressBar.css('width', element.percentage + '%');
                    $progressBar.attr('aria-valuenow', element.percentage);

                    if (element.status === 'done') {
                        $progressBar.removeClass('progress-bar-animated progress-bar-striped bg-processing');
                        $progressBar.addClass('bg-success');
                    } else if(element.status === 'processing') {
                        $progressBar.addClass('progress-bar-striped progress-bar-animated bg-processing');
                        $progressBar.removeClass('bg-success');
                    }

                    // Update status label
                    const $statusLabel = $elementRow.find('.seoo-report__status-label');
                    if (element.status === 'done') {
                        $statusLabel.text('Done');
                    } else if (element.status === 'processing') {
                        $statusLabel.text('In progress');
                    }

                    $elementRow.find('.report__fixed').html(element.fixed_count);

                    const $badge = $elementRow.find('.report__result .seoo-report__badge');
                    $badge.toggleClass('seoo-report__badge--success', element.results_count === 0);
                    $badge.toggleClass('seoo-report__badge--danger', element.results_count > 0);
                    $badge.html(element.results_count);
                });

                // Update KPI cards
                const $report = $form.closest('.panel').find('.seoo-report');
                if ($report.length) {
                    let doneEntities = 0;
                    let totalEntities = 0;
                    let totalErrors = 0;
                    let totalAnalyzed = 0;
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
                    const $errorsKpi = $report.find('[data-kpi="errors"]');
                    $errorsKpi.text(totalErrors);
                    $errorsKpi.closest('.seoo-report__kpi').toggleClass('seoo-report__kpi--danger', totalErrors > 0);
                }

                if (!shouldStopRunningAjax) {
                    setTimeout(() => {
                        runAjaxProcess(input_submit, false);
                    }, 200);
                } else {
                    document.location.reload();
                }
            }

            if (result.status === 'done') {
                document.location.reload();
            }

        },
        error: function ( jqXHR, textStatus, errorThrown ) {
            /*document.location.href = href+'&error';*/
        }
    });


}

function initAuditCsvButtons() {
    $('.seoo-audit__csv-btn').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('audit-action');
        window.location.href = SeoOptimizerAjaxUrl + '&ajax=1&action=' + action;
    });
}

function initAuditButtons() {
    $('.seoo-audit__start-btn').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const action = $btn.data('audit-action');
        const $audit = $btn.closest('.seoo-audit');
        const $progressWrap = $audit.find('.seoo-audit__progress-wrap');
        const $bar = $audit.find('[data-audit-bar]');

        $btn.prop('disabled', true).text('Crawling...');
        $progressWrap.show();
        $bar.css('width', '0%').removeClass('bg-success').addClass('bg-processing');

        runAuditBatch(action, true, $audit, $btn);
    });
}

function runAuditBatch(action, firstProcess, $audit, $btn) {
    $.ajax({
        type: 'POST',
        url: SeoOptimizerAjaxUrl,
        data: {
            ajax: 1,
            action: action,
            first_process: firstProcess
        },
        dataType: 'json',
        success: function(result) {
            if (!result || !result.audit) {
                $btn.prop('disabled', false).html('<i class="process-icon-search"></i> Start audit');
                return;
            }

            const audit = result.audit;

            // KPIs live update
            if (audit.kpis) {
                $.each(audit.kpis, function(i, kpi) {
                    const $kpiValue = $audit.find('[data-audit-kpi="' + kpi.key + '"]');
                    if ($kpiValue.length) {
                        $kpiValue.text(kpi.value);
                        $kpiValue.closest('.seoo-report__kpi')
                            .toggleClass('seoo-report__kpi--danger', !!kpi.danger)
                            .toggleClass('seoo-report__kpi--warning', !!kpi.warning);
                    }
                });
            }

            // Update per-type rows
            if (audit.items) {
                let doneTypes = 0;
                let totalTypes = 0;
                $.each(audit.items, function(typeKey, item) {
                    totalTypes++;
                    if (item.status === 'done') doneTypes++;

                    const $row = $audit.find('[data-audit-item="' + typeKey + '"]');
                    if (!$row.length) return;

                    // Progress bar
                    const $bar = $row.find('.progress-bar');
                    $bar.css('width', item.percentage + '%')
                        .attr('aria-valuenow', item.percentage);

                    if (item.status === 'done') {
                        $bar.removeClass('bg-processing progress-bar-striped progress-bar-animated').addClass('bg-success');
                    } else if (item.status === 'processing') {
                        $bar.addClass('bg-processing progress-bar-striped progress-bar-animated').removeClass('bg-success');
                    }

                    // Status label
                    const $statusLabel = $row.find('.seoo-report__status-label');
                    if (item.status === 'done') $statusLabel.text('Done');
                    else if (item.status === 'processing') $statusLabel.text('In progress');

                    // Progress fraction
                    $row.find('.seoo-report__progress-value').text(item.crawled + ' / ' + item.total);

                    // Issues badge
                    const $badge = $row.find('.seoo-report__badge');
                    $badge.text(item.issues_count);
                    $badge.toggleClass('seoo-report__badge--danger', item.issues_count > 0);
                    $badge.toggleClass('seoo-report__badge--success', item.issues_count === 0);
                });
            }

            if (result.status === 'success') {
                setTimeout(function() {
                    runAuditBatch(action, false, $audit, $btn);
                }, 100);
            } else if (result.status === 'done') {
                document.location.reload();
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="process-icon-search"></i> Start audit');
        }
    });
}