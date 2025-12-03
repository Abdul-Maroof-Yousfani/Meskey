@if(isset($jobOrder) && $jobOrder)
<div class="row">
    <!-- Job Order Detail -->
    <div class="col-md-6">
        <div class="row">
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Order Detail</h6>

                <div class="form-group">
                    <label>Location:</label>
                    <select name="company_location_id" class="form-control" id="locationSelect" required>
                        @if(count($companyLocations) == 1)
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}"
                                    data-totalkg="{{ $location->getTotalKgsForJobOrder($jobOrder->id) }}" selected>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        @else
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}"
                                    data-totalkg="{{ $location->getTotalKgsForJobOrder($jobOrder->id) }}"
                                    {{ isset($qc) && $qc->location_id == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" class="form-control" readonly value="{{ $jobOrder->product->name ?? 'N/A' }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Job Order Qty:</label>
                    <input type="text" class="form-control" id="jobOrderQty" readonly value="">
                </div>
            </div>
        </div>
        
        <!-- Materials Selection -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Choose Materials:</label>
                    <select name="commodities[]" class="form-control select2" multiple id="commoditiesSelect" required>
                        <option value="">Select Materials</option>
                        @foreach($products as $product)
                            @php
                                $selected = false;
                                if (isset($commodityIds) && in_array($product->id, $commodityIds)) {
                                    $selected = true;
                                } elseif (isset($qc) && in_array($product->id, json_decode($qc->commodities, true) ?? [])) {
                                    $selected = true;
                                }
                            @endphp
                            <option value="{{ $product->id }}" {{ $selected ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Job Order Specifications Section -->
    <div class="col-md-6" id="specificationsSection"
        style="display: {{ $jobOrder->specifications->count() ? 'block' : 'none' }};">
        <h6 class="header-heading-sepration">Order Specifications</h6>
        <div id="productSpecs">
            @if($jobOrder->specifications->count())
                <div class="specifications-table">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="70%">Specification Name</th>
                                    <th width="30%">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jobOrder->specifications as $index => $spec)
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
                                            <fieldset>
                                                <div class="input-group m-0 ">
                                                    <input disabled type="text" 
                                                        name="specifications[{{ $index }}][spec_value]"
                                                        value="{{ $spec->spec_value ?? 0 }}{{ $spec->productSlabType->qc_symbol ?? 'N/A' }} {{ $spec->value_type ?? 'N/A' }}"
                                                        class="form-control form-control-sm spec-value-input text-capitalize"
                                                        placeholder="Enter value">
                                                </div>
                                            </fieldset>
                                        </td>
                                    </tr>
                                @endforeach
                                
                                @if($jobOrder->cropYear)
                                <tr>
                                    <td>
                                        <strong>Crop Year</strong>
                                    </td>
                                    <td>
                                        <strong> {{ $jobOrder->cropYear->name }} </strong>
                                    </td>
                                </tr>
                                @endif
                                
                                <tr>
                                    <td colspan="2">
                                        <strong>Other specifications</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <p class="m-0"> {{ $jobOrder->other_specifications ?? 'No Other Specifications' }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                    <i class="ft-info mr-1"></i>
                    <strong>No specifications found!</strong>
                </div>
            @endif
        </div>
    </div>
</div>
@else
<div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
    <i class="ft-info mr-1"></i>
    <strong>Select Job Order!</strong> Please select a Job Order to fetch the details.
</div>
@endif


<script>
    // Initialize Select2
    $('.select2').select2();



    $(document).ready(function () {

        // Initialize Select2
        $('.select2').select2();

        // let sublocations = [];

        // // Job Order Selection
        // $('#jobOrderSelect').change(function () {
        //     const jobOrderId = $(this).val();
        //     if (jobOrderId) {
        //         $.get('{{ route("get.job_order_details", "") }}/' + jobOrderId, function (data) {
        //             sublocations = data.sublocations;
        //         });
        //     }
        // });

        // Commodities Selection - Load Partial via AJAX
        $('#commoditiesSelect').change(function () {
            const selectedCommodities = $(this).val();

            if (selectedCommodities && selectedCommodities.length > 0) {
                // Show loading
                $('#qcCommoditiesSection').html('<div class="text-center py-4"><i class="ft-loader spinner"></i> Loading QC tables...</div>');

                // Load partial via AJAX
                $.ajax({
                    url: '{{ route("load_qc_commodities_tables") }}',
                    type: 'GET',
                    data: {
                        commodities: selectedCommodities
                    },
                    success: function (response) {
                        $('#qcCommoditiesSection').html(response);

                        // Initial calculations
                        $('.commodity-table').each(function () {
                            const commodityId = $(this).data('commodity-id');
                            calculateWeightedAverage(commodityId);
                        });
                        updateCombinedAverage();
                    },
                    error: function () {
                        $('#qcCommoditiesSection').html('<div class="alert alert-danger">Error loading QC tables</div>');
                    }
                });
            } else {
                $('#qcCommoditiesSection').html('<div class="alert alert-warning">Please select at least one commodity</div>');
            }
        });

        // Add location row - Event delegation for dynamically loaded content
        $(document).on('click', '.add-location-row', function () {
            const commodityId = $(this).data('commodity-id');
            addLocationRow(commodityId);
        });

        // Remove location row - Event delegation for dynamically loaded content
        $(document).on('click', '.remove-row', function () {
            const $row = $(this).closest('tr');
            const $tbody = $row.closest('tbody');
            const commodityId = $row.closest('.commodity-table').data('commodity-id');

            if ($tbody.find('tr').length > 1) {
                $row.remove();

                // Re-index remaining rows
                reindexCommodityRows(commodityId);

                // Recalculate averages
                calculateWeightedAverage(commodityId);
                updateCombinedAverage();

                // Disable remove button if only one row left
                if ($tbody.find('tr').length === 1) {
                    $tbody.find('.remove-row').prop('disabled', true);
                }
            }
        });

        // Auto-calculate totals - Event delegation for dynamically loaded content
        $(document).on('input', '.suggested-quantity, .param-input', function () {
            const commodityId = $(this).closest('.commodity-table').data('commodity-id');
            calculateWeightedAverage(commodityId);
            updateCombinedAverage();
        });






        // Function to add location row
        function addLocationRow(commodityId) {
            const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
            const $tbody = $table.find('tbody');
            const $lastRow = $tbody.find('tr').last();

            // Clone the last row
            const $newRow = $lastRow.clone();

            // Update index in names
            const newIndex = $tbody.find('tr').length;
            $newRow.find('input, select').each(function () {
                const $element = $(this);
                const name = $element.attr('name');
                if (name) {
                    const newName = name.replace(/\[locations\]\[\d+\]/, `[locations][${newIndex}]`);
                    $element.attr('name', newName);
                }
            });

            // Clear values
            $newRow.find('.sublocation-select').val('');
            $newRow.find('.suggested-quantity').val('');
            $newRow.find('.param-input').val('');

            // Enable remove button for new row
            $newRow.find('.remove-row').prop('disabled', false);

            // Append to table
            $tbody.append($newRow);

            // Recalculate averages
            calculateWeightedAverage(commodityId);
            updateCombinedAverage();
        }

        // Function to reindex rows after removal
        function reindexCommodityRows(commodityId) {
            const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
            const $tbody = $table.find('tbody');

            $tbody.find('tr').each(function (index) {
                const $row = $(this);
                $row.find('input, select').each(function () {
                    const $element = $(this);
                    const name = $element.attr('name');
                    if (name) {
                        const newName = name.replace(/\[locations\]\[\d+\]/, `[locations][${index}]`);
                        $element.attr('name', newName);
                    }
                });
            });
        }


        // Function to calculate weighted average for a commodity
        function calculateWeightedAverage(commodityId) {
            const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
            console.log('Calculating for commodity:', commodityId);
            console.log('Table found:', $table.length);

            const $rows = $table.find('tbody tr');
            let totalQuantity = 0;
            const weightedSums = {};

            // Get parameters for this commodity
            const $addButton = $table.find('.add-location-row');
            const commodityParameters = $addButton.data('parameters') || [];
            console.log('Parameters:', commodityParameters);

            // Initialize weighted sums
            commodityParameters.forEach(param => {
                const paramField = createParamField(param);
                weightedSums[paramField] = 0;
            });

            console.log('Weighted sums initialized:', weightedSums);

            // Calculate totals
            $rows.each(function (index) {
                const $row = $(this);
                const quantity = parseFloat($row.find('.suggested-quantity').val()) || 0;
                console.log(`Row ${index} quantity:`, quantity);
                totalQuantity += quantity;

                commodityParameters.forEach(param => {
                    const paramField = createParamField(param);
                    const $paramInput = $row.find('.param-input[data-param="' + paramField + '"]');
                    const paramValue = parseFloat($paramInput.val()) || 0;
                    console.log(`Row ${index} ${param}:`, paramValue);

                    weightedSums[paramField] += paramValue * quantity;
                });
            });

            console.log('Total quantity:', totalQuantity);
            console.log('Weighted sums after calculation:', weightedSums);

            // Update footer
            $table.find('.total-quantity').html('<strong>' + totalQuantity.toFixed(2) + '</strong>');

            commodityParameters.forEach(param => {
                const paramField = createParamField(param);
                const avg = totalQuantity > 0 ? (weightedSums[paramField] / totalQuantity) : 0;
                console.log(`Average for ${param}:`, avg);

                const $avgCell = $table.find('.avg-' + paramField);
                console.log('Average cell found:', $avgCell.length);
                $avgCell.html('<strong>' + avg.toFixed(4) + '</strong>');
            });
        }

        // Helper function to create consistent param field names
        function createParamField(param) {
            return param
                .toLowerCase()
                .replace(/[^a-zA-Z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
        }


        // Function to update combined average
        function updateCombinedAverage() {
            const $tables = $('.commodity-table');
            let grandTotalQuantity = 0;
            const grandWeightedSums = {};

            // Get common parameters from all tables
            const commonParameters = [];
            $tables.each(function () {
                const $addButton = $(this).find('.add-location-row');
                const commodityParameters = $addButton.data('parameters') || [];
                commodityParameters.forEach(param => {
                    const paramField = param.replace(/[^a-zA-Z0-9]/g, '_');
                    if (!commonParameters.includes(paramField)) {
                        commonParameters.push(paramField);
                    }
                });
            });

            // Initialize grand sums
            commonParameters.forEach(paramField => {
                grandWeightedSums[paramField] = 0;
            });

            // Calculate grand totals
            $tables.each(function () {
                const totalQuantity = parseFloat($(this).find('.total-quantity').text()) || 0;
                grandTotalQuantity += totalQuantity;

                commonParameters.forEach(paramField => {
                    const avgElement = $(this).find('.avg-' + paramField);
                    if (avgElement.length) {
                        const avg = parseFloat(avgElement.text()) || 0;
                        grandWeightedSums[paramField] += avg * totalQuantity;
                    }
                });
            });

            // Update combined average
            commonParameters.forEach(paramField => {
                const combinedAvg = grandTotalQuantity > 0 ? (grandWeightedSums[paramField] / grandTotalQuantity) : 0;
                $('.combined-avg-' + paramField).html('<strong>' + combinedAvg.toFixed(4) + '</strong>');
            });
        }

    });
</script>