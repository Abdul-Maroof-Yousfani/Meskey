@extends('management.layouts.master')
@section('title')
    Steaming / Parboiling Job Order
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Steaming / Parboiling Job Order</h4>
                    <a href="{{ route('production-steam-parboiling-job-order.index') }}" onclick="loadPageContent('{{ route('production-steam-parboiling-job-order.index') }}')" class="btn btn-sm btn-secondary">
                        <i class="ft-arrow-left mr-1"></i>Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="#" method="POST" id="ajaxSubmit" autocomplete="off">
                        @csrf

                        <!-- Basic Information -->
                        <div class="row form-mar">
                            <div class="col-md-12">
                                <h6 class="header-heading-sepration">Basic Information</h6>
                                <div class="row">
                                    <!-- Job Order No -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Job Order No#</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button class="btn btn-primary" type="button">Job Order No#</button>
                                                </div>
                                                <input type="text" name="job_order_no" class="form-control" placeholder="JOB-2026-0001">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Job Order Date -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Job Order Date:</label>
                                            <input type="date" name="job_order_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>

                                    <!-- Reference No -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Reference No:</label>
                                            <input type="text" name="ref_no" class="form-control" placeholder="Enter Reference No">
                                        </div>
                                    </div>

                                    <!-- Attention To -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Attention To:</label>
                                            <select name="attention_to[]" class="form-control select2" multiple="multiple">
                                                <option value="">Select Attention To</option>
                                                @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Commodity / Product -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Commodity / Product:</label>
                                            <select name="product_id" class="form-control select2">
                                                <option value="">Select Commodity</option>
                                                @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Crop Year -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Crop Year:</label>
                                            <select name="crop_year" class="form-control select2">
                                                <option value="">Select Crop Year</option>
                                                @foreach($cropYears as $cropYear)
                                                    <option value="{{ $cropYear->name }}">{{ $cropYear->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                     <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Process Selection <span class="text-danger">*</span></label>
                                            <select name="p2_process_type" id="p2_process_type" class="form-control">
                                                <option value="-1">Select Process</option>
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
                                            <label>Parboiled Output Grade</label>
                                            <select name="p2_parboiled_grade" class="form-control">
                                                <option value="golden_sella">Golden Sella</option>
                                                <option value="creamy_sella">Creamy Sella</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Parameters (Multi-select) -->
                                    <div class="col-md-12 mt-2">
                                        <div class="card border mb-2 shadow-none" style="background-color: #f8f9fa; border-radius: 6px;">
                                            <div class="card-body p-2">
                                                <div class="form-group mb-0">
                                                    <label class="font-weight-bold text-dark mb-1">
                                                        <i class="ft-sliders mr-1 text-primary"></i> Select Parameters / Attributes:
                                                    </label>
                                                    <select class="form-control select2" id="attribute_select" multiple="multiple" style="width: 100%;" data-placeholder="-- Select Parameters (Multi-Select) --">
                                                        @foreach ($attributes as $attr)
                                                            <option value="{{ $attr->id }}"
                                                                data-id="{{ $attr->id }}"
                                                                data-key="{{ $attr->key }}"
                                                                data-type="{{ $attr->type }}"
                                                                data-slug="{{ $attr->slug }}">
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
                                                    <i class="ft-list mr-1 text-primary"></i>Recipe Parameters List
                                                </h6>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger mr-1" id="btn-clear-all-params" style="display: none;">
                                                    <i class="ft-trash-2 mr-1"></i>Clear All
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped" id="recipe-params-table">
                                                <thead class="bg-light text-center">
                                                    <tr>
                                                        <th style="width: 35%;">Parameter Key <span class="text-danger">*</span></th>
                                                        <th style="width: 25%;">Type</th>
                                                        <th style="width: 30%;">Parameter Value <span class="text-danger">*</span></th>
                                                        <th style="width: 10%;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="recipe-params-tbody">
                                                    <tr id="no-params-row">
                                                        <td colspan="4" class="text-center text-muted py-4">
                                                            <i class="ft-alert-circle mr-1"></i> No attributes selected yet. Select attributes from the dropdown above to add them to this recipe.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Remarks:</label>
                                            <textarea name="remarks" class="form-control" rows="4" placeholder="Enter remarks..."></textarea>
                                        </div>
                                    </div>

                                    <!-- Order Description -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Description:</label>
                                            <textarea name="order_description" class="form-control" rows="4" placeholder="Enter order description..."></textarea>
                                        </div>
                                    </div>

                                    <!-- Other Specifications -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Other Specification:</label>
                                            <textarea name="other_specifications" class="form-control" rows="3" placeholder="Enter other specifications..."></textarea>
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
                                    Save Job Order
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
        // Initialize Select2 dropdowns
        $('.select2').select2({
            width: '100%'
        });
        let paramIndex = 0;     
        // Get placeholder text based on attribute type
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
                <tr class="param-row" data-index="${paramIndex}" data-attribute-id="${attrId}">
                    <td>
                        <input type="hidden" name="items[${paramIndex}][production_attribute_id]" value="${attrId}">
                        <input type="text" name="items[${paramIndex}][key]" class="form-control form-control-sm text-monospace bg-light font-weight-bold"
                            value="${key}" readonly required>
                    </td>
                    <td>
                        <select name="items[${paramIndex}][type]" class="form-control form-control-sm bg-light" style="pointer-events: none;">
                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="percentage" ${type === 'percentage' ? 'selected' : ''}>Percentage (%)</option>
                            <option value="temperature" ${type === 'temperature' ? 'selected' : ''}>Temperature (°C)</option>
                            <option value="time" ${type === 'time' ? 'selected' : ''}>Time / Duration</option>
                            <option value="boolean" ${type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="${
                            type == 'text' ? 'text' :
                            type == 'number' ? 'number' :
                            type == 'percentage' ? 'number' :
                            type == 'temperature' ? 'number' :
                            type == 'time' ? 'time' :
                            'text'
                        }"
                        name="items[${paramIndex}][value]"
                        class="form-control form-control-sm param-value-input"
                        placeholder="${placeholder}"
                        value="${value}"
                        required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" data-id="${attrId}" title="Remove">
                            <i class="ft-trash font-medium-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#recipe-params-tbody').append(rowHtml);
            paramIndex++;
            updateTableState();
        }


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

        updateTableState();


    });
</script>
@endsection