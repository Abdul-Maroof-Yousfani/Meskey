@extends('management.layouts.master')
@section('title')
    Edit Job Order #{{ $jobOrder->job_order_no }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Job Order: {{ $jobOrder->job_order_no }}</h4>
                    <div>
                        <a href="{{ route('production.job-orders.show', $jobOrder->id) }}" 
                           onclick="loadPageContent('{{ route('production.job-orders.show', $jobOrder->id) }}')" 
                           class="btn btn-sm btn-info mr-1">
                            <i class="ft-eye mr-1"></i>View
                        </a>
                        <a href="{{ route('production.job-orders.index') }}" 
                           onclick="loadPageContent('{{ route('production.job-orders.index') }}')" 
                           class="btn btn-sm btn-secondary">
                            <i class="ft-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('production.job-orders.update', $jobOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
                        @csrf
                        @method('PUT')
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
                                                            data-phases="{{ json_encode(array_map('intval', $pIds)) }}"
                                                            {{ $jobOrder->company_location_id == $loc->id ? 'selected' : '' }}>
                                                        {{ $loc->name }} ({{ $phaseLabel }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Job Order No -->
                                    <div class="col-md-4">
                                        <fieldset>
                                            <label>Job Order No#</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button class="btn btn-primary" type="button">Job Order No#</button>
                                                </div>
                                                <input type="text" name="job_order_no" id="job_order_no" 
                                                       class="form-control" value="{{ $jobOrder->job_order_no }}" readonly>
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
                                                    <option value="{{ $prod->id }}" {{ $jobOrder->product_id == $prod->id ? 'selected' : '' }}>
                                                        {{ $prod->name }}
                                                    </option>
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
                                                    <option value="{{ $eo->id }}" {{ $jobOrder->export_order_id == $eo->id ? 'selected' : '' }}>
                                                        {{ $eo->voucher_no ?? ('#' . $eo->id) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Job Order Date -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Job Order Date <span class="text-danger">*</span></label>
                                            <input type="date" name="job_order_date" id="job_order_date" 
                                                   class="form-control" 
                                                   value="{{ $jobOrder->job_order_date ? $jobOrder->job_order_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <!-- Ref No -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Reference No</label>
                                            <input type="text" name="ref_no" id="ref_no" class="form-control" value="{{ $jobOrder->ref_no }}">
                                        </div>
                                    </div>

                                    <!-- Attention To -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Attention To</label>
                                            @php
                                                $selectedUsers = (array)($jobOrder->attention_to ?? []);
                                            @endphp
                                            <select name="attention_to[]" id="attention_to" class="form-control select2" multiple data-placeholder="Select Users">
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" {{ in_array($user->id, $selectedUsers) ? 'selected' : '' }}>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Order Description -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Order Description</label>
                                            <textarea name="order_description" id="order_description" rows="3" class="form-control">{{ $jobOrder->order_description }}</textarea>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Remarks</label>
                                            <textarea name="remarks" id="remarks" rows="3" class="form-control">{{ $jobOrder->remarks }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Production Phases Selection -->
                            @php
                                $jobActivePhases = (array)($jobOrder->active_phases ?? []);
                            @endphp
                            <div class="col-md-12 mt-2">
                                <h6 class="header-heading-sepration">Production Phases</h6>

                                <div id="phasesCheckboxWrapper" class="my-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">Select production phases for this Job Order:</small>
                                        <span class="badge badge-light border px-2 py-1 font-weight-semibold" id="selectedPhasesCountBadge">0 Phases Selected</span>
                                    </div>
                                    <div class="row" id="phasesCheckboxRow">
                                        @foreach($allPhases as $phase)
                                            @php
                                                $isPhaseChecked = in_array((int)$phase->id, array_map('intval', (array)$jobActivePhases));
                                            @endphp
                                            <div class="col-md-4 mb-2 phase-checkbox-col" id="col-chk-phase{{ $phase->id }}">
                                                <div class="phase-checkbox-box p-2 border rounded bg-white d-flex align-items-center justify-content-between {{ $isPhaseChecked ? 'is-selected' : '' }}">
                                                    <div class="custom-control custom-checkbox mr-2">
                                                        <input type="checkbox" class="custom-control-input phase-selector-checkbox" 
                                                               id="chk-phase{{ $phase->id }}" name="selected_phases[]" value="{{ $phase->id }}" data-tab="tab-phase{{ $phase->id }}"
                                                               {{ $isPhaseChecked ? 'checked' : '' }}>
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
                            <div class="col-md-12" id="phasesTabsContainer">
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
                                    <!-- TAB: PHASE 1 -->
                                    @php
                                        $p1 = $jobOrder->productionPhase1->first();
                                    @endphp
                                    <div class="tab-pane fade" id="tab-phase1" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Drying Mode</label>
                                                    <select name="p1_drying_mode" class="form-control">
                                                        <option value="batch" {{ ($p1?->drying_mode == 'batch') ? 'selected' : '' }}>Batch Drying</option>
                                                        <option value="series" {{ ($p1?->drying_mode == 'series') ? 'selected' : '' }}>Series Drying</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Dryer Temperature (°C)</label>
                                                    <input type="number" step="0.1" name="p1_temperature" class="form-control" 
                                                           value="{{ $p1?->temperature }}" placeholder="e.g. 45.0">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Moisture Level Output</label>
                                                    <select name="p1_moisture_level" class="form-control">
                                                        <option value="half_dried" {{ ($p1?->moisture_level == 'half_dried') ? 'selected' : '' }}>Half-Dried (~15% Moisture)</option>
                                                        <option value="fully_dried" {{ ($p1?->moisture_level == 'fully_dried') ? 'selected' : '' }}>Fully-Dried (13.5% - 14% Moisture)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Phase 1 Remarks</label>
                                                    <textarea name="p1_remarks" rows="2" class="form-control" placeholder="Drying notes or cycle remarks...">{{ $p1?->remarks }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 2 -->
                                    @php
                                        $p2 = $jobOrder->productionPhase2->first();
                                        $processType = $p2?->process_type ?? 'steaming';
                                    @endphp
                                    <div class="tab-pane fade" id="tab-phase2" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Process Selection <span class="text-danger">*</span></label>
                                                    <select name="p2_process_type" id="p2_process_type" class="form-control">
                                                        <option value="steaming" {{ $processType == 'steaming' ? 'selected' : '' }}>Steaming (White / Sella Steam)</option>
                                                        <option value="parboiling" {{ $processType == 'parboiling' ? 'selected' : '' }}>Parboiling (Soak & Boil)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Steaming Fields -->
                                            <div class="col-md-4 p2-steaming-field {{ $processType == 'parboiling' ? 'd-none' : '' }}">
                                                <div class="form-group">
                                                    <label>Steam Type</label>
                                                    <select name="p2_steam_type" class="form-control">
                                                        <option value="single_steam" {{ ($p2?->steam_type == 'single_steam') ? 'selected' : '' }}>Single Steam</option>
                                                        <option value="double_steam" {{ ($p2?->steam_type == 'double_steam') ? 'selected' : '' }}>Double Steam</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Parboiling Fields -->
                                            <div class="col-md-4 p2-parboiling-field {{ $processType != 'parboiling' ? 'd-none' : '' }}">
                                                <div class="form-group">
                                                    <label>Soaking Time (Hours)</label>
                                                    <input type="number" step="0.5" name="p2_parboil_soak_hours" class="form-control" 
                                                           value="{{ $p2?->parboil_soak_hours }}" placeholder="e.g. 8.0">
                                                </div>
                                            </div>

                                            <div class="col-md-4 p2-parboiling-field {{ $processType != 'parboiling' ? 'd-none' : '' }}">
                                                <div class="form-group">
                                                    <label>Cooking Time (Minutes)</label>
                                                    <input type="number" name="p2_parboil_cook_minutes" class="form-control" 
                                                           value="{{ $p2?->parboil_cook_minutes }}" placeholder="e.g. 20">
                                                </div>
                                            </div>

                                            <div class="col-md-4 p2-parboiling-field {{ $processType != 'parboiling' ? 'd-none' : '' }}">
                                                <div class="form-group">
                                                    <label>Parboiled Output Grade</label>
                                                    <select name="p2_parboiled_grade" class="form-control">
                                                        <option value="golden_sella" {{ ($p2?->parboiled_grade == 'golden_sella') ? 'selected' : '' }}>Golden Sella</option>
                                                        <option value="creamy_sella" {{ ($p2?->parboiled_grade == 'creamy_sella') ? 'selected' : '' }}>Creamy Sella</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Phase 2 Remarks</label>
                                                    <textarea name="p2_remarks" rows="2" class="form-control" placeholder="Specific cooking notes, temperature variations...">{{ $p2?->remarks }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 3 -->
                                    @php
                                        $p3 = $jobOrder->productionPhase3->first();
                                    @endphp
                                    <div class="tab-pane fade" id="tab-phase3" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Milling Feed Source</label>
                                                    <select name="p3_milling_type" class="form-control">
                                                        <option value="direct_milling" {{ ($p3?->milling_type == 'direct_milling') ? 'selected' : '' }}>Direct Paddy Milling (Raw White)</option>
                                                        <option value="post_steaming" {{ ($p3?->milling_type == 'post_steaming') ? 'selected' : '' }}>Post-Steaming Milling</option>
                                                        <option value="post_parboiling" {{ ($p3?->milling_type == 'post_parboiling') ? 'selected' : '' }}>Post-Parboiling (Sella) Milling</option>
                                                        <option value="reprocessing" {{ ($p3?->milling_type == 'reprocessing') ? 'selected' : '' }}>Reprocessing Mill</option>
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
                                                    <input type="text" name="p3_remarks" class="form-control" 
                                                           value="{{ $p3?->remarks }}" placeholder="Milling instructions...">
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
                                    Update
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
            const selectedOpt = $(this).find('option:selected');
            const phases = selectedOpt.data('phases') || [];

            $('.phase-checkbox-col').each(function() {
                const phaseId = parseInt($(this).find('.phase-selector-checkbox').val(), 10);
                if (phases.includes(phaseId) || phases.includes(String(phaseId))) {
                    $(this).show();
                } else {
                    $(this).hide();
                    $(this).find('.phase-selector-checkbox').prop('checked', false);
                }
            });

            syncPhaseTabsWithCheckboxes();
        });

        // Click anywhere in phase box to toggle checkbox
        $(document).on('click', '.phase-checkbox-box', function(e) {
            if (!$(e.target).is('input') && !$(e.target).is('label')) {
                $(this).find('.phase-selector-checkbox').trigger('click');
            }
        });

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

        // Initialize display
        $('#company_location_id').trigger('change');
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
