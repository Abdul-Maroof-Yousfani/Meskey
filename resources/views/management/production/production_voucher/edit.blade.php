<form action="{{ route('job-order-rm-qc.update', $qc->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.job_order_rm_qc') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC No:</label>
                        <input type="text" name="qc_no" class="form-control" value="{{ $qc->qc_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC Date:</label>
                        <input type="date" name="qc_date" class="form-control" 
                               value="{{ $qc->qc_date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <select name="job_order_id" onchange="loadJobOrderDetails()" class="form-control" id="jobOrderSelect" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}" 
                                        {{ $qc->job_order_id == $jobOrder->id ? 'selected' : '' }}>
                                    {{ $jobOrder->job_order_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- <div class="col-md-3">
                    <div class="form-group">
                        <label>Mill:</label>
                        <input type="text" name="mill" class="form-control" value="{{ $qc->mill }}" required>
                    </div>
                </div> -->
            </div>
            
            <!-- Job Order Details Section -->
            <div id="JobOrderDetail">
                @include('management.production.job_order_raw_material_qc.partials.job_order_detail', [
                    'jobOrder' => $qc->jobOrder,
                    'companyLocations' => $companyLocations,
                    'products' => $products,
                    'qc' => $qc
                ])
            </div>
        </div>

        <!-- QC Commodities Section -->
        <div class="col-md-12" id="qcCommoditiesSection">
            @include('management.production.job_order_raw_material_qc.partials.edit_qc_commodities_tables', [
                'qc' => $qc,
                'products' => $products,
                'sublocations' => $sublocations
            ])
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update QC</button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();
    
    // Update job order quantity on page load
    updateJobOrderQty();
    
    // Initial calculations for QC tables
    $('.commodity-table').each(function() {
        const commodityId = $(this).data('commodity-id');
        calculateWeightedAverage(commodityId);
    });
    updateCombinedAverage();
});

function loadJobOrderDetails() {
    const jobOrderId = $('#jobOrderSelect').val();
    
    if (jobOrderId) {
        // Show loading
        $('#JobOrderDetail').html('<div class="text-center py-4"><i class="ft-loader spinner"></i> Loading job order details...</div>');
        $('#qcCommoditiesSection').html('<div class="alert alert-info">Select materials to load QC tables</div>');
        
        // Load job order details via AJAX
        $.ajax({
            url: '/',
            type: 'GET',
            data: {
                job_order_id: jobOrderId,
                qc_id: {{ $qc->id }}
            },
            success: function(response) {
                $('#JobOrderDetail').html(response);
                
                // Re-initialize Select2 for materials dropdown
                $('#commoditiesSelect').select2();
                
                // Update job order quantity
                updateJobOrderQty();
                
                // Clear QC tables section
                $('#qcCommoditiesSection').html('<div class="alert alert-info">Select materials to load QC tables</div>');
            },
            error: function() {
                $('#JobOrderDetail').html('<div class="alert alert-danger">Error loading job order details</div>');
            }
        });
    }
}

function updateJobOrderQty() {
    var selected = $('#locationSelect').find(':selected');
    var totalKg = selected.data('totalkg');
    
    if (totalKg) {
        $('#jobOrderQty').val(totalKg + ' Kgs');
    } else {
        $('#jobOrderQty').val('');
    }
}

// Run on location change
$(document).on('change', '#locationSelect', function() {
    updateJobOrderQty();
});

// Materials Selection - Load QC Tables via AJAX
$(document).on('change', '#commoditiesSelect', function() {
    const selectedCommodities = $(this).val();
    const jobOrderId = $('#jobOrderSelect').val();
    const companyLocationId = $('#locationSelect').val();
    
    if (selectedCommodities && selectedCommodities.length > 0 && jobOrderId) {
        // Show loading
        $('#qcCommoditiesSection').html('<div class="text-center py-4"><i class="ft-loader spinner"></i> Loading QC tables...</div>');
        
        // Load QC tables via AJAX with existing data
        $.ajax({
            url: '{{ route("load_qc_commodities_tables") }}',
            type: 'GET',
            data: {
                commodities: selectedCommodities,
                job_order_id: jobOrderId,
                company_location_id: companyLocationId,
                qc_id: {{ $qc->id }}
            },
            success: function(response) {
                $('#qcCommoditiesSection').html(response);
                
                // Initial calculations
                $('.commodity-table').each(function() {
                    const commodityId = $(this).data('commodity-id');
                    calculateWeightedAverage(commodityId);
                });
                updateCombinedAverage();
            },
            error: function() {
                $('#qcCommoditiesSection').html('<div class="alert alert-danger">Error loading QC tables</div>');
            }
        });
    } else {
        $('#qcCommoditiesSection').html('<div class="alert alert-warning">Please select at least one material</div>');
    }
});

// Add location row
function addLocationRow(commodityId) {
    const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
    const $tbody = $table.find('tbody');
    const $lastRow = $tbody.find('tr').last();
    
    // Clone the last row
    const $newRow = $lastRow.clone();
    
    // Update index in names
    const newIndex = $tbody.find('tr').length;
    $newRow.find('input, select').each(function() {
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

// Remove location row
$(document).on('click', '.remove-row', function() {
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

// Re-index rows after removal
function reindexCommodityRows(commodityId) {
    const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
    const $tbody = $table.find('tbody');
    
    $tbody.find('tr').each(function(index) {
        const $row = $(this);
        $row.find('input, select').each(function() {
            const $element = $(this);
            const name = $element.attr('name');
            if (name) {
                const newName = name.replace(/\[locations\]\[\d+\]/, `[locations][${index}]`);
                $element.attr('name', newName);
            }
        });
    });
}

// Auto-calculate totals
$(document).on('input', '.suggested-quantity, .param-input', function() {
    const commodityId = $(this).closest('.commodity-table').data('commodity-id');
    calculateWeightedAverage(commodityId);
    updateCombinedAverage();
});

// Function to calculate weighted average
function calculateWeightedAverage(commodityId) {
    const $table = $('.commodity-table[data-commodity-id="' + commodityId + '"]');
    const $rows = $table.find('tbody tr');
    let totalQuantity = 0;
    const weightedSums = {};
    
    // Get parameters for this commodity
    const $addButton = $table.find('.add-location-row');
    const commodityParameters = $addButton.data('parameters') || [];
    
    // Initialize weighted sums
    commodityParameters.forEach(param => {
        const paramField = param.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
        weightedSums[paramField] = 0;
    });
    
    // Calculate totals
    $rows.each(function() {
        const quantity = parseFloat($(this).find('.suggested-quantity').val()) || 0;
        totalQuantity += quantity;
        
        commodityParameters.forEach(param => {
            const paramField = param.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
            const paramValue = parseFloat($(this).find('.param-input[data-param="' + paramField + '"]').val()) || 0;
            weightedSums[paramField] += paramValue * quantity;
        });
    });
    
    // Update footer
    $table.find('.total-quantity').html('<strong>' + totalQuantity.toFixed(2) + '</strong>');
    
    commodityParameters.forEach(param => {
        const paramField = param.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
        const avg = totalQuantity > 0 ? (weightedSums[paramField] / totalQuantity) : 0;
        $table.find('.avg-' + paramField).html('<strong>' + avg.toFixed(4) + '</strong>');
    });
}

// Function to update combined average
function updateCombinedAverage() {
    const $tables = $('.commodity-table');
    let grandTotalQuantity = 0;
    const grandWeightedSums = {};
    
    // Get common parameters from all tables
    const commonParameters = [];
    $tables.each(function() {
        const $addButton = $(this).find('.add-location-row');
        const commodityParameters = $addButton.data('parameters') || [];
        commodityParameters.forEach(param => {
            const paramField = param.replace(/[^a-zA-Z0-9]/g, '_').toLowerCase();
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
    $tables.each(function() {
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