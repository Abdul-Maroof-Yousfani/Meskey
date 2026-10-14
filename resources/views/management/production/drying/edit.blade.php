@extends('management.layouts.master')
@section('title')
    Edit Drying Job Order #{{ $order->job_order_no }}
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Drying Job Order - <span class="text-primary">{{ $order->job_order_no }}</span></h4>
                    <a href="{{ route('production-drying-job-order.index') }}" onclick="loadPageContent('{{ route('production-drying-job-order.index') }}')" class="btn btn-sm btn-secondary">
                        <i class="ft-arrow-left mr-1"></i>Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('production-drying-job-order.update', $order->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="url" value="{{ route('production-drying-job-order.index') }}" />
                        <input type="hidden" id="listRefresh" value="{{ route('get.production-drying-job-order') }}" />

                        <div class="row form-mar">
                            <!-- 1. Basic Information -->
                            <div class="col-md-12">
                                <h6 class="header-heading-sepration">Basic Information</h6>
                                <div class="row">
                                    <!-- Export Order (Optional) -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Export Order (Optional):</label>
                                            <select name="export_order_id" class="form-control select2" id="exportOrderSelect">
                                                <option value="">Select Export Order</option>
                                                @foreach($exportOrders as $eo)
                                                    <option value="{{ $eo->id }}" {{ $order->export_order_id == $eo->id ? 'selected' : '' }}>{{ $eo->voucher_no }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Company Location -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Company Location: <span class="text-danger">*</span></label>
                                            <select name="company_location_id" id="company_location_id" class="form-control select2" required>
                                                <option value="">Select Location</option>
                                                @foreach($companyLocations as $location)
                                                    <option data-code="{{ $location->code }}" value="{{ $location->id }}" {{ $order->company_location_id == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Job Order Date -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Job Order Date: <span class="text-danger">*</span></label>
                                            <input type="date" name="job_order_date" class="form-control" value="{{ $order->job_order_date ? \Carbon\Carbon::parse($order->job_order_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <!-- Job Order No -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Job Order No#</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button class="btn btn-primary" type="button">Job Order No#</button>
                                                </div>
                                                <input type="text" name="job_order_no" class="form-control" value="{{ $order->job_order_no }}" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Export Order Quantity Alert Container -->
                                    <div class="col-12" id="export-order-quantity-info" style="display: none;"></div>

                                    <!-- Reference No -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Reference No:</label>
                                            <input type="text" name="ref_no" class="form-control" value="{{ $order->ref_no }}" placeholder="Enter Reference No">
                                        </div>
                                    </div>

                                    <!-- Attention To -->
                                    @php
                                        $selectedAttention = is_array($order->attention_to) ? $order->attention_to : (json_decode($order->attention_to ?? '[]', true) ?: []);
                                    @endphp
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Attention To:</label>
                                            <select name="attention_to[]" class="form-control select2" multiple="multiple">
                                                <option value="">Select Attention To</option>
                                                @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ in_array($user->id, $selectedAttention) ? 'selected' : '' }}>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Status:</label>
                                            <select name="status" class="form-control select2">
                                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="in_progress" {{ $order->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Process & Commodity -->
                            <div class="col-md-12">
                                <h6 class="header-heading-sepration">Process & Commodity</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Commodity / Product: <span class="text-danger">*</span></label>
                                            <select name="product_id" class="form-control select2" id="mainProductSelect" required>
                                                <option value="">Select Commodity</option>
                                                @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ $order->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Crop Year:</label>
                                            <select name="crop_year" class="form-control select2">
                                                <option value="">Select Crop Year</option>
                                                @foreach($cropYears as $cropYear)
                                                    <option value="{{ $cropYear->name }}" {{ $order->crop_year == $cropYear->name ? 'selected' : '' }}>{{ $cropYear->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Moisture Level:</label>
                                            <select name="moisture_level" class="form-control select2" id="moistureLevelSelect">
                                                <option value="">Select Moisture Level</option>
                                                <option value="half_dried" {{ $order->moisture_level == 'half_dried' ? 'selected' : '' }}>Half-Dried (~15%)</option>
                                                <option value="fully_dried" {{ $order->moisture_level == 'fully_dried' ? 'selected' : '' }}>Fully-Dried (13.5-14%)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Moisture Value (%):</label>
                                            <div class="input-group">
                                                <input type="text" name="moisture_value" id="moisture_value_input" class="form-control" value="{{ $order->moisture_value ?? '' }}" placeholder="e.g. 15 or 20">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Specifications Section -->
                            <div class="col-md-12 mt-1" id="specificationsSection"
                                style="display: {{ $order->specifications->count() ? 'block' : 'none' }};">
                                <h6 class="header-heading-sepration">Specifications</h6>
                                <div id="productSpecs">
                                    @if($order->specifications->count())
                                        <div class="specifications-table">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th width="40%">Specification Name</th>
                                                            <th width="30%">Value</th>
                                                            <th width="30%">Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($order->specifications as $index => $spec)
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $spec->spec_name }}</strong>
                                                                    <input type="hidden" name="specifications[{{ $index }}][product_slab_type_id]"
                                                                        value="{{ $spec->product_slab_type_id }}">
                                                                    <input type="hidden" name="specifications[{{ $index }}][spec_name]"
                                                                        value="{{ $spec->spec_name }}">
                                                                    <input type="hidden" name="specifications[{{ $index }}][uom]"
                                                                        value="{{ $spec->uom }}">
                                                                </td>
                                                                <td>
                                                                    <div class="input-group">
                                                                        <input type="text" name="specifications[{{ $index }}][spec_value]" value="{{ $spec->spec_value ?? 0 }}"
                                                                            class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                                                        <div class="input-group-prepend">
                                                                            <button class="btn btn-secondary" type="button">{{ $spec->uom ?: ($spec->productSlabType->qc_symbol ?? '') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <select name="specifications[{{ $index }}][value_type]" class="form-control form-control-sm">
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

                            <!-- 4. Production Recipe & Parameters -->
                            <div class="col-md-12 mt-1">
                                <h6 class="header-heading-sepration">Production Recipe & Parameters</h6>
                                <div class="row">
                                    <!-- Recipe Dropdown -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="ft-book-open mr-1 text-success"></i> Production Recipe:
                                            </label>
                                            <select name="production_recipe_id" class="form-control select2" id="productionRecipeSelect">
                                                <option value="">-- Select Production Recipe (Optional) --</option>
                                                <option value="custom" {{ (!$order->production_recipe_id && $order->recipe_name) ? 'selected' : '' }}>+ Create New / Custom Recipe</option>
                                                @if(isset($productionRecipes))
                                                    @foreach($productionRecipes as $rec)
                                                        <option value="{{ $rec->id }}" data-name="{{ $rec->name }}" {{ $order->production_recipe_id == $rec->id ? 'selected' : '' }}>
                                                            {{ $rec->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <small class="text-muted d-block mt-1" id="recipe-select-hint">
                                                <i class="ft-info mr-1"></i>Select recipe or click "+ New Recipe"
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Recipe Name Input -->
                                    <div class="col-md-6" id="recipe_name_container">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                Recipe Name:
                                            </label>
                                            <input type="text" name="recipe_name" id="recipe_name_input" class="form-control"
                                                value="{{ $order->recipe_name ?? ($order->productionRecipe->name ?? '') }}"
                                                placeholder="Enter recipe name for this Drying Job Order">
                                            <small class="text-muted d-block mt-1">
                                                <i class="ft-shield mr-1 text-info"></i>Stored with this Job Order only (won't affect master recipes).
                                            </small>
                                        </div>
                                    </div>

                                    <!-- New Recipe Button -->
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="button" class="btn btn-outline-success btn-block" id="btn-create-new-recipe" title="Define a new recipe for this job order only">
                                                    <i class="ft-plus mr-1"></i>New Recipe
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Predefined Attributes Multi-Select -->
                                    @php
                                        $selectedAttrIds = $order->items->pluck('production_attribute_id')->filter()->map(fn($id) => (string)$id)->toArray();
                                    @endphp
                                    <div class="col-md-12">
                                        <div class="card border mb-2 shadow-none" style="background-color: #f8f9fa; border-radius: 6px;">
                                            <div class="card-body p-2">
                                                <div class="form-group mb-0">
                                                    <label class="font-weight-bold text-dark mb-1">
                                                        <i class="ft-sliders mr-1 text-primary"></i> Quick Add Attributes:
                                                    </label>
                                                    <select class="form-control select2" id="attribute_select" multiple="multiple" style="width: 100%;" data-placeholder="-- Select Parameters (Multi-Select) --">
                                                        @foreach ($attributes as $attr)
                                                            <option value="{{ $attr->id }}"
                                                                data-id="{{ $attr->id }}"
                                                                data-key="{{ $attr->key }}"
                                                                data-type="{{ $attr->type }}"
                                                                data-slug="{{ $attr->slug }}"
                                                                {{ in_array((string)$attr->id, $selectedAttrIds) ? 'selected' : '' }}>
                                                                {{ $attr->key }} ({{ ucfirst($attr->type) }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="ft-info mr-1"></i>Select attributes from the list above. Each selected attribute will automatically be added to the parameters table.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recipe Parameters Table Toolbar & Table -->
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between align-items-center mb-1 mt-1">
                                            <h6 class="mb-0 font-weight-bold text-dark">
                                                <i class="ft-list mr-1 text-primary"></i>Recipe Parameters List
                                            </h6>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-outline-danger mr-1" id="btn-clear-all-params" style="{{ $order->items->count() > 0 ? '' : 'display: none;' }}">
                                                    <i class="ft-trash-2 mr-1"></i>Clear All
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-custom-param">
                                                    <i class="ft-plus mr-1"></i>Add Custom Parameter
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="recipe-params-table">
                                                <thead class="thead-light text-center">
                                                    <tr>
                                                        <th style="width: 35%;">Parameter Key <span class="text-danger">*</span></th>
                                                        <th style="width: 25%;">Type</th>
                                                        <th style="width: 30%;">Parameter Value <span class="text-danger">*</span></th>
                                                        <th style="width: 10%;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="recipe-params-tbody">
                                                    @forelse($order->items as $idx => $item)
                                                        @php
                                                            $type = $item->type ?? 'text';
                                                            $placeholder = match($type) {
                                                                'temperature' => 'e.g. 65 °C',
                                                                'time' => 'e.g. 4 Hours or 20 Mins',
                                                                'percentage' => 'e.g. 15%',
                                                                'number' => 'e.g. 100',
                                                                'boolean' => 'e.g. Yes / No',
                                                                default => 'Enter parameter value'
                                                            };
                                                        @endphp
                                                        <tr class="param-row" data-index="{{ $idx }}" data-attribute-id="{{ $item->production_attribute_id ?? '' }}">
                                                            <td>
                                                                <input type="hidden" name="items[{{ $idx }}][production_attribute_id]" value="{{ $item->production_attribute_id ?? '' }}">
                                                                <input type="text" name="items[{{ $idx }}][key]" class="form-control form-control-sm text-monospace font-weight-bold"
                                                                    value="{{ $item->key }}" placeholder="e.g. drying_temperature" required>
                                                            </td>
                                                            <td>
                                                                <select name="items[{{ $idx }}][type]" class="form-control form-control-sm">
                                                                    <option value="text" {{ $type === 'text' ? 'selected' : '' }}>Text</option>
                                                                    <option value="number" {{ $type === 'number' ? 'selected' : '' }}>Number</option>
                                                                    <option value="percentage" {{ $type === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                                                    <option value="temperature" {{ $type === 'temperature' ? 'selected' : '' }}>Temperature (°C)</option>
                                                                    <option value="time" {{ $type === 'time' ? 'selected' : '' }}>Time / Duration</option>
                                                                    <option value="boolean" {{ $type === 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="items[{{ $idx }}][value]"
                                                                    class="form-control form-control-sm param-value-input"
                                                                    placeholder="{{ $placeholder }}"
                                                                    value="{{ $item->value }}"
                                                                    required>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" data-id="{{ $item->production_attribute_id ?? '' }}" title="Remove">
                                                                    <i class="ft-trash font-medium-2"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr id="no-params-row">
                                                            <td colspan="4" class="text-center text-muted py-4">
                                                                <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to this recipe.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Remarks & Additional Details -->
                            <div class="col-md-12 mt-1">
                                <h6 class="header-heading-sepration">Remarks & Additional Details</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Remarks:</label>
                                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks...">{{ $order->remarks }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Order Description:</label>
                                            <textarea name="order_description" class="form-control" rows="3" placeholder="Enter order description...">{{ $order->order_description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Other Specifications:</label>
                                            <textarea name="other_specifications" class="form-control" rows="3" placeholder="Enter other specifications...">{{ $order->other_specifications }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3">
                            <div class="col-12 text-right">
                                <a href="{{ route('production-drying-job-order.index') }}" onclick="loadPageContent('{{ route('production-drying-job-order.index') }}')" class="btn btn-danger mr-1">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary submitbutton">
                                    Update Job Order
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
    $(document).ready(function () {
        $('.select2').select2({
            width: '100%'
        });

        let paramIndex = {{ $order->items->count() }};
        let isInitialRecipeLoad = true;

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

        function updateTableState() {
            const rowCount = $('#recipe-params-tbody tr.param-row').length;
            if (rowCount === 0) {
                if ($('#no-params-row').length === 0) {
                    $('#recipe-params-tbody').html(`
                        <tr id="no-params-row">
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to this recipe.
                            </td>
                        </tr>
                    `);
                }
                $('#btn-clear-all-params').hide();
            } else {
                $('#no-params-row').remove();
                $('#btn-clear-all-params').show();
            }
        }

        function addAttributeRow(attrId, key, type, value = '') {
            $('#no-params-row').remove();
            const placeholder = getPlaceholderForType(type);

            const rowHtml = `
                <tr class="param-row" data-index="${paramIndex}" data-attribute-id="${attrId || ''}">
                    <td>
                        <input type="hidden" name="items[${paramIndex}][production_attribute_id]" value="${attrId || ''}">
                        <input type="text" name="items[${paramIndex}][key]" class="form-control form-control-sm text-monospace font-weight-bold"
                            value="${key || ''}" placeholder="e.g. drying_temperature" required>
                    </td>
                    <td>
                        <select name="items[${paramIndex}][type]" class="form-control form-control-sm">
                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="percentage" ${type === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                            <option value="temperature" ${type === 'temperature' ? 'selected' : ''}>Temperature (°C)</option>
                            <option value="time" ${type === 'time' ? 'selected' : ''}>Time / Duration</option>
                            <option value="boolean" ${type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                            name="items[${paramIndex}][value]"
                            class="form-control form-control-sm param-value-input"
                            placeholder="${placeholder}"
                            value="${value || ''}"
                            required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" data-id="${attrId || ''}" title="Remove">
                            <i class="ft-trash font-medium-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#recipe-params-tbody').append(rowHtml);
            paramIndex++;
            updateTableState();
        }

        // On selecting an attribute from multi-select
        $('#attribute_select').on('select2:select', function (e) {
            const attrId = e.params.data.id;
            const $option = $(e.params.data.element);
            const key = $option.data('key') || e.params.data.text;
            const type = $option.data('type') || 'text';

            if ($('#recipe-params-tbody tr[data-attribute-id="' + attrId + '"]').length === 0) {
                addAttributeRow(attrId, key, type);
                $('#recipe-params-tbody tr[data-attribute-id="' + attrId + '"] .param-value-input').focus();
            }
        });

        // On unselecting an attribute from multi-select
        $('#attribute_select').on('select2:unselect', function (e) {
            const attrId = e.params.data.id;
            $('#recipe-params-tbody tr[data-attribute-id="' + attrId + '"]').remove();
            updateTableState();
        });

        // Add custom row manually if clicked
        $('#btn-add-custom-param').on('click', function () {
            addAttributeRow('', '', 'text', '');
            $('#recipe-params-tbody tr:last-child input[name$="[key]"]').focus();
        });

        // Remove row when trash button is clicked
        $(document).on('click', '.btn-remove-param-row', function () {
            const $row = $(this).closest('tr');
            const attrId = $row.data('attribute-id');
            $row.remove();

            if (attrId) {
                let currentVals = $('#attribute_select').val() || [];
                currentVals = currentVals.filter(v => String(v) !== String(attrId));
                $('#attribute_select').val(currentVals).trigger('change.select2');
            }
            updateTableState();
        });

        // Clear all parameters
        $('#btn-clear-all-params').on('click', function () {
            $('#attribute_select').val(null).trigger('change.select2');
            $('#recipe-params-tbody tr.param-row').remove();
            updateTableState();
        });

        let skipProductSpecsAjax = false;

        function buildSpecsTable(specs) {
            if(!specs || specs.length === 0) return;
            
            let html = '<div class="specifications-table mt-2"><div class="table-responsive"><table class="table table-bordered table-striped"><thead class="thead-dark"><tr><th width="40%">Specification Name</th><th width="30%">Value</th><th width="30%">Type</th></tr></thead><tbody>';
            specs.forEach(function(spec, idx) {
                let specName = spec.product_slab_type ? spec.product_slab_type.name : (spec.spec_name || '');
                let uom = spec.product_slab_type ? (spec.product_slab_type.qc_symbol || '') : (spec.uom || '');
                let valueType = spec.value_type || 'min';
                let specVal = spec.spec_value !== null && spec.spec_value !== undefined ? spec.spec_value : 0;
                
                html += `<tr>
                    <td>
                        <strong>${specName}</strong>
                        <input type="hidden" name="specifications[${idx}][product_slab_type_id]" value="${spec.product_slab_type_id || ''}">
                        <input type="hidden" name="specifications[${idx}][spec_name]" value="${specName}">
                        <input type="hidden" name="specifications[${idx}][uom]" value="${uom}">
                    </td>
                    <td>
                        <fieldset>
                            <div class="input-group">
                                <input type="text" name="specifications[${idx}][spec_value]" value="${specVal}" class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                <div class="input-group-prepend">
                                    <button class="btn btn-secondary" type="button">${uom || 'N/A'}</button>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                    <td>
                        <select name="specifications[${idx}][value_type]" class="form-control form-control-sm">
                            <option value="min" ${valueType === 'min' ? 'selected' : ''}>Minimum</option>
                            <option value="max" ${valueType === 'max' ? 'selected' : ''}>Maximum</option>
                        </select>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div></div>';
            $('#productSpecs').html(html);
            $('#specificationsSection').show();
        }

        function loadRecipesForProduct(productId, selectedRecipeId = null, isInitial = false) {
            let $select = $('#productionRecipeSelect');
            let currentVal = selectedRecipeId || $select.val();
            $select.empty();
            $select.append('<option value="">-- Select Production Recipe (Optional) --</option>');
            $select.append('<option value="custom">+ Create New / Custom Recipe</option>');

            if (!productId) {
                $select.trigger('change.select2');
                return;
            }

            $.get('{{ route("production-drying-job-order.get-recipes", "") }}/' + productId, function(response) {
                if (response.success && response.recipes) {
                    response.recipes.forEach(function(rec) {
                        let isSelected = currentVal && String(currentVal) === String(rec.id);
                        $select.append(`<option value="${rec.id}" data-name="${rec.name}" ${isSelected ? 'selected' : ''}>${rec.name}</option>`);
                    });
                    if (currentVal && currentVal === 'custom') {
                        $select.val('custom');
                    }
                    if (isInitial) {
                        isInitialRecipeLoad = true;
                    }
                    $select.trigger('change.select2');
                }
            }).fail(function(xhr) {
                console.error('Failed to load recipes:', xhr);
            });
        }

        function loadRecipeItems(items) {
            $('#recipe-params-tbody tr.param-row').remove();
            let selectedAttrIds = [];

            items.forEach(function(item) {
                let attrId = item.production_attribute_id || '';
                let key = item.key || '';
                let type = item.type || 'text';
                let val = item.value || '';
                if (attrId) {
                    selectedAttrIds.push(String(attrId));
                }
                addAttributeRow(attrId, key, type, val);
            });

            $('#attribute_select').val(selectedAttrIds).trigger('change.select2');
            updateTableState();
        }

        $('#productionRecipeSelect').on('change', function() {
            if (isInitialRecipeLoad) {
                isInitialRecipeLoad = false;
                return;
            }
            let val = $(this).val();
            if (!val) {
                return;
            }
            if (val === 'custom') {
                if (!$('#recipe_name_input').val()) {
                    let prodText = $('#mainProductSelect option:selected').text().trim();
                    if (prodText && !prodText.includes('Select')) {
                        $('#recipe_name_input').val(prodText + ' - Custom Recipe');
                    }
                }
                $('#recipe_name_input').focus();
                return;
            }

            let optName = $(this).find('option:selected').data('name');
            if (optName) {
                $('#recipe_name_input').val(optName);
            }

            $.get('{{ url("production/production-drying-job-order/get-recipe-details") }}/' + val, function(response) {
                if (response.success && response.recipe) {
                    $('#recipe_name_input').val(response.recipe.name);
                    if (response.recipe.items && response.recipe.items.length > 0) {
                        loadRecipeItems(response.recipe.items);
                    } else {
                        $('#recipe-params-tbody tr.param-row').remove();
                        $('#attribute_select').val(null).trigger('change.select2');
                        updateTableState();
                    }
                }
            }).fail(function(xhr) {
                console.error('Error fetching recipe details:', xhr);
            });
        });

        $('#btn-create-new-recipe').on('click', function() {
            isInitialRecipeLoad = false;
            $('#productionRecipeSelect').val('custom').trigger('change.select2');
            $('#recipe_name_input').val('').focus();
        });

        // Initialize recipes on edit page load without overwriting existing saved rows
        @if($order->product_id)
            loadRecipesForProduct({{ $order->product_id }}, '{{ $order->production_recipe_id ?: ($order->recipe_name ? "custom" : "") }}', true);
        @endif

        // Export Order selection change
        $('#exportOrderSelect').on('change', function() {
            let id = $(this).val();
            if(!id) {
                $('#mainProductSelect').prop('disabled', false);
                $('#mainProductSelect').next('.select2-container').css('pointer-events', '');
                $('#hidden_product_id').remove();
                $('#export-order-quantity-info').html('').hide();
                return;
            }

            $.get('{{ url("production/get-export-order-details") }}/' + id, function(data) {
                if (data.ref_no) {
                    $('input[name="ref_no"]').val(data.ref_no);
                }
                if (data.other_specifications) {
                    $('textarea[name="other_specifications"]').val(data.other_specifications);
                }

                let totalMt = data.total_eo_mt || 0;
                let consumedMt = data.consumed_mt || 0;
                let remainingMt = data.remaining_mt || 0;
                let alertHtml = `
                    <div class="alert bg-light-info mb-2 py-1 px-2" role="alert" style="color: #0c5460; font-weight: 500;">
                        <i class="ft-info mr-1"></i>
                        <strong>Export Order Quantity:</strong> Total: ${totalMt} MT | Consumed: ${consumedMt} MT | Remaining: ${remainingMt} MT
                    </div>
                `;
                $('#export-order-quantity-info').html(alertHtml).show();

                if (data.product_id) {
                    skipProductSpecsAjax = true;
                    $('#mainProductSelect').val(data.product_id).trigger('change.select2');
                    $('#mainProductSelect').prop('disabled', true);
                    $('#mainProductSelect').next('.select2-container').css('pointer-events', 'none');
                    skipProductSpecsAjax = false;

                    loadRecipesForProduct(data.product_id);

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
                }

                if (data.specifications && data.specifications.length > 0) {
                    buildSpecsTable(data.specifications);
                } else if (data.product_id) {
                    $.get('{{ route("get.product_specs", "") }}/' + data.product_id, function (specHtml) {
                        $('#productSpecs').html(specHtml);
                        $('#specificationsSection').show();
                    });
                }
            }).fail(function(xhr) {
                console.error('Export Order Details Error:', xhr.status, xhr.responseText);
            });
        });

        $('#mainProductSelect').on('change', function () {
            if (skipProductSpecsAjax) return;
            var productId = $(this).val();
            loadRecipesForProduct(productId);
            if (productId) {
                $.get('{{ route("get.product_specs", "") }}/' + productId, function (data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#specificationsSection').hide();
                $('#productSpecs').html(`
                    <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                        <i class="ft-info mr-1"></i>
                        <strong>No specifications found!</strong> Please select a commodity first!
                    </div>
                `);
            }
        });

        $('#moistureLevelSelect').on('change', function () {
            let val = $(this).val();
            let $moistureInput = $('#moisture_value_input');
            if (val === 'half_dried' && !$moistureInput.val()) {
                $moistureInput.val('15');
            } else if (val === 'fully_dried' && !$moistureInput.val()) {
                $moistureInput.val('14');
            }
        });
    });
</script>
@endsection
