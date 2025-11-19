<form action="{{ route('job-order-rm-qc.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.job_order_rm_qc') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC No:</label>
                        <input type="text" name="qc_no" class="form-control"
                            value="RMQC-{{ \Carbon\Carbon::now()->format('YmdHis') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC Date:</label>
                        <input type="date" name="qc_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <select name="job_order_id" class="form-control" id="jobOrderSelect" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}">{{ $jobOrder->job_order_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" class="form-control" id="locationSelect" required>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mill:</label>
                        <input type="text" name="mill" class="form-control" placeholder="e.g., A-45" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodities:</label>
                        <select name="commodities[]" class="form-control select2" multiple id="commoditiesSelect"
                            required>
                            <option value="">Select Commodities</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- QC Commodities Section -->
        <div class="col-md-12" id="qcCommoditiesSection">
            <!-- Partial will be loaded here via AJAX -->
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save QC</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Initialize Select2
        // $('.select2').select2();

        let sublocations = [];

        // Job Order Selection
        $('#jobOrderSelect').change(function () {
            const jobOrderId = $(this).val();
            if (jobOrderId) {
                $.get('{{ route("get.job_order_details", "") }}/' + jobOrderId, function (data) {
                    sublocations = data.sublocations;
                });
            }
        });

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
</script>