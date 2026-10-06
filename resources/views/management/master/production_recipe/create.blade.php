<form action="{{ route('production-recipe.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-recipe') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Production Recipe Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" placeholder="e.g. Sella Steaming Recipe" class="form-control" required />
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status: <span class="text-danger">*</span></label>
                <select class="form-control select2" name="status">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Commodity (Raw Material): <span class="text-danger">*</span></label>
                <select class="form-control select2" name="commodity_id" id="commodity_id" required>
                    <option value="">-- Select Commodity --</option>
                    @foreach ($commodities as $commodity)
                        <option value="{{ $commodity->id }}">{{ $commodity->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Crop Year:</label>
                <select class="form-control select2" name="crop_year_id" id="crop_year_id">
                    <option value="">-- Select Crop Year --</option>
                    @foreach ($cropYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description / Notes:</label>
                <textarea name="description" placeholder="Recipe instructions and process details" class="form-control" rows="2"></textarea>
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
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-param-row">
                        <i class="ft-plus mr-1"></i>Add Custom Parameter
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
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Production Recipe</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2({
            width: '100%'
        });

        let paramIndex = 0;

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
        $('#btn-add-param-row').off('click').on('click', function () {
            $('#no-params-row').remove();
            const rowHtml = `
                <tr class="param-row" data-index="${paramIndex}">
                    <td>
                        <input type="text" name="items[${paramIndex}][key]" class="form-control form-control-sm text-monospace"
                            placeholder="e.g. cooling_duration" required>
                    </td>
                    <td>
                        <select name="items[${paramIndex}][type]" class="form-control form-control-sm">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="temperature">Temperature (°C)</option>
                            <option value="time">Time / Duration</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${paramIndex}][value]" class="form-control form-control-sm"
                            placeholder="Enter parameter value" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                            <i class="ft-trash font-medium-2"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#recipe-params-tbody').append(rowHtml);
            paramIndex++;
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
