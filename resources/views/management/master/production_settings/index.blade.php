@extends('management.layouts.master')
@section('title')
Production Settings
@endsection
@section('content')
<div class="content-wrapper">
    <section id="extended">
        <div class="row w-100 mx-auto mb-2">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"><i class="ft-settings mr-2"></i> Production Settings</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom pb-0 bg-transparent" style="padding: 12px 20px 0 20px;">
                        <ul class="nav production-tabs-nav" id="productionSettingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="plant-analysis-tab" href="#plant-analysis" role="tab" aria-controls="plant-analysis" aria-selected="true">
                                    <i class="ft-activity mr-1"></i> Plant Analysis Intervals (Auto Request)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="fumigation-types-tab" href="#fumigation-types" role="tab" aria-controls="fumigation-types" aria-selected="false">
                                    <i class="ft-shield mr-1"></i> Fumigation Types
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="auto-history-tab" href="#auto-history" role="tab" aria-controls="auto-history" aria-selected="false">
                                    <i class="ft-clock mr-1"></i> Auto Request History
                                    @if(!empty($autoHistoryCountToday))
                                        <span class="badge badge-pill badge-primary ml-1" id="autoHistoryTodayBadge" title="{{ $autoHistoryCountToday }} auto requests generated today">{{ $autoHistoryCountToday }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <div class="tab-content" id="productionSettingsTabContent">
                                {{-- Tab 1: Plant Analysis Intervals (Location-Wise) --}}
                                <div class="tab-pane show active" id="plant-analysis" role="tabpanel" aria-labelledby="plant-analysis-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                        <div>
                                            <h5 class="mb-1 font-weight-bold text-dark">
                                                <i class="ft-sliders mr-1 text-primary"></i> Plant-Wise Auto Analysis Request Ranges
                                            </h5>
                                            <p class="font-small-2 text-muted mb-0">
                                                Configure Min &amp; Max minutes intervals plant-wise (grouped by Location).
                                                Analysis Requests will only auto-create between this range if the plant is active, its Machine Plan Setting is running, and no breakdown exists.
                                            </p>
                                        </div>
                                        <div class="mt-2 mt-md-0 d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-info btn-sm mr-2" id="triggerAutoCheckBtn" title="Run instant auto-generation check">
                                                <i class="ft-play mr-1"></i> Run Auto Check Now
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="reloadPlantAnalysisSettings()" title="Refresh table">
                                                <i class="ft-refresh-cw mr-1"></i> Refresh
                                            </button>
                                        </div>
                                    </div>

                                    <div id="plantAnalysisContainer">
                                        @include('management.master.production_settings.partials.plant_analysis_table', [
                                            'locationPlants' => $locationPlants,
                                            'plantStatuses' => $plantStatuses
                                        ])
                                    </div>
                                </div>

                                {{-- Tab 2: Fumigation Types --}}
                                <div class="tab-pane" id="fumigation-types" role="tabpanel" aria-labelledby="fumigation-types-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 text-muted">Manage Fumigation Types</h5>
                                        <button onclick="openModal(this, '{{ route('production-settings.fumigation-types.create') }}', 'Add Fumigation Type')"
                                            type="button" class="btn btn-primary position-relative">
                                            <i class="ft-plus mr-1"></i> Add Fumigation Type
                                        </button>
                                    </div>

                                    <div class="table-responsive" id="filteredData">
                                        @include('management.master.production_settings.partials.fumigation_types_table', ['fumigationTypes' => $fumigationTypes])
                                    </div>
                                </div>

                                {{-- Tab 3: Auto Request Generation History --}}
                                <div class="tab-pane" id="auto-history" role="tabpanel" aria-labelledby="auto-history-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                        <div>
                                            <h5 class="mb-1 font-weight-bold text-dark">
                                                <i class="ft-clock mr-1 text-primary"></i> Auto Generated Request History
                                            </h5>
                                            <p class="font-small-2 text-muted mb-0">
                                                Audit log of all Production Analysis Requests automatically triggered by running plants and scheduled interval windows.
                                            </p>
                                        </div>
                                        <div class="mt-2 mt-md-0 d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="reloadAutoHistory()" title="Refresh History">
                                                <i class="ft-refresh-cw mr-1"></i> Refresh History
                                            </button>
                                        </div>
                                    </div>

                                    <div id="autoHistoryContainer">
                                        @include('management.master.production_settings.partials.auto_history_table', ['historyRequests' => $historyRequests])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    /* Custom Production Tabs Navigation */
    .production-tabs-nav {
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        gap: 6px;
        padding: 0;
        margin-bottom: 0;
    }

    .production-tabs-nav .nav-item {
        margin-bottom: -2px;
    }

    .production-tabs-nav .nav-link {
        border: 2px solid transparent;
        border-bottom: 3px solid transparent;
        background: #f8fafc;
        color: #64748b;
        font-size: 13.5px;
        font-weight: 600;
        padding: 11px 22px;
        border-radius: 8px 8px 0 0;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
    }

    .production-tabs-nav .nav-link:hover {
        color: #1e293b;
        background: #f1f5f9;
        border-color: #e2e8f0 #e2e8f0 transparent #e2e8f0;
    }

    .production-tabs-nav .nav-link.active {
        color: #0284c7 !important;
        background: #ffffff !important;
        border-color: #e2e8f0 #e2e8f0 #ffffff #e2e8f0;
        border-bottom: 3px solid #0284c7 !important;
        font-weight: 700;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.03);
    }

    .production-tabs-nav .nav-link i {
        font-size: 15px;
    }

    .production-tabs-nav .nav-link.active i {
        color: #0284c7;
    }

    .production-tabs-nav .nav-link:not(.active) i {
        color: #94a3b8;
    }

    /* Tab Pane Visibility */
    #productionSettingsTabContent > .tab-pane {
        display: none !important;
    }

    #productionSettingsTabContent > .tab-pane.active {
        display: block !important;
        animation: fadeInTab 0.18s ease-in-out;
    }

    @keyframes fadeInTab {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Card & Location Header Styling */
    .location-card {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 24px;
        background: #ffffff;
    }

    .location-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 18px;
    }

    .location-title-badge {
        background: #1e293b;
        color: #ffffff;
        font-weight: 700;
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.3px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .location-meta-tag {
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .location-id-pill {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
    }

    /* Table Design */
    .plant-table {
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
        width: 100%;
    }

    .plant-table th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 11px 12px;
        border-top: none;
        border-bottom: 2px solid #e2e8f0;
        vertical-align: middle;
    }

    .col-header-input {
        background-color: #f0f7ff !important;
        border-left: 1px solid #e0eeff;
        border-right: 1px solid #e0eeff;
    }

    .col-header-output {
        background-color: #f0fff4 !important;
        border-right: 1px solid #e0f9e7;
    }

    .col-cell-input {
        background-color: #f8fbff;
        border-left: 1px solid #edf4fc;
        border-right: 1px solid #edf4fc;
    }

    .col-cell-output {
        background-color: #f8fdf9;
        border-right: 1px solid #ecf8ef;
    }

    .plant-table td {
        padding: 12px 14px;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9;
    }

    .plant-setting-row:hover {
        background-color: #fbfcfe !important;
    }

    .plant-title {
        color: #1e293b;
        font-size: 13.5px;
        letter-spacing: 0.1px;
    }

    /* Machine Status Badges */
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.3;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }

    .status-pill-running {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }

    .status-pill-running .live-dot {
        width: 7px;
        height: 7px;
        background-color: #16a34a;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.25);
        animation: live-pulse 1.8s infinite;
    }

    @keyframes live-pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
        70% { transform: scale(1.15); box-shadow: 0 0 0 4px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    .status-pill-breakdown {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    .status-pill-idle {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fcd34d;
    }

    .status-pill-noplan {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    /* Range Min/Max Dual Inputs */
    .range-input-box {
        display: inline-flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 3px 6px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .range-input-box.input-box:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        background: #ffffff;
    }

    .range-input-box.output-box:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        background: #ffffff;
    }

    .range-num-input {
        width: 46px;
        height: 26px;
        border: none;
        background: transparent;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        color: #0f172a;
        padding: 0 2px;
    }

    .range-num-input:focus {
        outline: none;
        background: #f1f5f9;
        border-radius: 4px;
    }

    .range-sep {
        color: #94a3b8;
        font-weight: 600;
        font-size: 13px;
        padding: 0 5px;
        user-select: none;
    }

    .range-unit {
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
        user-select: none;
    }

    /* Next Scheduled Request Badges */
    .sched-row {
        display: flex;
        align-items: center;
        min-height: 24px;
    }

    .tag-analysis-type {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 20px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-right: 8px;
        flex-shrink: 0;
        line-height: 1;
    }

    .tag-analysis-input {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    .tag-analysis-output {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .sched-badge-pending {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    .sched-badge-due {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
    }

    .sched-text-range {
        color: #64748b;
        font-size: 11.5px;
        font-weight: 500;
    }

    /* Action button */
    .btn-save-row {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0284c7;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-save-row:hover {
        background: #0284c7;
        border-color: #0284c7;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2);
    }

    /* Clean custom toggle switch */
    .toggle-switch-custom {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin: 0;
        cursor: pointer;
        vertical-align: middle;
    }
    .toggle-switch-custom input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .toggle-slider-custom {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: all 0.25s ease;
        border-radius: 24px;
    }
    .toggle-slider-custom:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff;
        transition: all 0.25s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }
    .toggle-switch-custom input:checked + .toggle-slider-custom {
        background-color: #007bff;
    }
    .toggle-switch-custom input:checked + .toggle-slider-custom:before {
        transform: translateX(20px);
    }
</style>
@endsection

@section('script')
<script>
    // Bulletproof Tab Switching
    function activateProductionTab(tabId) {
        if (!tabId) return;

        var targetSelector = tabId.startsWith('#') ? tabId : '#' + tabId;
        var $tabLink = $('#productionSettingsTabs a[href="' + targetSelector + '"]');

        if ($tabLink.length) {
            $('#productionSettingsTabs .nav-link').removeClass('active').attr('aria-selected', 'false');
            $tabLink.addClass('active').attr('aria-selected', 'true');

            $('#productionSettingsTabContent > .tab-pane').removeClass('show active');
            $(targetSelector).addClass('show active');

            if (history.replaceState) {
                history.replaceState(null, null, targetSelector);
            }

            if (targetSelector === '#auto-history') {
                reloadAutoHistory();
            }
        }
    }

    $(document).on('click', '#productionSettingsTabs a', function (e) {
        e.preventDefault();
        var target = $(this).attr('href');
        activateProductionTab(target);
    });

    // Check URL hash on initial page load
    $(document).ready(function() {
        if (window.location.hash) {
            activateProductionTab(window.location.hash);
        }
        if (typeof initCustomDaterangepicker === 'function') {
            initCustomDaterangepicker('#historyDateRange');
        }
    });

    function reloadFumigationTypes() {
        filterationCommon("{{ route('production-settings.fumigation-types.list') }}");
    }

    function reloadPlantAnalysisSettings() {
        let $btn = $('button[onclick="reloadPlantAnalysisSettings()"]');
        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="ft-loader ft-spin mr-1"></i> Refreshing...');

        $.get("{{ route('production-settings.plant-analysis.table') }}", function(html) {
            $('#plantAnalysisContainer').html(html);
        }).fail(function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to reload plant analysis settings table.');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(origHtml);
        });
    }

    // Run Auto Check Now Button
    $(document).off('click', '#triggerAutoCheckBtn').on('click', '#triggerAutoCheckBtn', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="ft-loader ft-spin mr-1"></i> Checking Plants...');

        $.post("{{ route('production-settings.plant-analysis.trigger-check') }}", {
            _token: "{{ csrf_token() }}"
        }, function(response) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Auto Check Complete',
                    text: response.success,
                    confirmButtonColor: '#007bff'
                });
            } else {
                alert(response.success);
            }
            reloadPlantAnalysisSettings();
            reloadAutoHistory();
        }).fail(function(xhr) {
            let msg = xhr.responseJSON?.catchError || 'Failed to trigger auto check.';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            } else {
                alert(msg);
            }
        }).always(function() {
            $btn.prop('disabled', false).html(origHtml);
        });
    });

    // Save individual plant row
    $(document).off('click', '.save-single-plant-btn').on('click', '.save-single-plant-btn', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let url = $btn.data('url');
        let $row = $btn.closest('tr');

        let isEnabled = $row.find('.plant-toggle-enabled').is(':checked') ? 1 : 0;
        let requireCompleted = $row.find('.plant-require-completed').is(':checked') ? 1 : 0;
        let inMin = parseInt($row.find('.plant-input-min').val(), 10) || 20;
        let inMax = parseInt($row.find('.plant-input-max').val(), 10) || 60;
        let outMin = parseInt($row.find('.plant-output-min').val(), 10) || 20;
        let outMax = parseInt($row.find('.plant-output-max').val(), 10) || 60;

        if (inMax < inMin) {
            if (typeof toastr !== 'undefined') toastr.error('Input Max interval must be >= Min interval.');
            else alert('Input Max interval must be >= Min interval.');
            return;
        }

        if (outMax < outMin) {
            if (typeof toastr !== 'undefined') toastr.error('Output Max interval must be >= Min interval.');
            else alert('Output Max interval must be >= Min interval.');
            return;
        }

        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="ft-loader ft-spin font-medium-1"></i>');

        $.post(url, {
            _token: "{{ csrf_token() }}",
            auto_analysis_enabled: isEnabled,
            require_last_completed: requireCompleted,
            input_min_interval: inMin,
            input_max_interval: inMax,
            output_min_interval: outMin,
            output_max_interval: outMax
        }, function(response) {
            if (typeof toastr !== 'undefined') {
                toastr.success(response.success);
            } else {
                alert(response.success);
            }
            reloadPlantAnalysisSettings();
        }).fail(function(xhr) {
            let msg = xhr.responseJSON?.message || xhr.responseJSON?.catchError || 'Failed to save settings.';
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
            $btn.html(origHtml);
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    // Bulk Save all plants form
    $(document).off('submit', '#bulkPlantSettingsForm').on('submit', '#bulkPlantSettingsForm', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $('#bulkSavePlantSettingsBtn');
        let origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="ft-loader ft-spin mr-1"></i> Saving All Plant Settings...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: response.success,
                        confirmButtonColor: '#007bff'
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.success(response.success);
                } else {
                    alert(response.success);
                }
                reloadPlantAnalysisSettings();
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.catchError || xhr.responseJSON?.message || 'Failed to save plant settings.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else {
                    alert(msg);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // Auto Request History Reload
    function reloadAutoHistory(page = 1) {
        let $container = $('#autoHistoryContainer');
        let formData = $('#autoHistoryFilterForm').serializeArray() || [];
        formData.push({ name: 'page', value: page });

        $container.html('<div class="text-center py-5 text-muted"><i class="ft-loader ft-spin font-large-1 mb-2"></i><p class="font-small-3">Loading auto request history...</p></div>');

        $.ajax({
            url: "{{ route('production-settings.auto-history') }}",
            type: "GET",
            data: formData,
            success: function(html) {
                $container.html(html);
                if (typeof initCustomDaterangepicker === 'function') {
                    initCustomDaterangepicker('#historyDateRange');
                }
            },
            error: function() {
                $container.html('<div class="alert alert-danger text-center p-3">Failed to load auto request history.</div>');
            }
        });
    }

    // Auto load when Auto History tab is activated
    $(document).on('shown.bs.tab', '#auto-history-tab', function() {
        reloadAutoHistory();
    });

    // Handle history pagination clicks
    $(document).on('click', '#autoHistoryContainer .pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        let page = 1;
        if (url && url.includes('page=')) {
            page = url.split('page=')[1].split('&')[0];
        }
        reloadAutoHistory(page);
    });

    // History filter submit & reset
    $(document).on('submit', '#autoHistoryFilterForm', function(e) {
        e.preventDefault();
        reloadAutoHistory(1);
    });

    $(document).on('click', '#resetAutoHistoryBtn', function(e) {
        e.preventDefault();
        $('#autoHistoryFilterForm')[0].reset();
        $('#historyPlantId').val('');
        reloadAutoHistory(1);
    });

    // Click "View History" button on plant row to filter by that plant
    $(document).on('click', '.view-plant-auto-history-btn', function(e) {
        e.preventDefault();
        let plantId = $(this).data('plant-id');

        // Activate Tab 3 using bulletproof tab switcher
        activateProductionTab('#auto-history');

        // Apply plant filter & reload
        setTimeout(function() {
            $('#historyPlantId').val(plantId);
            reloadAutoHistory(1);
        }, 80);
    });
</script>
@endsection
