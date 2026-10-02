@extends('management.layouts.master')
@section('title')
    Create Job Order
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Job Order</h4>
                    <a href="{{ route('production.job-orders.index') }}" 
                       onclick="loadPageContent('{{ route('production.job-orders.index') }}')" 
                       class="btn btn-sm btn-secondary">
                        <i class="ft-arrow-left mr-1"></i>Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('production.job-orders.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
                        @csrf
                        <input type="hidden" id="url" value="{{ route('production.job-orders.index') }}" />
                        <input type="hidden" id="listRefresh" value="{{ route('production.job-orders.getList') }}" />

                        <div class="row form-mar">
                            <!-- Basic Information -->
                            <div class="col-md-12">
                                <h6 class="header-heading-sepration">Basic Information</h6>
                                <div class="row">
                                    <!-- Location -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Plant / Factory Location <span class="text-danger">*</span></label>
                                            <select name="company_location_id" id="company_location_id" class="form-control select2" required>
                                                <option value="">-- Select Plant Location --</option>
                                                @foreach($locations as $loc)
                                                    @php
                                                        $pIds = (array)($loc->production_phases ?? []);
                                                        $phaseNames = [];
                                                        foreach($pIds as $pId) {
                                                            $pObj = $allPhases->firstWhere('id', (int)$pId);
                                                            if ($pObj) {
                                                                $phaseNames[] = $pObj->name;
                                                            }
                                                        }
                                                        $phaseLabel = !empty($phaseNames) ? implode(' | ', $phaseNames) : 'No phases';
                                                    @endphp
                                                    <option value="{{ $loc->id }}" 
                                                            data-code="{{ $loc->code }}" 
                                                            data-phases="{{ json_encode(array_map('intval', $pIds)) }}">
                                                        {{ $loc->name }} ({{ $phaseLabel }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Job Order No -->
                                    <div class="col-md-4">
                                        <fieldset>
                                            <label>Job Order No# <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button class="btn btn-primary" type="button">Job Order No#</button>
                                                </div>
                                                <input type="text" readonly name="job_order_no" id="job_order_no" 
                                                       class="form-control" placeholder="Select Location" required>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- Commodity / Product -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Commodity / Product</label>
                                            <select name="product_id" id="product_id" class="form-control select2">
                                                <option value="">-- Select Commodity --</option>
                                                @foreach($products as $prod)
                                                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Export Order -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Export Order</label>
                                            <select name="export_order_id" id="export_order_id" class="form-control select2">
                                                <option value="">-- None / Domestic Order --</option>
                                                @foreach($exportOrders as $eo)
                                                    <option value="{{ $eo->id }}">{{ $eo->voucher_no ?? ('#' . $eo->id) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Job Order Date -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Job Order Date <span class="text-danger">*</span></label>
                                            <input type="date" name="job_order_date" id="job_order_date" 
                                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <!-- Ref No -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Reference No</label>
                                            <input type="text" name="ref_no" id="ref_no" class="form-control" placeholder="Internal or Customer Ref #">
                                        </div>
                                    </div>

                                    <!-- Attention To -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Attention To</label>
                                            <select name="attention_to[]" id="attention_to" class="form-control select2" multiple data-placeholder="Select Users">
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Order Description -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Order Description</label>
                                            <textarea name="order_description" id="order_description" rows="3" class="form-control" placeholder="Order description..."></textarea>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Remarks</label>
                                            <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Remarks..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Production Phases Selection -->
                            <div class="col-md-12 mt-2">
                                <h6 class="header-heading-sepration">Production Phases</h6>
                                <div id="noLocationSelectedNotice" class="text-muted small my-2">
                                    Please select a Plant / Factory Location above to display available production phases.
                                </div>

                                <div id="phasesCheckboxWrapper" class="d-none my-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Select production phases for this Job Order:</small>
                                        <span class="badge badge-light border px-2 py-1 font-weight-semibold" id="selectedPhasesCountBadge">0 Phases Selected</span>
                                    </div>
                                    <div class="row" id="phasesCheckboxRow">
                                        @foreach($allPhases as $phase)
                                            <div class="col-md-4 mb-2 phase-checkbox-col" id="col-chk-phase{{ $phase->id }}" style="display:none;">
                                                <div class="phase-checkbox-box p-2 border rounded bg-white d-flex align-items-center justify-content-between">
                                                    <div class="custom-control custom-checkbox mr-2">
                                                        <input type="checkbox" class="custom-control-input phase-selector-checkbox" 
                                                               id="chk-phase{{ $phase->id }}" name="selected_phases[]" value="{{ $phase->id }}" data-tab="tab-phase{{ $phase->id }}" checked>
                                                        <label class="custom-control-label font-weight-bold text-dark cursor-pointer mb-0" for="chk-phase{{ $phase->id }}">
                                                            {{ $phase->name }}
                                                        </label>
                                                    </div>
                                                    @if(!empty($phase->category))
                                                        <span class="badge badge-light border ml-auto">{{ $phase->category }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Production Phase Tabs -->
                            <div class="col-md-12" id="phasesTabsContainer" style="display: none;">
                                <ul class="nav nav-tabs" id="productionTabs" role="tablist">
                                    @foreach($allPhases as $phase)
                                        <li class="nav-item phase-tab-item" id="tab-nav-phase{{ $phase->id }}" style="display:none;">
                                            <a class="nav-link font-weight-bold" data-toggle="tab" href="#tab-phase{{ $phase->id }}" role="tab">
                                                {{ $phase->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="tab-content pt-3" id="productionTabsContent">
                                    <!-- TAB: PHASE 1 - DRYING -->
                                    <div class="tab-pane fade" id="tab-phase1" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Drying Mode</label>
                                                    <select name="p1_drying_mode" class="form-control">
                                                        <option value="batch">Batch Drying</option>
                                                        <option value="series">Series Drying</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Dryer Temperature (°C)</label>
                                                    <input type="number" step="0.1" name="p1_temperature" class="form-control" placeholder="e.g. 45.0">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Moisture Level Output</label>
                                                    <select name="p1_moisture_level" class="form-control">
                                                        <option value="half_dried">Half-Dried (~15% Moisture)</option>
                                                        <option value="fully_dried">Fully-Dried (13.5% - 14% Moisture)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Phase 1 Remarks</label>
                                                    <textarea name="p1_remarks" rows="2" class="form-control" placeholder="Drying notes or cycle remarks..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 2 - STEAMING / PARBOILING -->
                                    <div class="tab-pane fade" id="tab-phase2" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Process Selection <span class="text-danger">*</span></label>
                                                    <select name="p2_process_type" id="p2_process_type" class="form-control">
                                                        <option value="steaming">Steaming (White / Sella Steam)</option>
                                                        <option value="parboiling">Parboiling (Soak & Boil)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Steaming Fields -->
                                            <div class="col-md-4 p2-steaming-field">
                                                <div class="form-group">
                                                    <label>Steam Type</label>
                                                    <select name="p2_steam_type" class="form-control">
                                                        <option value="single_steam">Single Steam</option>
                                                        <option value="double_steam">Double Steam</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Parboiling Fields -->
                                            <div class="col-md-4 p2-parboiling-field d-none">
                                                <div class="form-group">
                                                    <label>Soaking Time (Hours)</label>
                                                    <input type="number" step="0.5" name="p2_parboil_soak_hours" class="form-control" placeholder="e.g. 8.0">
                                                </div>
                                            </div>

                                            <div class="col-md-4 p2-parboiling-field d-none">
                                                <div class="form-group">
                                                    <label>Cooking Time (Minutes)</label>
                                                    <input type="number" name="p2_parboil_cook_minutes" class="form-control" placeholder="e.g. 20">
                                                </div>
                                            </div>

                                            <div class="col-md-4 p2-parboiling-field d-none">
                                                <div class="form-group">
                                                    <label>Parboiled Output Grade</label>
                                                    <select name="p2_parboiled_grade" class="form-control">
                                                        <option value="golden_sella">Golden Sella</option>
                                                        <option value="creamy_sella">Creamy Sella</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Phase 2 Remarks</label>
                                                    <textarea name="p2_remarks" rows="2" class="form-control" placeholder="Specific cooking notes, temperature variations..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 3 - MILLING -->
                                    <div class="tab-pane fade" id="tab-phase3" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Milling Feed Source</label>
                                                    <select name="p3_milling_type" class="form-control">
                                                        <option value="direct_milling">Direct Paddy Milling (Raw White Rice)</option>
                                                        <option value="post_steaming">Post-Steaming Milling</option>
                                                        <option value="post_parboiling">Post-Parboiling (Sella) Milling</option>
                                                        <option value="reprocessing">Reprocessing Mill (Already-milled)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Storage Location Post-Husking</label>
                                                    <input type="text" class="form-control" value="Flat Storage" readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Phase 3 Milling Notes</label>
                                                    <input type="text" name="p3_remarks" class="form-control" placeholder="Milling instructions...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="col-md-12 text-right mt-3">
                                <a href="{{ route('production.job-orders.index') }}" 
                                   onclick="loadPageContent('{{ route('production.job-orders.index') }}')" 
                                   class="btn btn-secondary mr-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary submitbutton">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        if (typeof $('.select2').select2 === 'function') {
            $('.select2').select2({ width: '100%' });
        }

        // Handle Location Selection
        $('#company_location_id').on('change', function() {
            const locId = $(this).val();
            const selectedOpt = $(this).find('option:selected');
            const phases = selectedOpt.data('phases') || [];

            if (!locId) {
                $('#noLocationSelectedNotice').removeClass('d-none');
                $('#phasesCheckboxWrapper').addClass('d-none');
                $('#phasesTabsContainer').hide();
                $('.phase-checkbox-col').hide();
                $('.phase-tab-item').hide();
                $('#job_order_no').val('');
                return;
            }

            $('#noLocationSelectedNotice').addClass('d-none');
            $('#phasesCheckboxWrapper').removeClass('d-none');

            // Show checkboxes dynamically only for enabled phases of this location
            $('.phase-checkbox-col').each(function() {
                const phaseId = parseInt($(this).find('.phase-selector-checkbox').val(), 10);
                if (phases.includes(phaseId) || phases.includes(String(phaseId))) {
                    $(this).show();
                    $(this).find('.phase-selector-checkbox').prop('checked', true);
                } else {
                    $(this).hide();
                    $(this).find('.phase-selector-checkbox').prop('checked', false);
                }
            });

            // Sync tabs based on checked checkboxes
            syncPhaseTabsWithCheckboxes();

            // Fetch unique Job Order No
            fetchJobOrderNumber();
        });

        // Click anywhere in phase box to toggle checkbox
        $(document).on('click', '.phase-checkbox-box', function(e) {
            if (!$(e.target).is('input') && !$(e.target).is('label')) {
                $(this).find('.phase-selector-checkbox').trigger('click');
            }
        });

        // Whenever a phase checkbox is toggled
        $(document).on('change', '.phase-selector-checkbox', function() {
            syncPhaseTabsWithCheckboxes();
        });

        function syncPhaseTabsWithCheckboxes() {
            let checkedCount = 0;
            let firstActiveTabNav = null;

            $('.phase-selector-checkbox').each(function() {
                const phaseNum = $(this).val();
                const isChecked = $(this).is(':checked');
                const isVisible = $(this).closest('.phase-checkbox-col').is(':visible');
                const tabNavId = '#tab-nav-phase' + phaseNum;
                const tabPaneId = '#tab-phase' + phaseNum;
                const $box = $(this).closest('.phase-checkbox-box');

                if (isVisible && isChecked) {
                    $(tabNavId).show();
                    $box.addClass('is-selected');
                    checkedCount++;
                    if (!firstActiveTabNav) {
                        firstActiveTabNav = tabNavId;
                    }
                } else {
                    $(tabNavId).hide();
                    $(tabPaneId).removeClass('show active');
                    $(tabNavId + ' a').removeClass('active');
                    $box.removeClass('is-selected');
                }
            });

            $('#selectedPhasesCountBadge').text(checkedCount + ' Phase' + (checkedCount === 1 ? '' : 's') + ' Selected');

            if (checkedCount > 0) {
                $('#phasesTabsContainer').show();
                if (!$('#productionTabs .nav-link.active').is(':visible')) {
                    if (firstActiveTabNav) {
                        $(firstActiveTabNav + ' a').tab('show');
                    }
                }
            } else {
                $('#phasesTabsContainer').hide();
            }
        }

        $('#job_order_date').on('change', function() {
            if ($('#company_location_id').val()) {
                fetchJobOrderNumber();
            }
        });

        function fetchJobOrderNumber() {
            const locId = $('#company_location_id').val();
            const dateVal = $('#job_order_date').val();
            if (!locId) return;

            $.ajax({
                url: "{{ route('production.job-orders.get-number') }}",
                type: 'GET',
                data: {
                    location_id: locId,
                    job_order_date: dateVal
                },
                success: function(res) {
                    if (res.job_order_no) {
                        $('#job_order_no').val(res.job_order_no);
                    }
                }
            });
        }

        // Toggle Steaming vs Parboiling in Phase 2
        $('#p2_process_type').on('change', function() {
            if ($(this).val() === 'steaming') {
                $('.p2-steaming-field').removeClass('d-none');
                $('.p2-parboiling-field').addClass('d-none');
            } else {
                $('.p2-steaming-field').addClass('d-none');
                $('.p2-parboiling-field').removeClass('d-none');
            }
        });

        // Trigger on load if pre-selected
        if ($('#company_location_id').val()) {
            $('#company_location_id').trigger('change');
        } else if ($('#company_location_id option').length === 2) {
            $('#company_location_id').val($('#company_location_id option:nth-child(2)').val()).trigger('change');
        }
    });
</script>

<style>
    .phase-checkbox-box {
        background-color: #fff;
        border: 1px solid #d2d6de;
        border-radius: 4px;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        user-select: none;
    }
    .phase-checkbox-box:hover {
        background-color: #f9fafb;
        border-color: #b0b7c3;
    }
    .phase-checkbox-box.is-selected {
        border-color: #007bff;
        background-color: #f4f8fd;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endsection
