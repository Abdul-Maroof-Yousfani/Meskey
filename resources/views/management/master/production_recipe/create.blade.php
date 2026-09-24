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

        <!-- Dynamic Parameters (Key, Value, Type - Multiple) -->
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
                        <tr class="param-row" data-index="0">
                            <td>
                                <input type="text" name="items[0][key]" class="form-control form-control-sm text-monospace"
                                    placeholder="e.g. soaking_temperature" value="soaking_temperature" required>
                            </td>
                            <td>
                                <select name="items[0][type]" class="form-control form-control-sm">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="temperature" selected>Temperature (°C)</option>
                                    <option value="time">Time / Duration</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[0][value]" class="form-control form-control-sm"
                                    placeholder="e.g. 65 °C" value="65 °C">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                                    <i class="ft-trash font-medium-2"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="param-row" data-index="1">
                            <td>
                                <input type="text" name="items[1][key]" class="form-control form-control-sm text-monospace"
                                    placeholder="e.g. soaking_duration" value="soaking_duration" required>
                            </td>
                            <td>
                                <select name="items[1][type]" class="form-control form-control-sm">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="temperature">Temperature (°C)</option>
                                    <option value="time" selected>Time / Duration</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[1][value]" class="form-control form-control-sm"
                                    placeholder="e.g. 4 Hours" value="4 Hours">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                                    <i class="ft-trash font-medium-2"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="param-row" data-index="2">
                            <td>
                                <input type="text" name="items[2][key]" class="form-control form-control-sm text-monospace"
                                    placeholder="e.g. steam_duration" value="steam_duration" required>
                            </td>
                            <td>
                                <select name="items[2][type]" class="form-control form-control-sm">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="temperature">Temperature (°C)</option>
                                    <option value="time" selected>Time / Duration</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[2][value]" class="form-control form-control-sm"
                                    placeholder="e.g. 20 Mins" value="20 Mins">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-param-row" title="Delete">
                                    <i class="ft-trash font-medium-2"></i>
                                </button>
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
        $('.select2').select2();

        let paramIndex = 3;

        $('#btn-add-param-row').off('click').on('click', function () {
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
                            placeholder="e.g. 30 Mins">
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
