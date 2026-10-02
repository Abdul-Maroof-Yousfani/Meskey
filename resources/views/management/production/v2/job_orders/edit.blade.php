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
                    <form action="{{ route('production.job-orders.update', $jobOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data" novalidate>
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
                                        <input type="hidden" name="current_stage" id="current_stage_input" value="{{ $jobOrder->current_stage ?? 1 }}">
                                        <span class="badge badge-light border px-2 py-1 font-weight-semibold" id="selectedPhasesCountBadge">0 Phases Selected</span>
                                    </div>
                                    <div class="row" id="phasesCheckboxRow">
                                        @foreach($allPhases as $phase)
                                            @php
                                                $isPhaseChecked = in_array((int)$phase->id, array_map('intval', (array)$jobActivePhases));
                                            @endphp
                                            <div class="col-md-4 mb-2 phase-checkbox-col" id="col-chk-phase{{ $phase->id }}">
                                                <div class="phase-checkbox-box p-2 border rounded bg-white d-flex align-items-center justify-content-between {{ $isPhaseChecked ? 'is-selected' : '' }}">
                                                    <div class="custom-control custom-switch mr-2">
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
                                        $p1Params = $jobOrder->phase1Parameters;
                                        $selectedP1AttrIds = $p1Params ? $p1Params->pluck('production_attribute_id')->filter()->toArray() : [];
                                    @endphp
                                    <div class="tab-pane fade" id="tab-phase1" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Drying Mode</label>
                                                    <select name="p1_drying_mode" class="form-control">
                                                        <option value="batch" {{ ($p1?->drying_mode == 'batch') ? 'selected' : '' }}>Batch Drying</option>
                                                        <option value="series" {{ ($p1?->drying_mode == 'series') ? 'selected' : '' }}>Series Drying</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
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

                                            <!-- Dynamic Parameters from Production Attributes (Multi-Select) -->
                                            <div class="col-12 mt-2">
                                                <div class="card border mb-2 shadow-none" style="background-color: #f8f9fa; border-radius: 6px;">
                                                    <div class="card-body p-2">
                                                        <div class="form-group mb-0">
                                                            <label class="font-weight-bold text-dark mb-1">
                                                                <i class="ft-sliders mr-1 text-primary"></i> Select Parameters / Attributes:
                                                            </label>
                                                            <select class="form-control select2" id="phase1_attribute_select" multiple="multiple" style="width: 100%;" data-placeholder="-- Select Parameters (Multi-Select) --">
                                                                @foreach ($attributes as $attr)
                                                                    <option value="{{ $attr->id }}"
                                                                        data-id="{{ $attr->id }}"
                                                                        data-key="{{ $attr->key }}"
                                                                        data-type="{{ $attr->type }}"
                                                                        data-slug="{{ $attr->slug }}"
                                                                        {{ in_array($attr->id, $selectedP1AttrIds) ? 'selected' : '' }}>
                                                                        {{ $attr->key }} ({{ ucfirst($attr->type) }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <small class="text-muted d-block mt-1">
                                                                <i class="ft-info mr-1"></i>Select attributes from the list above. Each selected attribute will automatically be added to the bottom list.
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-1 mt-2">
                                                    <div>
                                                        <h6 class="mb-0 font-weight-bold">
                                                            <i class="ft-list mr-1 text-primary"></i>Phase 1 Parameters List
                                                        </h6>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger mr-1" id="btn-clear-phase1-params" style="{{ ($p1Params && $p1Params->count() > 0) ? '' : 'display: none;' }}">
                                                            <i class="ft-trash-2 mr-1"></i>Clear All
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped" id="phase1-params-table">
                                                        <thead class="bg-light text-center">
                                                            <tr>
                                                                <th style="width: 35%;">Parameter Key <span class="text-danger">*</span></th>
                                                                <th style="width: 25%;">Type</th>
                                                                <th style="width: 30%;">Parameter Value <span class="text-danger">*</span></th>
                                                                <th style="width: 10%;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="phase1-params-tbody">
                                                            @forelse ($p1Params ?? [] as $idx => $param)
                                                                <tr class="param-row" data-index="{{ $idx }}" data-attribute-id="{{ $param->production_attribute_id ?? '' }}">
                                                                    <td>
                                                                        <input type="hidden" name="phase1_parameters[{{ $idx }}][production_attribute_id]" value="{{ $param->production_attribute_id ?? '' }}">
                                                                        <input type="text" name="phase1_parameters[{{ $idx }}][key]" class="form-control form-control-sm text-monospace bg-light font-weight-bold"
                                                                            value="{{ $param->key ?? '' }}" readonly required>
                                                                    </td>
                                                                    <td>
                                                                        <select name="phase1_parameters[{{ $idx }}][type]" class="form-control form-control-sm bg-light" style="pointer-events: none;">
                                                                            <option value="text" {{ ($param->type ?? 'text') == 'text' ? 'selected' : '' }}>Text</option>
                                                                            <option value="number" {{ ($param->type ?? '') == 'number' ? 'selected' : '' }}>Number</option>
                                                                            <option value="percentage" {{ ($param->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                                                            <option value="temperature" {{ ($param->type ?? '') == 'temperature' ? 'selected' : '' }}>Temperature (°C)</option>
                                                                            <option value="time" {{ ($param->type ?? '') == 'time' ? 'selected' : '' }}>Time / Duration</option>
                                                                            <option value="boolean" {{ ($param->type ?? '') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="phase1_parameters[{{ $idx }}][value]" class="form-control form-control-sm param-value-input"
                                                                            placeholder="Enter parameter value" value="{{ $param->value ?? '' }}" required>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-phase1-param-row" data-id="{{ $param->production_attribute_id ?? '' }}" title="Remove">
                                                                            <i class="ft-trash font-medium-2"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr id="no-phase1-params-row">
                                                                    <td colspan="4" class="text-center text-muted py-4">
                                                                        <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to Phase 1.
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 2 -->
                                    @php
                                        $p2 = $jobOrder->productionPhase2->first();
                                        $processType = $p2?->process_type ?? 'steaming';
                                        $p2Params = $jobOrder->phase2Parameters;
                                        $selectedP2AttrIds = $p2Params ? $p2Params->pluck('production_attribute_id')->filter()->toArray() : [];
                                    @endphp
                                    <div class="tab-pane fade" id="tab-phase2" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Process Selection <span class="text-danger">*</span></label>
                                                    <select name="p2_process_type" id="p2_process_type" class="form-control">
                                                        <option value="steaming" {{ $processType == 'steaming' ? 'selected' : '' }}>Steaming (White / Sella Steam)</option>
                                                        <option value="parboiling" {{ $processType == 'parboiling' ? 'selected' : '' }}>Parboiling (Soak & Boil)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Steaming Fields -->
                                            <div class="col-md-6 p2-steaming-field {{ $processType == 'parboiling' ? 'd-none' : '' }}">
                                                <div class="form-group">
                                                    <label>Steam Type</label>
                                                    <select name="p2_steam_type" class="form-control">
                                                        <option value="single_steam" {{ ($p2?->steam_type == 'single_steam') ? 'selected' : '' }}>Single Steam</option>
                                                        <option value="double_steam" {{ ($p2?->steam_type == 'double_steam') ? 'selected' : '' }}>Double Steam</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Parboiling Fields -->
                                            <div class="col-md-6 p2-parboiling-field {{ $processType != 'parboiling' ? 'd-none' : '' }}">
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

                                            <!-- Dynamic Parameters from Production Attributes (Multi-Select) -->
                                            <div class="col-12 mt-2">
                                                <div class="card border mb-2 shadow-none" style="background-color: #f8f9fa; border-radius: 6px;">
                                                    <div class="card-body p-2">
                                                        <div class="form-group mb-0">
                                                            <label class="font-weight-bold text-dark mb-1">
                                                                <i class="ft-sliders mr-1 text-primary"></i> Select Parameters / Attributes:
                                                            </label>
                                                            <select class="form-control select2" id="phase2_attribute_select" multiple="multiple" style="width: 100%;" data-placeholder="-- Select Parameters (Multi-Select) --">
                                                                @foreach ($attributes as $attr)
                                                                    <option value="{{ $attr->id }}"
                                                                        data-id="{{ $attr->id }}"
                                                                        data-key="{{ $attr->key }}"
                                                                        data-type="{{ $attr->type }}"
                                                                        data-slug="{{ $attr->slug }}"
                                                                        {{ in_array($attr->id, $selectedP2AttrIds) ? 'selected' : '' }}>
                                                                        {{ $attr->key }} ({{ ucfirst($attr->type) }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <small class="text-muted d-block mt-1">
                                                                <i class="ft-info mr-1"></i>Select attributes from the list above. Each selected attribute will automatically be added to the bottom list.
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mb-1 mt-2">
                                                    <div>
                                                        <h6 class="mb-0 font-weight-bold">
                                                            <i class="ft-list mr-1 text-primary"></i>Phase 2 Parameters List
                                                        </h6>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger mr-1" id="btn-clear-phase2-params" style="{{ ($p2Params && $p2Params->count() > 0) ? '' : 'display: none;' }}">
                                                            <i class="ft-trash-2 mr-1"></i>Clear All
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped" id="phase2-params-table">
                                                        <thead class="bg-light text-center">
                                                            <tr>
                                                                <th style="width: 35%;">Parameter Key <span class="text-danger">*</span></th>
                                                                <th style="width: 25%;">Type</th>
                                                                <th style="width: 30%;">Parameter Value <span class="text-danger">*</span></th>
                                                                <th style="width: 10%;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="phase2-params-tbody">
                                                            @forelse ($p2Params ?? [] as $idx => $param)
                                                                <tr class="param-row" data-index="{{ $idx }}" data-attribute-id="{{ $param->production_attribute_id ?? '' }}">
                                                                    <td>
                                                                        <input type="hidden" name="phase2_parameters[{{ $idx }}][production_attribute_id]" value="{{ $param->production_attribute_id ?? '' }}">
                                                                        <input type="text" name="phase2_parameters[{{ $idx }}][key]" class="form-control form-control-sm text-monospace bg-light font-weight-bold"
                                                                            value="{{ $param->key ?? '' }}" readonly required>
                                                                    </td>
                                                                    <td>
                                                                        <select name="phase2_parameters[{{ $idx }}][type]" class="form-control form-control-sm bg-light" style="pointer-events: none;">
                                                                            <option value="text" {{ ($param->type ?? 'text') == 'text' ? 'selected' : '' }}>Text</option>
                                                                            <option value="number" {{ ($param->type ?? '') == 'number' ? 'selected' : '' }}>Number</option>
                                                                            <option value="percentage" {{ ($param->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                                                            <option value="temperature" {{ ($param->type ?? '') == 'temperature' ? 'selected' : '' }}>Temperature (°C)</option>
                                                                            <option value="time" {{ ($param->type ?? '') == 'time' ? 'selected' : '' }}>Time / Duration</option>
                                                                            <option value="boolean" {{ ($param->type ?? '') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="phase2_parameters[{{ $idx }}][value]" class="form-control form-control-sm param-value-input"
                                                                            placeholder="Enter parameter value" value="{{ $param->value ?? '' }}" required>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-phase2-param-row" data-id="{{ $param->production_attribute_id ?? '' }}" title="Remove">
                                                                            <i class="ft-trash font-medium-2"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr id="no-phase2-params-row">
                                                                    <td colspan="4" class="text-center text-muted py-4">
                                                                        <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to Phase 2.
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB: PHASE 3 - OLD PRODUCTION FLOW / PACKING & MILLING -->
                                    <div class="tab-pane fade" id="tab-phase3" role="tabpanel">
                                        <div class="row">
                                            <!-- Specifications Section -->
                                            <div class="col-md-12" id="specificationsSection" style="display: {{ ($jobOrder->specifications && $jobOrder->specifications->count()) ? 'block' : 'none' }};">
                                                <h6 class="header-heading-sepration">Specifications</h6>
                                                <div id="productSpecs">
                                                    @if($jobOrder->specifications && $jobOrder->specifications->count())
                                                        <div class="specifications-table">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-striped">
                                                                    <thead class="thead-dark">
                                                                        <tr>
                                                                            <th width="40%">Specification Name</th>
                                                                            <th width="30%">Value</th>
                                                                            <th width="30%">UOM</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($jobOrder->specifications as $index => $spec)
                                                                            <tr>
                                                                                <td>
                                                                                    <strong>{{ $spec->spec_name }}</strong>
                                                                                    <input type="hidden" name="specifications[{{ $index }}][product_slab_type_id]" value="{{ $spec->product_slab_type_id }}">
                                                                                    <input type="hidden" name="specifications[{{ $index }}][spec_name]" value="{{ $spec->spec_name }}">
                                                                                    <input type="hidden" name="specifications[{{ $index }}][uom]" value="{{ $spec->uom }}">
                                                                                </td>
                                                                                <td>
                                                                                    <fieldset>
                                                                                        <div class="input-group">
                                                                                            <input type="text" name="specifications[{{ $index }}][spec_value]" value="{{ $spec->spec_value ?? 0 }}" class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                                                                            <div class="input-group-prepend">
                                                                                                <button class="btn btn-secondary" type="button">{{ $spec->productSlabType->qc_symbol ?? ($spec->uom ?? 'N/A') }}</button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </fieldset>
                                                                                </td>
                                                                                <td>
                                                                                    <select name="specifications[{{ $index }}][value_type]" class="form-control">
                                                                                        <option {{ $spec->value_type == 'min' ? 'selected' : '' }} value="min">Minimum</option>
                                                                                        <option {{ $spec->value_type == 'max' ? 'selected' : '' }} value="max">Maximum</option>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                                                            <i class="ft-info mr-1"></i>
                                                            <strong>No specifications found!</strong> Please select a commodity first!
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Crop Year:</label>
                                                    <select name="crop_year_id" class="form-control select2" id="cropYearSelect">
                                                        <option value="">Select Crop Year</option>
                                                        @foreach($cropYears as $cropYear)
                                                            <option {{ $jobOrder->crop_year_id == $cropYear->id ? 'selected' : '' }} value="{{ $cropYear->id }}">{{ $cropYear->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Other Specification:</label>
                                                    <textarea name="other_specifications" class="form-control" rows="4" placeholder="Enter other specifications...">{{ $jobOrder->other_specifications }}</textarea>
                                                </div>
                                            </div>

                                            <!-- Packing Details Section -->
                                            <div class="col-md-12">
                                                <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                                                    Packing Details
                                                    <button type="button" class="btn btn-sm btn-success" id="addPackingItem">
                                                        <i class="ft-plus"></i> Add More Packing Item
                                                    </button>
                                                </h6>

                                                <div id="export-order-quantity-info" class="mb-3" style="display: none;"></div>

                                                <div id="packingItems">
                                                    @php
                                                        $packingItemsList = ($jobOrder->packingItems && $jobOrder->packingItems->count() > 0) 
                                                            ? $jobOrder->packingItems 
                                                            : [null];
                                                    @endphp
                                                    @foreach($packingItemsList as $packingIndex => $packingItem)
                                                        <div class="packing-item card border shadow-sm mb-4" data-index="{{ $packingIndex }}">
                                                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                                                <h6 class="mb-0 font-weight-bold text-primary packing-item-title">Packing Item #{{ $loop->iteration }}</h6>
                                                                <button type="button" class="btn btn-sm btn-outline-danger remove-packing-item" style="{{ count($packingItemsList) > 1 ? '' : 'display:none;' }}">
                                                                    <i class="ft-trash-2"></i> Remove Item
                                                                </button>
                                                            </div>
                                                            <div class="card-body p-3">
                                                                <div class="row">
                                                                    <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Company Location:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][company_location_id]" class="form-control select2">
                                                                        <option value="">Select Location</option>
                                                                        @foreach($companyLocations as $location)
                                                                            <option data-code="{{ $location->code }}" value="{{ $location->id }}" {{ ($packingItem && $packingItem->company_location_id == $location->id) ? 'selected' : '' }}>
                                                                                {{ $location->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Brand:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][brand_id]" class="form-control select2">
                                                                        <option value="">Select Brand</option>
                                                                        @foreach($brands as $brand)
                                                                            <option value="{{ $brand->id }}" {{ ($packingItem && $packingItem->brand_id == $brand->id) ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Bag Type/Product:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][bag_product_id]" class="form-control select2">
                                                                        <option value="">Select Bag Type/Product</option>
                                                                        @foreach($bagProducts as $bagProduct)
                                                                            <option value="{{ $bagProduct->id }}" {{ ($packingItem && $packingItem->bag_product_id == $bagProduct->id) ? 'selected' : '' }}>{{ $bagProduct->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Bag Condition:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][bag_condition_id]" class="form-control select2">
                                                                        <option value="">Select Condition</option>
                                                                        @foreach($bagConditions as $condition)
                                                                            <option value="{{ $condition->id }}" {{ ($packingItem && $packingItem->bag_condition_id == $condition->id) ? 'selected' : '' }}>{{ $condition->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Bag Color:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][bag_color_id]" class="form-control select2">
                                                                        <option value="">Select Color</option>
                                                                        @foreach($bagColors as $color)
                                                                            <option value="{{ $color->id }}" {{ ($packingItem && $packingItem->bag_color_id == $color->id) ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Thread Color:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][thread_color_id]" class="form-control select2">
                                                                        <option value="">Select Color</option>
                                                                        @foreach($bagColors as $color)
                                                                            <option value="{{ $color->id }}" {{ ($packingItem && $packingItem->thread_color_id == $color->id) ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Stitching:</label>
                                                                    <select name="packing_items[{{ $packingIndex }}][stitching_id]" class="form-control select2">
                                                                        <option value="">Select Stitching</option>
                                                                        @foreach($stitchings as $stitching)
                                                                            <option value="{{ $stitching->id }}" {{ ($packingItem && $packingItem->stitching_id == $stitching->id) ? 'selected' : '' }}>{{ $stitching->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Packing Size (kg):</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][bag_size]" class="form-control bag-size" step="0.01" value="{{ $packingItem?->bag_size }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>No. of Bags:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][no_of_bags]" class="form-control no-of-bags" value="{{ $packingItem?->no_of_bags }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Extra Bags:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][extra_bags]" class="form-control extra-bags" value="{{ $packingItem?->extra_bags ?? 0 }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Extra Bags %:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][extra_bags_percentage]" class="form-control extra-bags-percentage" step="0.01" value="{{ $packingItem?->extra_bags_percentage ?? 0 }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Empty Bags:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][empty_bags]" class="form-control empty-bags" value="{{ $packingItem?->empty_bags ?? 0 }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Total Bags:</label>
                                                                    <input type="number" min="0" name="packing_items[{{ $packingIndex }}][total_bags]" class="form-control total-bags" readonly value="{{ $packingItem?->total_bags }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Total KGs:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][total_kgs]" class="form-control total-kgs" step="0.01" readonly value="{{ $packingItem?->total_kgs }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Metric Tons:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][metric_tons]" class="form-control metric-tons" step="0.01" min="0" readonly value="{{ $packingItem?->metric_tons }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="form-group">
                                                                    <label>Stuffing (MTs):</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][stuffing_in_container]" value="{{ $packingItem?->stuffing_in_container ?? 0 }}" class="form-control stuffing" step="any" min="0">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>No. of Containers:</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][no_of_containers]" class="form-control containers" value="{{ $packingItem?->no_of_containers ?? 0 }}" min="0">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Min Weight Empty Bags (g):</label>
                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][min_weight_empty_bags]" class="form-control min-weight" value="{{ $packingItem?->min_weight_empty_bags ?? 0 }}" min="0" step="0.01">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Delivery Date:</label>
                                                                    <input type="date" name="packing_items[{{ $packingIndex }}][delivery_date]" class="form-control" value="{{ $packingItem?->delivery_date ? $packingItem->delivery_date->format('Y-m-d') : '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>Fumigation By:</label>
                                                                    @php
                                                                        $selectedFumigation = ($packingItem && $packingItem->fumigation_company_id) 
                                                                            ? (is_array($packingItem->fumigation_company_id) ? $packingItem->fumigation_company_id : json_decode($packingItem->fumigation_company_id, true) ?? [])
                                                                            : [];
                                                                    @endphp
                                                                    <select name="packing_items[{{ $packingIndex }}][fumigation_company_id][]" class="form-control select2" multiple>
                                                                        <option value="">Select Fumigation Company</option>
                                                                        @foreach($fumigationCompanies as $company)
                                                                            <option value="{{ $company->id }}" {{ in_array($company->id, (array)$selectedFumigation) ? 'selected' : '' }}>{{ $company->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <!-- Master Packing Section -->
                                                            <div class="col-md-12 mt-3">
                                                                <div class="card border">
                                                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                                                        <h6 class="mb-0 font-weight-bold" style="font-size: 13px;">Master Packing</h6>
                                                                        <button type="button" class="btn btn-sm btn-primary add-sub-packing-item" data-index="{{ $packingIndex }}">
                                                                            <i class="ft-plus"></i> Add Master Packing Item
                                                                        </button>
                                                                    </div>
                                                                    <div class="card-body p-0">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-bordered table-sm mb-0">
                                                                                <thead class="thead-light">
                                                                                    <tr>
                                                                                        <th style="min-width: 170px;">Bag Type/Product</th>
                                                                                        <th style="min-width: 120px;">Bag Size</th>
                                                                                        <th style="min-width: 130px;">Primary Bags/Master</th>
                                                                                        <th style="min-width: 110px;">Packing Size (kg)</th>
                                                                                        <th style="min-width: 90px;">No. of Bags</th>
                                                                                        <th style="min-width: 90px;">Empty Bags</th>
                                                                                        <th style="min-width: 80px;">Extra Bags</th>
                                                                                        <th style="min-width: 90px;">Extra Bags %</th>
                                                                                        <th style="min-width: 110px;">Empty Bag Wt (g)</th>
                                                                                        <th style="min-width: 90px;">Total Bags</th>
                                                                                        <th style="min-width: 120px;">Stitching</th>
                                                                                        <th style="min-width: 110px;">Bag Color</th>
                                                                                        <th style="min-width: 120px;">Brand</th>
                                                                                        <th style="min-width: 110px;">Thread Color</th>
                                                                                        <th style="min-width: 120px;">Attachment</th>
                                                                                        <th style="min-width: 50px;" class="text-center">Action</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody class="sub-packing-items-container" data-index="{{ $packingIndex }}">
                                                                                    @if($packingItem && $packingItem->subItems && $packingItem->subItems->count() > 0)
                                                                                        @foreach($packingItem->subItems as $subIndex => $subItem)
                                                                                            <tr class="sub-packing-item-row">
                                                                                                <td>
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_product_id]"
                                                                                                        class="form-control form-control-sm select2 sub-bag-product">
                                                                                                        <option value="">Select Bag Type/Product</option>
                                                                                                        @foreach($bagProducts as $bagProduct)
                                                                                                            <option value="{{ $bagProduct->id }}" {{ $subItem->bag_product_id == $bagProduct->id ? 'selected' : '' }}>{{ $bagProduct->name }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_size_id]"
                                                                                                        class="form-control form-control-sm select2 sub-bag-size">
                                                                                                        <option value="">Select Size</option>
                                                                                                        @foreach($sizes as $size)
                                                                                                            <option value="{{ $size->id }}" {{ $subItem->bag_size_id == $size->id ? 'selected' : '' }}>{{ $size->size }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][no_of_primary_bags]"
                                                                                                        class="form-control form-control-sm sub-no-of-primary-bags"
                                                                                                        value="{{ $subItem->no_of_primary_bags }}"
                                                                                                        placeholder="Primary bags fit in master bag">
                                                                                                </td>
                                                                                                <td>
                                                                                                    @php
                                                                                                        $calcSubSize = ($packingItem->bag_size ?? 0) * ($subItem->no_of_primary_bags ?? 0);
                                                                                                    @endphp
                                                                                                    <input type="number" step="0.01" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][packing_size]" readonly class="form-control form-control-sm sub-calculated-packing-size" placeholder="Auto calc" value="{{ $calcSubSize }}">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][no_of_bags]"
                                                                                                        class="form-control form-control-sm sub-no-of-bags" readonly placeholder="Auto calc" value="{{ $subItem->no_of_bags }}">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][empty_bags]"
                                                                                                        class="form-control form-control-sm sub-empty-bags" value="{{ $subItem->empty_bags ?? 0 }}" min="0">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][extra_bags]"
                                                                                                        class="form-control form-control-sm sub-extra-bags" value="{{ $subItem->extra_bags ?? 0 }}" min="0">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][extra_bags_percentage]"
                                                                                                        class="form-control form-control-sm sub-extra-bags-percentage" value="{{ $subItem->extra_bags_percentage ?? 0 }}" min="0" step="0.01">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][empty_bag_weight]"
                                                                                                        class="form-control form-control-sm sub-empty-bag-weight" value="{{ $subItem->empty_bag_weight ?? 0 }}" min="0" step="0.01">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][total_bags]"
                                                                                                        class="form-control form-control-sm sub-total-bags" readonly value="{{ $subItem->total_bags ?? 0 }}">
                                                                                                </td>
                                                                                                <td>
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][stitching_id]"
                                                                                                        class="form-control form-control-sm select2 sub-stitching">
                                                                                                        <option value="">Select Stitching</option>
                                                                                                        @foreach($stitchings as $stitching)
                                                                                                            <option value="{{ $stitching->id }}" {{ $subItem->stitching_id == $stitching->id ? 'selected' : '' }}>{{ $stitching->name }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td class="col-1">
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_color_id]"
                                                                                                        class="form-control form-control-sm select2 sub-bag-color">
                                                                                                        <option value="">Select Color</option>
                                                                                                        @foreach($bagColors as $color)
                                                                                                            <option value="{{ $color->id }}" {{ $subItem->bag_color_id == $color->id ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td class="col-1">
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][brand_id]"
                                                                                                        class="form-control form-control-sm select2 sub-brand">
                                                                                                        <option value="">Select Brand</option>
                                                                                                        @foreach($brands as $brand)
                                                                                                            <option value="{{ $brand->id }}" {{ $subItem->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td class="col-1">
                                                                                                    <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][thread_color_id]"
                                                                                                        class="form-control form-control-sm select2 sub-thread-color">
                                                                                                        <option value="">Select Color</option>
                                                                                                        @foreach($bagColors as $color)
                                                                                                            <option value="{{ $color->id }}" {{ $subItem->thread_color_id == $color->id ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </td>
                                                                                                <td>
                                                                                                    @if($subItem->attachment)
                                                                                                        <a href="{{ asset('storage/' . $subItem->attachment) }}" target="_blank" class="btn btn-sm btn-info mb-1 d-inline-block">View</a>
                                                                                                        <input type="hidden" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][existing_attachment]" value="{{ $subItem->attachment }}">
                                                                                                    @endif
                                                                                                    <input type="file" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][attachment]"
                                                                                                        class="form-control form-control-sm sub-attachment">
                                                                                                </td>
                                                                                                                              <td class="text-center">
                                                                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-sub-packing-item" title="Remove">
                                                                                                        <i class="ft-trash-2"></i>
                                                                                                    </button>
                                                                                                </td>
                                                                                            </tr>
                                                                                        @endforeach
                                                                                    @endif
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                                </div>
                                            </div>

                                            <!-- Operational Details Section -->
                                            <div class="col-md-12">
                                                <h6 class="header-heading-sepration">Operational Details</h6>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Inspection By:</label>
                                                            @php
                                                                $selectedInspection = ($jobOrder->inspection_company_id) 
                                                                    ? (is_array($jobOrder->inspection_company_id) ? $jobOrder->inspection_company_id : json_decode($jobOrder->inspection_company_id, true) ?? [])
                                                                    : [];
                                                            @endphp
                                                            <select name="inspection_company_id[]" class="form-control select2" multiple>
                                                                <option value="">Select Inspection Company</option>
                                                                @foreach($inspectionCompanies as $company)
                                                                    <option value="{{ $company->id }}" {{ in_array($company->id, (array)$selectedInspection) ? 'selected' : '' }}>{{ $company->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Load From/Location:</label>
                                                            @php
                                                                $selectedArrival = ($jobOrder->arrival_locations) 
                                                                    ? (is_array($jobOrder->arrival_locations) ? $jobOrder->arrival_locations : json_decode($jobOrder->arrival_locations, true) ?? [])
                                                                    : [];
                                                            @endphp
                                                            <select name="arrival_locations[]" class="form-control select2" multiple>
                                                                @foreach($arrivalLocations as $location)
                                                                    <option value="{{ $location->id }}" {{ in_array($location->id, (array)$selectedArrival) ? 'selected' : '' }}>{{ $location->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Loading Date:</label>
                                                            <input type="date" name="loading_date" class="form-control" value="{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('Y-m-d') : '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label>Packing Description:</label>
                                                            <textarea name="packing_description" class="form-control" rows="4" placeholder="Packing instructions or notes...">{{ $jobOrder->packing_description }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Container Protection & Packing Materials -->
                                            <div class="col-md-12" id="containerProtectionSection">
                                                <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                                                    Container Protection & Packing Materials
                                                    <button type="button" class="btn btn-sm btn-success" id="addContainerProtectionItem">
                                                        <i class="ft-plus"></i> Add More
                                                    </button>
                                                </h6>
                                                <div id="containerProtectionItems">
                                                    @if($jobOrder->containerProtectionItems && $jobOrder->containerProtectionItems->count() > 0)
                                                        @foreach($jobOrder->containerProtectionItems as $index => $product)
                                                            <div class="container-protection-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                                                                <div class="col-md-5">
                                                                    <div class="form-group">
                                                                        <label>Product:</label>
                                                                        <select name="container_protection_items[{{ $index }}][product_id]" class="form-control select2 container-protection-product">
                                                                            <option value="">Select Product</option>
                                                                            @foreach($containerProtectionProducts as $containerProduct)
                                                                                <option value="{{ $containerProduct->id }}" {{ $product->id == $containerProduct->id ? 'selected' : '' }}>{{ $containerProduct->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Quantity Per Container:</label>
                                                                        <input type="number" name="container_protection_items[{{ $index }}][quantity_per_container]" 
                                                                            class="form-control container-protection-quantity" step="0.01" min="0" placeholder="Enter Quantity" value="{{ $product->pivot->quantity_per_container ?? 0 }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label>&nbsp;</label>
                                                                        <button type="button" class="btn btn-sm btn-danger remove-container-protection-item form-control">
                                                                            Remove
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
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

<!-- Hidden Template for Container Protection & Packing Materials -->
<div class="container-protection-item-template d-none">
    <div class="container-protection-item row border-bottom pb-3 mb-3 w-100 mx-auto">
        <div class="col-md-5">
            <div class="form-group">
                <label>Product:</label>
                <select name="container_protection_items[INDEX][product_id]"
                    class="form-control select2 container-protection-product">
                    <option value="">Select Product</option>
                    @foreach($containerProtectionProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Quantity Per Container:</label>
                <input type="number" name="container_protection_items[INDEX][quantity_per_container]"
                    class="form-control container-protection-quantity" step="0.01" min="0" placeholder="Enter Quantity">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-sm btn-danger remove-container-protection-item form-control">
                    Remove
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Template for Sub Packing Item -->
<table class="sub-packing-item-template d-none">
    <tbody>
        <tr class="sub-packing-item-row">
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_product_id]"
                    class="form-control form-control-sm sub-select2 sub-bag-product">
                    <option value="">Select Bag Type/Product</option>
                    @foreach($bagProducts as $bagProduct)
                        <option value="{{ $bagProduct->id }}">{{ $bagProduct->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_size_id]"
                    class="form-control form-control-sm sub-select2 sub-bag-size">
                    <option value="">Select Size</option>
                    @foreach($sizes as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_primary_bags]"
                    class="form-control form-control-sm sub-no-of-primary-bags"
                    placeholder="Primary bags fit in master bag">
            </td>
            <td>
                <input type="number" step="0.01" name="packing_items[INDEX][sub_items][SUB_INDEX][packing_size]" readonly class="form-control form-control-sm sub-calculated-packing-size" placeholder="Auto calc">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_bags]"
                    class="form-control form-control-sm sub-no-of-bags" readonly placeholder="Auto calc">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bags]"
                    class="form-control form-control-sm sub-empty-bags" value="0" min="0">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags]"
                    class="form-control form-control-sm sub-extra-bags" value="0" min="0">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags_percentage]"
                    class="form-control form-control-sm sub-extra-bags-percentage" value="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bag_weight]"
                    class="form-control form-control-sm sub-empty-bag-weight" value="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][total_bags]"
                    class="form-control form-control-sm sub-total-bags" readonly value="0">
            </td>
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][stitching_id]"
                    class="form-control form-control-sm sub-select2 sub-stitching">
                    <option value="">Select Stitching</option>
                    @foreach($stitchings as $stitching)
                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_color_id]"
                    class="form-control form-control-sm sub-select2 sub-bag-color">
                    <option value="">Select Color</option>
                    @foreach($bagColors as $color)
                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][brand_id]"
                    class="form-control form-control-sm sub-select2 sub-brand">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][thread_color_id]"
                    class="form-control form-control-sm sub-select2 sub-thread-color">
                    <option value="">Select Color</option>
                    @foreach($bagColors as $color)
                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="file" name="packing_items[INDEX][sub_items][SUB_INDEX][attachment]"
                    class="form-control form-control-sm sub-attachment">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-sub-packing-item" title="Remove">
                    <i class="ft-trash-2"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        if (typeof $('.select2').select2 === 'function') {
            $('.select2').not('.sub-packing-item-template .select2').not('.container-protection-item-template .select2').select2({ width: '100%' });
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
            let checkedPhases = [];

            $('.phase-selector-checkbox').each(function() {
                const phaseNum = parseInt($(this).val());
                const isChecked = $(this).is(':checked');
                const isVisible = $(this).closest('.phase-checkbox-col').is(':visible');
                const tabNavId = '#tab-nav-phase' + phaseNum;
                const tabPaneId = '#tab-phase' + phaseNum;
                const $box = $(this).closest('.phase-checkbox-box');

                if (isVisible && isChecked) {
                    $(tabNavId).show();
                    $box.addClass('is-selected');
                    checkedCount++;
                    checkedPhases.push(phaseNum);
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

            let currentSelectedStage = parseInt($('#current_stage_input').val()) || null;
            let runningStage = 1;
            if (currentSelectedStage && checkedPhases.includes(currentSelectedStage)) {
                runningStage = currentSelectedStage;
            } else if (checkedPhases.length > 0) {
                runningStage = Math.min(...checkedPhases);
            }
            $('#current_stage_input').val(runningStage);

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

        // ================= Phase 1 & 2 Helper =================
        function getPlaceholderForType(type) {
            switch (type) {
                case 'temperature':
                    return 'e.g. 65 °C';
                case 'time':
                    return 'e.g. 4 Hours or 20 Mins';
                case 'percentage':
                    return 'e.g. 15%';
                case 'number':
                    return 'e.g. 100';
                case 'boolean':
                    return 'e.g. Yes / No or True / False';
                default:
                    return 'Enter parameter value';
            }
        }

        // ================= Phase 1 Parameters =================
        let phase1ParamIndex = $('#phase1-params-tbody tr.param-row').length;

        function updatePhase1TableState() {
            const rowCount = $('#phase1-params-tbody tr.param-row').length;
            if (rowCount === 0) {
                if ($('#no-phase1-params-row').length === 0) {
                    $('#phase1-params-tbody').html(`
                        <tr id="no-phase1-params-row">
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to Phase 1.
                            </td>
                        </tr>
                    `);
                }
                $('#btn-clear-phase1-params').hide();
            } else {
                $('#no-phase1-params-row').remove();
                $('#btn-clear-phase1-params').show();
            }
        }

        function addPhase1AttributeRow(attrId, key, type, value = '') {
            $('#no-phase1-params-row').remove();
            const placeholder = getPlaceholderForType(type);

            const rowHtml = `
                <tr class="param-row" data-index="${phase1ParamIndex}" data-attribute-id="${attrId}">
                    <td>
                        <input type="hidden" name="phase1_parameters[${phase1ParamIndex}][production_attribute_id]" value="${attrId}">
                        <input type="text" name="phase1_parameters[${phase1ParamIndex}][key]" class="form-control form-control-sm text-monospace bg-light font-weight-bold"
                            value="${key}" readonly required>
                    </td>
                    <td>
                        <select name="phase1_parameters[${phase1ParamIndex}][type]" class="form-control form-control-sm bg-light" style="pointer-events: none;">
                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="percentage" ${type === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                            <option value="temperature" ${type === 'temperature' ? 'selected' : ''}>Temperature (°C)</option>
                            <option value="time" ${type === 'time' ? 'selected' : ''}>Time / Duration</option>
                            <option value="boolean" ${type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="phase1_parameters[${phase1ParamIndex}][value]" class="form-control form-control-sm param-value-input"
                            placeholder="${placeholder}" value="${value}" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-phase1-param-row" data-id="${attrId}" title="Remove">
                            <i class="ft-trash font-medium-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#phase1-params-tbody').append(rowHtml);
            phase1ParamIndex++;
            updatePhase1TableState();
        }

        $('#phase1_attribute_select').on('select2:select', function (e) {
            const attrId = e.params.data.id;
            const $option = $(e.params.data.element);
            const key = $option.data('key') || e.params.data.text;
            const type = $option.data('type') || 'text';

            if ($('#phase1-params-tbody tr[data-attribute-id="' + attrId + '"]').length === 0) {
                addPhase1AttributeRow(attrId, key, type);
                $('#phase1-params-tbody tr[data-attribute-id="' + attrId + '"] .param-value-input').focus();
            }
        });

        $('#phase1_attribute_select').on('select2:unselect', function (e) {
            const attrId = e.params.data.id;
            $('#phase1-params-tbody tr[data-attribute-id="' + attrId + '"]').remove();
            updatePhase1TableState();
        });

        $(document).on('click', '.btn-remove-phase1-param-row', function () {
            const attrId = $(this).data('id');
            $(this).closest('tr').remove();
            const currentVals = $('#phase1_attribute_select').val() || [];
            const newVals = currentVals.filter(v => String(v) !== String(attrId));
            $('#phase1_attribute_select').val(newVals).trigger('change');
            updatePhase1TableState();
        });

        $('#btn-clear-phase1-params').on('click', function () {
            $('#phase1-params-tbody').empty();
            $('#phase1_attribute_select').val(null).trigger('change');
            updatePhase1TableState();
        });

        // ================= Phase 2 Parameters =================
        let phase2ParamIndex = $('#phase2-params-tbody tr.param-row').length;

        function updatePhase2TableState() {
            const rowCount = $('#phase2-params-tbody tr.param-row').length;
            if (rowCount === 0) {
                if ($('#no-phase2-params-row').length === 0) {
                    $('#phase2-params-tbody').html(`
                        <tr id="no-phase2-params-row">
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to Phase 2.
                            </td>
                        </tr>
                    `);
                }
                $('#btn-clear-phase2-params').hide();
            } else {
                $('#no-phase2-params-row').remove();
                $('#btn-clear-phase2-params').show();
            }
        }

        function addPhase2AttributeRow(attrId, key, type, value = '') {
            $('#no-phase2-params-row').remove();
            const placeholder = getPlaceholderForType(type);

            const rowHtml = `
                <tr class="param-row" data-index="${phase2ParamIndex}" data-attribute-id="${attrId}">
                    <td>
                        <input type="hidden" name="phase2_parameters[${phase2ParamIndex}][production_attribute_id]" value="${attrId}">
                        <input type="text" name="phase2_parameters[${phase2ParamIndex}][key]" class="form-control form-control-sm text-monospace bg-light font-weight-bold"
                            value="${key}" readonly required>
                    </td>
                    <td>
                        <select name="phase2_parameters[${phase2ParamIndex}][type]" class="form-control form-control-sm bg-light" style="pointer-events: none;">
                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="percentage" ${type === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                            <option value="temperature" ${type === 'temperature' ? 'selected' : ''}>Temperature (°C)</option>
                            <option value="time" ${type === 'time' ? 'selected' : ''}>Time / Duration</option>
                            <option value="boolean" ${type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="phase2_parameters[${phase2ParamIndex}][value]" class="form-control form-control-sm param-value-input"
                            placeholder="${placeholder}" value="${value}" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-phase2-param-row" data-id="${attrId}" title="Remove">
                            <i class="ft-trash font-medium-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#phase2-params-tbody').append(rowHtml);
            phase2ParamIndex++;
            updatePhase2TableState();
        }

        $('#phase2_attribute_select').on('select2:select', function (e) {
            const attrId = e.params.data.id;
            const $option = $(e.params.data.element);
            const key = $option.data('key') || e.params.data.text;
            const type = $option.data('type') || 'text';

            if ($('#phase2-params-tbody tr[data-attribute-id="' + attrId + '"]').length === 0) {
                addPhase2AttributeRow(attrId, key, type);
                $('#phase2-params-tbody tr[data-attribute-id="' + attrId + '"] .param-value-input').focus();
            }
        });

        $('#phase2_attribute_select').on('select2:unselect', function (e) {
            const attrId = e.params.data.id;
            $('#phase2-params-tbody tr[data-attribute-id="' + attrId + '"]').remove();
            updatePhase2TableState();
        });

        $(document).on('click', '.btn-remove-phase2-param-row', function () {
            const attrId = $(this).data('id');
            $(this).closest('tr').remove();
            const currentVals = $('#phase2_attribute_select').val() || [];
            const newVals = currentVals.filter(v => String(v) !== String(attrId));
            $('#phase2_attribute_select').val(newVals).trigger('change');
            updatePhase2TableState();
        });

        $('#btn-clear-phase2-params').on('click', function () {
            $('#phase2-params-tbody').empty();
            $('#phase2_attribute_select').val(null).trigger('change');
            updatePhase2TableState();
        });

        // ==========================================
        // PHASE 3 (OLD PRODUCTION FLOW) JAVASCRIPT
        // ==========================================

        // Commodity / Product Selection Change -> Load Specifications
        $('#product_id').on('change', function () {
            var productId = $(this).val();
            if (productId) {
                $.get('{{ route("get.product_specs", "") }}/' + productId, function (data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#specificationsSection').hide();
            }
        });

        // Initialize state for export order
        let isInitialLoad = true;
        if($('#export_order_id').val()) {
            $('#product_id').prop('disabled', true);
            $('#product_id').next('.select2-container').css('pointer-events', 'none');
            if($('#hidden_product_id').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'hidden_product_id',
                    name: 'product_id',
                    value: $('#product_id').val()
                }).appendTo('form#ajaxSubmit');
            }
            // Fetch export order info without overwriting current data
            $.get('{{ url("production/get-export-order-details") }}/' + $('#export_order_id').val(), function(data) {
                let totalMt = data.total_eo_mt;
                let consumedMt = data.consumed_mt;
                let remainingMt = data.remaining_mt;
                let alertHtml = `
                    <div class="mb-2" style="color: #d9534f; font-weight: 500;">
                        <i class="ft-info mr-1"></i>
                        Export Order Quantity Info: Total: ${totalMt} MT | Consumed: ${consumedMt} MT | Remaining: ${remainingMt} MT
                    </div>
                `;
                $('#export-order-quantity-info').html(alertHtml).show();
            });
        }

        // Export Order Selection Change
        $('#export_order_id').on('change', function() {
            let id = $(this).val();
            if(!id) {
                $('#product_id').prop('disabled', false);
                $('#product_id').next('.select2-container').css('pointer-events', '');
                $('#hidden_product_id').remove();
                $('#export-order-quantity-info').hide();
                return;
            }

            $.get('{{ url("production/get-export-order-details") }}/' + id, function(data) {
                let totalMt = data.total_eo_mt;
                let consumedMt = data.consumed_mt;
                let remainingMt = data.remaining_mt;
                
                let alertHtml = `
                    <div class="mb-2" style="color: #d9534f; font-weight: 500;">
                        <i class="ft-info mr-1"></i>
                        Export Order Quantity Info: Total: ${totalMt} MT | Consumed: ${consumedMt} MT | Remaining: ${remainingMt} MT
                    </div>
                `;
                $('#export-order-quantity-info').html(alertHtml).show();

                if (data.ref_no) {
                    $('input[name="ref_no"]').val(data.ref_no);
                }
                if (data.other_specifications) {
                    $('textarea[name="other_specifications"]').val(data.other_specifications);
                }

                // Set product and make it readonly
                $('#product_id').val(data.product_id).trigger('change');
                $('#product_id').prop('disabled', true);
                $('#product_id').next('.select2-container').css('pointer-events', 'none');

                if($('#hidden_product_id').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'hidden_product_id',
                        name: 'product_id',
                        value: data.product_id
                    }).appendTo('form#ajaxSubmit');
                } else {
                    $('#hidden_product_id').val(data.product_id);
                }

                setTimeout(function() {
                    buildSpecsTable(data.specifications);
                }, 900);

                addPackingRowsFromExportOrder(data.packing_items);
            }).fail(function(xhr) {
                console.error('Export Order Details Error:', xhr.status, xhr.responseText);
            });
        });

        function buildSpecsTable(specs) {
            if(!specs || specs.length === 0) return;
            
            let html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="thead-dark"><tr><th width="40%">Specification Name</th><th width="30%">Value</th><th width="30%">UOM</th></tr></thead><tbody>';
            specs.forEach(function(spec, idx) {
                let specName = spec.product_slab_type ? spec.product_slab_type.name : spec.spec_name;
                let uom = spec.product_slab_type ? spec.product_slab_type.qc_symbol : spec.uom;
                let valueType = spec.value_type || 'min';
                
                html += `<tr>
                    <td>
                        <strong>${specName}</strong>
                        <input type="hidden" name="specifications[${idx}][product_slab_type_id]" value="${spec.product_slab_type_id}">
                        <input type="hidden" name="specifications[${idx}][spec_name]" value="${spec.spec_name}">
                        <input type="hidden" name="specifications[${idx}][uom]" value="${spec.uom}">
                    </td>
                    <td>
                        <fieldset>
                            <div class="input-group">
                                <input type="text" name="specifications[${idx}][spec_value]" value="${spec.spec_value || 0}" class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                <div class="input-group-prepend">
                                    <button class="btn btn-secondary" type="button">${uom || 'N/A'}</button>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                    <td>
                        <select name="specifications[${idx}][value_type]" class="form-control">
                            <option value="min" ${valueType === 'min' ? 'selected' : ''}>Minimum</option>
                            <option value="max" ${valueType === 'max' ? 'selected' : ''}>Maximum</option>
                        </select>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#productSpecs').html(html);
        }

        function addPackingRowsFromExportOrder(items) {
            if (!items || items.length === 0) return;
            let container = $('#packingItems');
            let templateRow = container.find('.packing-item').first().clone();
            
            templateRow.find('.select2-container').remove();
            templateRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            
            container.empty();

            items.forEach(function(item, index) {
                let row = templateRow.clone();
                row.find('.select2-container').remove();
                row.find('select').removeClass('select2-hidden-accessible').show();
                row.find('option').removeAttr('data-select2-id');
                
                row.attr('data-index', index);

                // Update input & select names
                row.find('input, select, textarea').each(function() {
                    let name = $(this).attr('name');
                    if(name) {
                        name = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', name);
                    }
                });

                function findBagProductIdByName(name, selectElement) {
                    if (!name) return null;
                    let id = null;
                    selectElement.find('option').each(function() {
                        if ($(this).text().trim().toLowerCase() === name.trim().toLowerCase()) {
                            id = $(this).val();
                            return false;
                        }
                    });
                    return id;
                }

                row.find(`select[name="packing_items[${index}][brand_id]"]`).val(item.brand_id);
                
                let bagProductSelect = row.find(`select[name="packing_items[${index}][bag_product_id]"]`);
                let mappedId = findBagProductIdByName(item.bag_type_name, bagProductSelect);
                if (mappedId) {
                    bagProductSelect.val(mappedId);
                } else {
                    bagProductSelect.val(item.bag_product_id);
                }

                row.find(`select[name="packing_items[${index}][bag_condition_id]"]`).val(item.bag_condition_id);
                row.find(`select[name="packing_items[${index}][bag_color_id]"]`).val(item.bag_color_id);
                row.find(`select[name="packing_items[${index}][thread_color_id]"]`).val(item.thread_color_id);
                row.find(`select[name="packing_items[${index}][stitching_id]"]`).val(item.stitching_id);
                
                row.find(`input[name="packing_items[${index}][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[${index}][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[${index}][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(item.extra_bags_percentage);
                row.find(`input[name="packing_items[${index}][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[${index}][total_bags]"]`).val(item.total_bags);
                row.find(`input[name="packing_items[${index}][total_kgs]"]`).val(item.total_kgs);
                row.find(`input[name="packing_items[${index}][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[${index}][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[${index}][no_of_containers]"]`).val(item.no_of_containers);
                row.find(`input[name="packing_items[${index}][min_weight_empty_bags]"]`).val(item.min_weight_empty_bags);
                
                if (item.fumigation_company_id) {
                    row.find(`select[name="packing_items[${index}][fumigation_company_id][]"]`).val(item.fumigation_company_id);
                }
                
                let subContainer = row.find('.sub-packing-items-container');
                subContainer.attr('data-index', index);
                subContainer.empty();
                row.find('.add-sub-packing-item').attr('data-index', index);

                container.append(row);
                
                row.find('select.select2').each(function() {
                    $(this).select2({ width: '100%' });
                    if ($(this).val()) {
                        $(this).trigger('change');
                    }
                });

                if (item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        let subRowHtml = $('.sub-packing-item-template tbody').html();
                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);
                        
                        let subBagProductSelect = subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`);
                        let subMappedId = findBagProductIdByName(sub.bag_type_name, subBagProductSelect);
                        if (subMappedId) {
                            subBagProductSelect.val(subMappedId);
                        } else {
                            subBagProductSelect.val(sub.bag_product_id);
                        }

                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][stitching_id]"]`).val(sub.stitching_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_color_id]"]`).val(sub.bag_color_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][brand_id]"]`).val(sub.brand_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][thread_color_id]"]`).val(sub.thread_color_id);
                        
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_primary_bags]"]`).val(sub.no_of_primary_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_bags]"]`).val(sub.no_of_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags]"]`).val(sub.empty_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags]"]`).val(sub.extra_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags_percentage]"]`).val(sub.extra_bags_percentage);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bag_weight]"]`).val(sub.empty_bag_weight);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);

                        subContainer.append(subRow);
                        subRow.find('select.sub-select2').select2({ width: '100%' });
                    });
                }

                row.find('.no-of-bags').trigger('input');
            });
            reindexPackingItems();
        }

        // Add Packing Item
        $(document).on('click', '#addPackingItem', function (e) {
            e.preventDefault();
            addNewPackingItem();
        });

        var isAddingItem = false;
        function addNewPackingItem() {
            if (isAddingItem) return;
            isAddingItem = true;

            var $firstItem = $('.packing-item').first();
            var $newItem = $firstItem.clone(false, false); 
            var newIndex = $('.packing-item').length;

            $newItem.find('input, select, textarea').each(function () {
                var $this = $(this);
                var name = $this.attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $this.attr('name', name);
                }
                
                if ($this.is('select')) {
                    $this.prop('selectedIndex', 0);
                } else {
                    if($this.hasClass('empty-bags') || $this.hasClass('extra-bags') || $this.hasClass('extra-bags-percentage') || $this.hasClass('min-weight') || $this.hasClass('containers') || $this.hasClass('stuffing')){
                        $this.val('0');
                    } else {
                        $this.val('');
                    }
                }

                $this.removeClass('select2-hidden-accessible');
                $this.removeAttr('data-select2-id');
                $this.find('option').removeAttr('data-select2-id');
            });

            $newItem.find('.select2-container').remove();
            $newItem.find('.sub-packing-items-container').attr('data-index', newIndex).empty();
            $newItem.find('.add-sub-packing-item').attr('data-index', newIndex);
            $newItem.find('.total-bags, .total-kgs, .metric-tons').val('0');

            $('#packingItems').append($newItem);

            $newItem.find('select.select2').each(function() {
                $(this).select2({ width: '100%' });
            });

            reindexPackingItems();
            isAddingItem = false;
        }

        // Remove Packing Item
        $(document).on('click', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                reindexPackingItems();
                $('select[name*="company_location_id"]').first().trigger('change');
            }
        });

        // Add Sub Packing Item
        $(document).on('click', '.add-sub-packing-item', function (e) {
            e.preventDefault();
            var packingItem = $(this).closest('.packing-item');
            var firstInput = packingItem.find('input[name*="packing_items"], select[name*="packing_items"]').first();
            var nameAttr = firstInput.attr('name');
            var packingIndexMatch = nameAttr ? nameAttr.match(/packing_items\[(\d+)\]/) : null;
            var packingIndex = packingIndexMatch ? packingIndexMatch[1] : packingItem.index();

            var container = packingItem.find('.sub-packing-items-container');
            var templateRow = $('.sub-packing-item-template').find('.sub-packing-item-row').first();

            if (!templateRow.length) return;

            var subIndex = container.find('.sub-packing-item-row').length;
            var newRow = templateRow.clone(false, false);
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');
            newRow.find('option').removeAttr('data-select2-id');

            newRow.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[SUB_INDEX\]/g, '[' + subIndex + ']');
                    name = name.replace(/\[INDEX\]/g, '[' + packingIndex + ']');
                    $(this).attr('name', name);
                }
            });

            newRow.find('input[type="text"], input[type="number"]').not('[readonly]').val('');
            newRow.find('.sub-empty-bags, .sub-extra-bags, .sub-extra-bags-percentage, .sub-empty-bag-weight, .sub-no-of-primary-bags').val('0');
            newRow.find('input[type="number"][readonly]').val('0');
            newRow.find('select').prop('selectedIndex', 0);
            newRow.find('input[type="file"]').val('');

            container.append(newRow);
            newRow.find('select.sub-select2').select2({ width: '100%' });

            calculateSubItemNoOfBags(newRow, packingItem);
        });

        // Remove Sub Packing Item
        $(document).on('click', '.remove-sub-packing-item', function () {
            var packingItem = $(this).closest('.packing-item');
            $(this).closest('.sub-packing-item-row').remove();
            calculateTotals(packingItem);
        });

        // Calculate No. of Bags for sub item
        $(document).on('input', '.sub-no-of-primary-bags', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var packingItem = subRow.closest('.packing-item');
            calculateSubItemNoOfBags(subRow, packingItem);
        });

        $(document).on('input', '.no-of-bags', function () {
            var packingItem = $(this).closest('.packing-item');
            packingItem.find('.sub-packing-item-row').each(function () {
                calculateSubItemNoOfBags($(this), packingItem);
            });
        });

        // Calculate total bags for sub item
        $(document).on('input', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
            var extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;

            if ($(this).hasClass('sub-no-of-bags')) {
                var percentageVal = subRow.find('.sub-extra-bags-percentage').val();
                if (percentageVal !== '' && noOfBags > 0) {
                    var percentage = parseFloat(percentageVal) || 0;
                    extraBags = Math.round((percentage / 100) * noOfBags);
                    subRow.find('.sub-extra-bags').val(extraBags);
                }
            }

            if ($(this).hasClass('sub-extra-bags') && noOfBags > 0 && !$(this).hasClass('is-calculating')) {
                if ($(this).val() === '') {
                    subRow.find('.sub-extra-bags-percentage').val('');
                } else {
                    var percentage = (extraBags / noOfBags) * 100;
                    subRow.find('.sub-extra-bags-percentage').val(percentage.toFixed(2));
                }
            }

            var totalBags = noOfBags + emptyBags + extraBags;
            subRow.find('.sub-total-bags').val(totalBags);
        });

        // Sub extra bags percentage calculation
        $(document).on('input', '.sub-extra-bags-percentage', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                subRow.find('.sub-extra-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var extraBags = Math.round((percentage / 100) * noOfBags);
                subRow.find('.sub-extra-bags').addClass('is-calculating').val(extraBags).trigger('input').removeClass('is-calculating');
            }
        });

        function calculateSubItemNoOfBags(subRow, packingItem) {
            var noOfBagsPrimary = parseInt(packingItem.find('.no-of-bags').val()) || 0;
            var noOfPrimaryBags = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;
            var parentBagSize = parseFloat(packingItem.find('.bag-size').val()) || 0;
            var calculatedPackingSize = parentBagSize * noOfPrimaryBags;
            subRow.find('.sub-calculated-packing-size').val(calculatedPackingSize > 0 ? calculatedPackingSize.toFixed(2) : '0');

            if (noOfBagsPrimary > 0 && noOfPrimaryBags > 0) {
                var noOfBags = Math.floor(noOfBagsPrimary / noOfPrimaryBags);
                subRow.find('.sub-no-of-bags').val(noOfBags);
                subRow.find('.sub-no-of-bags').trigger('input');
            } else {
                subRow.find('.sub-no-of-bags').val('0');
            }
        }

        // Auto-calculate totals on primary packing item
        $(document).on('input', '.bag-size, .no-of-bags, .extra-bags, .empty-bags', function () {
            var item = $(this).closest('.packing-item');
            
            if ($(this).hasClass('no-of-bags')) {
                var noOfBags = parseInt($(this).val()) || 0;
                var percentageVal = item.find('.extra-bags-percentage').val();
                if (percentageVal !== '' && noOfBags > 0) {
                    var percentage = parseFloat(percentageVal) || 0;
                    var extraBags = Math.round((percentage / 100) * noOfBags);
                    item.find('.extra-bags').addClass('is-calculating').val(extraBags).removeClass('is-calculating');
                }
            }

            if ($(this).hasClass('extra-bags') && !$(this).hasClass('is-calculating')) {
                var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
                var extraBags = parseInt($(this).val());
                if (noOfBags > 0) {
                    if ($(this).val() === '') {
                        item.find('.extra-bags-percentage').val('');
                    } else {
                        var percentage = (extraBags / noOfBags) * 100;
                        item.find('.extra-bags-percentage').val(percentage.toFixed(2));
                    }
                }
            }
            
            calculateTotals(item);
        });

        $(document).on('input', '.extra-bags-percentage', function () {
            var item = $(this).closest('.packing-item');
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                item.find('.extra-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var extraBags = Math.round((percentage / 100) * noOfBags);
                item.find('.extra-bags').addClass('is-calculating').val(extraBags).trigger('input').removeClass('is-calculating');
            }
        });

        $(document).on('input', '.metric-tons, .containers', function () {
            var item = $(this).closest('.packing-item');
            calculateStuffing(item);
        });

        $(document).on('input', '.metric-tons, .stuffing', function () {
            var item = $(this).closest('.packing-item');
            calculateContainers(item);
        });

        function calculateStuffing(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var containers = parseInt(item.find('.containers').val()) || 0;

            if (containers > 0 && metricTons > 0) {
                var stuffingPerContainer = metricTons / containers;
                item.find('.stuffing').val(stuffingPerContainer.toFixed(3));
            }
        }

        function calculateContainers(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var stuffing = parseFloat(item.find('.stuffing').val()) || 0;

            if (stuffing > 0 && metricTons > 0) {
                var containers = Math.ceil(metricTons / stuffing);
                item.find('.containers').val(containers);
            }
        }

        function calculateTotals(item) {
            var bagSize = parseFloat(item.find('.bag-size').val()) || 0;
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var extraBags = parseInt(item.find('.extra-bags').val()) || 0;
            var emptyBags = parseInt(item.find('.empty-bags').val()) || 0;

            var totalBags = noOfBags + extraBags + emptyBags;
            var totalKgs = noOfBags * bagSize;
            var metricTons = totalKgs / 1000;

            item.find('.total-bags').val(totalBags);
            item.find('.total-kgs').val(totalKgs.toFixed(2));
            item.find('.metric-tons').val(metricTons.toFixed(3));

            var containers = parseInt(item.find('.containers').val()) || 0;
            if (containers > 0) {
                calculateStuffing(item);
            }

            item.find('.sub-packing-item-row').each(function () {
                calculateSubItemNoOfBags($(this), item);
            });
        }

        function reindexPackingItems() {
            var totalItems = $('.packing-item').length;
            $('.packing-item').each(function (index) {
                var packingItem = $(this);
                packingItem.find('.packing-item-title').text('Packing Item #' + (index + 1));
                if (totalItems > 1) {
                    packingItem.find('.remove-packing-item').show();
                } else {
                    packingItem.find('.remove-packing-item').hide();
                }

                packingItem.find('.sub-packing-items-container').attr('data-index', index);
                packingItem.find('.add-sub-packing-item').attr('data-index', index);

                var firstInput = packingItem.find('input[name*="packing_items"], select[name*="packing_items"]').first();
                var oldName = firstInput.attr('name');
                var oldIndexMatch = oldName ? oldName.match(/packing_items\[(\d+)\]/) : null;
                var oldIndex = oldIndexMatch ? oldIndexMatch[1] : null;

                if (oldIndex !== null && oldIndex != index) {
                    packingItem.find('input, select, textarea').each(function () {
                        var name = $(this).attr('name');
                        if (name && name.includes('packing_items[' + oldIndex + ']')) {
                            name = name.replace('packing_items[' + oldIndex + ']', 'packing_items[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                }
            });
        }

        // Trigger calculate on existing items
        $('.packing-item').each(function() {
            calculateTotals($(this));
        });
        reindexPackingItems();

        // Container Protection & Packing Materials
        // Add Container Protection Item
        $(document).off('click.jobOrderEdit', '#addContainerProtectionItem').on('click.jobOrderEdit', '#addContainerProtectionItem', function (e) {
            e.preventDefault();
            var template = $('.container-protection-item-template').find('.container-protection-item').first();
            var newItem = template.clone(true, true); // Deep clone

            // Get current index
            var currentIndex = $('#containerProtectionItems').find('.container-protection-item').length;

            // Destroy any existing Select2 instances in cloned item
            newItem.find('select.select2').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                // Remove Select2 containers
                $select.siblings('.select2-container').remove();
                $select.show().removeClass('select2-hidden-accessible');
            });

            // Update index in all inputs/selects
            newItem.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[INDEX\]/g, '[' + currentIndex + ']');
                    $(this).attr('name', name);
                }
            });

            // Clear values
            newItem.find('input[type="number"]').val('');
            newItem.find('select').prop('selectedIndex', 0);

            // Show section if hidden
            $('#containerProtectionSection').show();

            // Append to container
            $('#containerProtectionItems').append(newItem);

            // Initialize Select2 for new selects after a small delay to ensure DOM is ready
            setTimeout(function () {
                newItem.find('select.select2').each(function () {
                    var $select = $(this);
                    // Make sure it's not already initialized
                    if (!$select.data('select2')) {
                        $select.select2({ width: '100%' });
                    }
                });
            }, 10);
        });

        // Remove Container Protection Item
        $(document).off('click.jobOrderEdit', '.remove-container-protection-item').on('click.jobOrderEdit', '.remove-container-protection-item', function () {
            $(this).closest('.container-protection-item').remove();
            reindexContainerProtectionItems();
        });

        // Re-index container protection items
        function reindexContainerProtectionItems() {
            $('#containerProtectionItems').find('.container-protection-item').each(function (index) {
                $(this).find('input, select').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        // Extract current index and replace with new index
                        name = name.replace(/container_protection_items\[\d+\]/, 'container_protection_items[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }

        // Auto-populate arrival locations based on company location selection
        $(document).on('change', 'select[name*="company_location_id"]', function() {
            var locationIds = [];
            $('select[name*="company_location_id"]').each(function() {
                var val = $(this).val();
                if (val && !locationIds.includes(val)) locationIds.push(val);
            });
            
            if (locationIds.length > 0) {
                $.ajax({
                    url: "{{ route('job-orders.get-arrival-locations') }}",
                    type: "GET",
                    data: { company_location_ids: locationIds },
                    success: function(response) {
                        var arrivalSelect = $('select[name="arrival_locations[]"]');
                        var currentValues = arrivalSelect.val() || [];
                        arrivalSelect.empty();
                        $.each(response, function(index, location) {
                            var isSelected = currentValues.includes(location.id.toString()) ? 'selected' : '';
                            arrivalSelect.append('<option value="' + location.id + '" ' + isSelected + '>' + location.name + '</option>');
                        });
                        arrivalSelect.trigger('change');
                    }
                });
            } else {
                $('select[name="arrival_locations[]"]').empty().trigger('change');
            }
        });

        // Initialize display for location & phases
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
