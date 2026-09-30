<form action="{{ route('production-recipe.update', $production_recipe->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-recipe') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Production Recipe Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ $production_recipe->name }}" placeholder="e.g. Sella Steaming Recipe" class="form-control" required />
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status: <span class="text-danger">*</span></label>
                <select class="form-control select2" name="status">
                    <option value="active" {{ $production_recipe->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $production_recipe->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Commodity (Raw Material): <span class="text-danger">*</span></label>
                <select class="form-control select2" name="commodity_id" id="commodity_id" required>
                    <option value="">-- Select Commodity --</option>
                    @foreach ($commodities as $commodity)
                        <option value="{{ $commodity->id }}" {{ $production_recipe->commodity_id == $commodity->id ? 'selected' : '' }}>
                            {{ $commodity->name }}
                        </option>
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
                        <option value="{{ $year->id }}" {{ $production_recipe->crop_year_id == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description / Notes:</label>
                <textarea name="description" placeholder="Recipe instructions and process details" class="form-control" rows="2">{{ $production_recipe->description }}</textarea>
            </div>
        </div>

        <!-- Dynamic Parameters & Items (Key, Type, Value, Slug) -->
        <div class="col-12 mt-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                    <h6 class="mb-0">
                        <i class="ft-sliders mr-1"></i>Recipe Parameters & Items
                    </h6>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-param-row">
                    <i class="ft-plus mr-1"></i>Add Parameter
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="recipe-params-table">
                    <thead class="bg-light text-center">
                        <tr>
                            <th style="width: 35%;">Parameter Key <span class="text-danger">*</span></th>
                            <th style="width: 25%;">Type</th>
                            <th style="width: 30%;">Parameter Value</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recipe-params-tbody">
                        @php
                            $items = $production_recipe->items;
                        @endphp
                        @forelse ($items as $idx => $item)
                            <tr class="param-row" data-index="{{ $idx }}">
                                <td>
                                    @if (!empty($item->id))
                                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                                    @endif
                                    <input type="text" name="items[{{ $idx }}][key]" class="form-control form-control-sm text-monospace"
                                        placeholder="e.g. soaking_temperature" value="{{ $item->key ?? '' }}" required>
                                </td>
                                <td>
                                    <select name="items[{{ $idx }}][type]" class="form-control form-control-sm">
                                        <option value="text" {{ ($item->type ?? 'text') == 'text' ? 'selected' : '' }}>Text</option>
                                        <option value="number" {{ ($item->type ?? '') == 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="percentage" {{ ($item->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                        <option value="temperature" {{ ($item->type ?? '') == 'temperature' ? 'selected' : '' }}>Temperature (°C)</option>
                                        <option value="time" {{ ($item->type ?? '') == 'time' ? 'selected' : '' }}>Time / Duration</option>
                                        <option value="boolean" {{ ($item->type ?? '') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $idx }}][value]" class="form-control form-control-sm"
                                        placeholder="e.g. 65 °C" value="{{ $item->value ?? '' }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                                        <i class="ft-trash font-medium-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="param-row" data-index="0">
                                <td>
                                    <input type="text" name="items[0][key]" class="form-control form-control-sm text-monospace"
                                        placeholder="e.g. soaking_temperature" value="" required>
                                </td>
                                <td>
                                    <select name="items[0][type]" class="form-control form-control-sm">
                                        <option value="text" selected>Text</option>
                                        <option value="number">Number</option>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="temperature">Temperature (°C)</option>
                                        <option value="time">Time / Duration</option>
                                        <option value="boolean">Boolean</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][value]" class="form-control form-control-sm"
                                        placeholder="e.g. 65 °C" value="">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                                        <i class="ft-trash font-medium-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Production Recipe</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();

        let paramIndex = {{ count($items) > 0 ? count($items) : 1 }};

        $('#btn-add-param-row').off('click').on('click', function () {
            const rowHtml = `
                <tr class="param-row" data-index="${paramIndex}">
                    <td>
                        <input type="text" name="items[${paramIndex}][key]" class="form-control form-control-sm text-monospace"
                            placeholder="e.g. parameter_name" required>
                    </td>
                    <td>
                        <select name="items[${paramIndex}][type]" class="form-control form-control-sm">
                            <option value="text" selected>Text</option>
                            <option value="number">Number</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="temperature">Temperature (°C)</option>
                            <option value="time">Time / Duration</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${paramIndex}][value]" class="form-control form-control-sm"
                            placeholder="e.g. value">
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
        });

        $(document).on('click', '.btn-remove-param-row', function () {
            $(this).closest('tr').remove();
        });
    });
</script>
