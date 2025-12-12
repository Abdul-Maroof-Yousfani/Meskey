@extends('management.layouts.master')
@section('title')
    Edit Production Voucher
@endsection
@section('styles')

@endsection

@section('content')
<style>
    /* Production Voucher Input/Output List Styles */
    .slot-header-row {
        background-color: #d1ecf1 !important;
        /* border-top: 5px solid white !important; */
    }
    .slot-header-cell {
        font-weight: bold;
    }
    
    .head-product-row {
        background-color: #cce5ff !important;
    }
    .head-product-cell {
        font-weight: bold;
    }
    .by-product-row {
        background-color: #d1ecf1 !important;
    }
    .by-product-cell {
        font-weight: bold;
    }
    .commodity-total-row {
        /* background-color: #fff3cd !important; */
    }
    .commodity-total-cell {
        font-weight: bold;
        padding-left: 30px;
    }
    .commodity-total-qty {
        /* font-weight: bold; */
        /* text-align: right; */
    }
    .grand-total-row {
        background-color: #d4edda !important;
    }
    .grand-total-cell {
        font-weight: bold;
        text-align: center;
    }
    .grand-total-commodity-row {
        background-color: #fff3cd !important;
    }
    .grand-total-commodity-cell {
        font-weight: bold;
    }
    .bg-light-warning {
        background-color: #fff3cd !important;
    }

    /* Grand Total Summary Dashboard Cards */
    .dashboard-card.summary-box {
        background: #f8f9fa!important;
        border-radius: 8px;
        padding: 20px;
        position: relative;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
        text-align: center;
    }

    .summary-box-info {
        background: #e7f3ff;
    }

    .summary-box-success {
        background: #e8f5e9;
    }

    .summary-box-primary {
        background: #e3f2fd;
    }

    .dashboard-card.summary-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .dashboard-card.summary-box .card-number {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 8px;
        margin-top: 0;
    }

    .dashboard-card.summary-box .card-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
        line-height: 1.2;
    }

    .dashboard-card.summary-box .card-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 0;
        line-height: 1.3;
    }
</style>
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Edit Production Voucher</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('production-voucher.index') }}" class="btn btn-primary position-relative">
                        <i class="ft-arrow-left mr-1"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="row">
                 <!-- Production Input and Output Buttons -->
        <div class="col-md-12 mt-3">
            <div class="row">
                <div class="col-md-4">
                                            <button type="button" class="btn btn-success btn-block" onclick="openModal(this, '{{ route('production-voucher.input.form', $productionVoucher->id) }}', 'Create Production Input', false, '50%')">
                        <i class="ft-plus"></i> Create Production Input
                    </button>
                </div>
                <div class="col-md-4">
                                            <button type="button" class="btn btn-info btn-block" onclick="openModal(this, '{{ route('production-voucher.output.form', $productionVoucher->id) }}', 'Create Production Output', false, '50%')">
                        <i class="ft-plus"></i> Create Production Output
                    </button>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-warning btn-block" onclick="openModal(this, '{{ route('production-voucher.slot.form', $productionVoucher->id) }}', 'Create Production Slot', false, '50%')">
                        <i class="ft-plus"></i> Create Production Slot
                    </button>
                </div>
            </div>
        </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Edit Production Voucher #{{ $productionVoucher->prod_no }}</h4>
                        </div>
                        <div class="card-body">
<form action="{{ route('production-voucher.update', $productionVoucher->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
                                <input type="hidden" id="url" value="{{ route('production-voucher.edit', $productionVoucher->id) }}" />

    <div class="row form-mar">
        
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Voucher</h6>
            <div class="row">
                <div class="col-md-3">
                    <fieldset>
                        <label>Prod. No:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Prod. No</button>
                            </div>
                            <input type="text" readonly name="prod_no" class="form-control" value="{{ $productionVoucher->prod_no }}">
                        </div>
                    </fieldset>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Prod. Date:</label>
                        <input type="date" name="prod_date" class="form-control" value="{{ $productionVoucher->prod_date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" id="location_id" class="form-control select2" required onchange="loadCommoditiesByLocation()">
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" {{ $productionVoucher->location_id == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" id="product_id" class="form-control select2" required onchange="loadJobOrdersByLocation()">
                            <option value="">Select Commodity</option>
                            @php
                                $locationId = $productionVoucher->location_id ?? null;
                                $currentProductId = $productionVoucher->product_id ?? null;
                                
                                // Get commodities for current location
                                $commodities = [];
                                if ($locationId) {
                                    $commodities = \App\Models\Production\JobOrder\JobOrder::with('product')
                                        ->where('status', 1)
                                        ->whereHas('packingItems', function ($q) use ($locationId) {
                                            $q->where('company_location_id', $locationId);
                                        })
                                        ->get()
                                        ->pluck('product_id')
                                        ->unique()
                                        ->filter()
                                        ->map(function ($productId) {
                                            return \App\Models\Product::find($productId);
                                        })
                                        ->filter();
                                }
                            @endphp
                            @foreach($commodities as $commodity)
                                <option value="{{ $commodity->id }}" {{ $currentProductId == $commodity->id ? 'selected' : '' }}>
                                    {{ $commodity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Ord. No:</label>
                        <select name="job_order_id[]" id="job_order_id" class="form-control select2" multiple="multiple" required onchange="loadPackingItems()">
                            <option value="">Select Job Order</option>
                            @php
                                $locationId = $productionVoucher->location_id ?? null;
                                $currentProductId = $productionVoucher->product_id ?? null;
                                $selectedJobOrderIds = $productionVoucher->jobOrders->pluck('id')->toArray();
                                
                                // Get job orders for current location and product
                                $jobOrders = [];
                                if ($locationId && $currentProductId) {
                                    $jobOrders = \App\Models\Production\JobOrder\JobOrder::with('product')
                                        ->where('status', 1)
                                        ->where('product_id', $currentProductId)
                                        ->whereHas('packingItems', function ($q) use ($locationId) {
                                            $q->where('company_location_id', $locationId);
                                        })
                                        ->get();
                                }
                            @endphp
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}" {{ in_array($jobOrder->id, $selectedJobOrderIds) ? 'selected' : '' }}>
                                    {{ $jobOrder->job_order_no }}@if($jobOrder->ref_no) ({{ $jobOrder->ref_no }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Produced QTY (kg):</label>
                        <input type="number" name="produced_qty_kg" class="form-control" step="0.01" min="0.01" value="{{ $productionVoucher->produced_qty_kg }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supervisor:</label>
                        <select name="supervisor_id" id="supervisor_id" class="form-control select2">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ $productionVoucher->supervisor_id == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Labor (per kg):</label>
                        <input type="number" name="labor_cost_per_kg" class="form-control" step="0.0001" min="0" value="{{ $productionVoucher->labor_cost_per_kg }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Overhead (per kg):</label>
                        <input type="number" name="overhead_cost_per_kg" class="form-control" step="0.0001" min="0" value="{{ $productionVoucher->overhead_cost_per_kg }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" id="status" class="form-control select2" required>
                            <option value="draft" {{ $productionVoucher->status == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="completed" {{ $productionVoucher->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="approved" {{ $productionVoucher->status == 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ $productionVoucher->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Packing Items Display Section -->
        @php
            $locationId = $productionVoucher->location_id ?? null;
            $selectedJobOrderIds = $productionVoucher->jobOrders->pluck('id')->toArray();
            $packingItems = [];
            $producedByJobOrder = [];
            $producedDetailsByJobOrder = [];
            
            if ($locationId && count($selectedJobOrderIds) > 0) {
                $packingItems = \App\Models\Production\JobOrder\JobOrderPackingItem::with([
                    'jobOrder.product',
                    'bagType',
                    'bagCondition',
                    'companyLocation',
                    'brand'
                ])
                    ->whereIn('job_order_id', $selectedJobOrderIds)
                    ->where('company_location_id', $locationId)
                    ->get();
                
                // Calculate produced quantity for each job order (location-wise)
                foreach ($selectedJobOrderIds as $jobOrderId) {
                    $outputs = \App\Models\Production\ProductionOutput::with([
                        'productionVoucher',
                        'productionVoucher.location',
                        'storageLocation',
                        'storageLocation.arrivalLocation',
                        'product',
                        'brand'
                    ])
                        ->where('job_order_id', $jobOrderId)
                        ->whereHas('productionVoucher', function($q) use ($locationId) {
                            $q->where('location_id', $locationId);
                        })
                        ->get();
                    
                    $producedQty = $outputs->sum('qty');
                    $producedByJobOrder[$jobOrderId] = $producedQty ?? 0;
                    $producedDetailsByJobOrder[$jobOrderId] = $outputs;
                }
            }
        @endphp
        @if(count($packingItems) > 0)
        <div class="col-md-12 mt-3" id="packingItemsSection">
            <h6 class="header-heading-sepration">Packing Items</h6>
            @include('management.production.production_voucher.partials.packing_items_table', [
                'packingItems' => $packingItems,
                'producedByJobOrder' => $producedByJobOrder,
                'producedDetailsByJobOrder' => $producedDetailsByJobOrder,
                'locationId' => $locationId,
                'currentProductionVoucherId' => $productionVoucher->id ?? null
            ])
        </div>
        @else
        <div class="col-md-12 mt-3" id="packingItemsSection" style="display: none;">
            <h6 class="header-heading-sepration">Packing Items</h6>
            <div id="packingItemsContainer">
                <!-- Packing items will be loaded here via fetchDynamicHTML -->
            </div>
        </div>
        @endif

       

        <!-- Production Inputs List -->
        <div class="col-md-12 mt-4">
            <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                <div class="col-md-6">
                    <h6 class="m-0">Production Inputs</h6>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-warning btn-sm" onclick="openModal(this, '{{ route('production-voucher.input.form', $productionVoucher->id) }}', 'Create Production Input', false, '50%')">
                        <i class="ft-plus"></i> Create Production Input
                    </button>
                </div>
            </div>
  
                            <div id="productionInputsFilterForm" class="form">
                               
                            </div>
            <div class="table-responsive" id="productionInputsTable">
                <table class="table table-bordered" >
                     <thead>
                        <tr>
                            <th>Commodity</th>
                            <th>Location</th>
                            <th>Qty (kg)</th>
                                                    <th>%</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody >
                    </tbody>
                </table>
                <!-- @include('management.production.production_voucher.input.getList', ['inputs' => $productionVoucher->inputs, 'outputs' => $productionVoucher->outputs, 'productionVoucher' => $productionVoucher]) -->

            </div>
        </div>

        <!-- Production Outputs List -->
        <div class="col-md-12 mt-4">
            <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                <div class="col-md-6">
                    <h6 class="m-0">Production Outputs</h6>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-warning btn-sm" onclick="openModal(this, '{{ route('production-voucher.output.form', $productionVoucher->id) }}', 'Create Production Output', false, '50%')">
                        <i class="ft-plus"></i> Create Production Output
                    </button>
                </div>
            </div>
            <div id="productionOutputsFilterForm" class="form">
                                
                            </div>
                                    <div id="productionOutputsTable">
                                                @include('management.production.production_voucher.output.getList', [
                                            'headProductOutputs' => $productionVoucher->outputs->where('product_id', $productionVoucher->jobOrder->product_id ?? null),
                                            'otherProductOutputs' => $productionVoucher->outputs->where('product_id', '!=', $productionVoucher->jobOrder->product_id ?? null),
                                            'productionVoucher' => $productionVoucher,
                                            'headProductId' => $productionVoucher->jobOrder->product_id ?? null,
                                            'inputs' => $productionVoucher->inputs
                                        ])
                                    </div>
                                </div>

                                <!-- Production Slots Section -->
                                <div class="col-md-12 mt-4">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                                            <div class="col-md-6">
                                            <h6 class="m-0">Production Slots</h6>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <button type="button" class="btn btn-warning btn-sm" onclick="openModal(this, '{{ route('production-voucher.slot.form', $productionVoucher->id) }}', 'Create Production Slot', false, '70%')">
                                                <i class="ft-plus"></i> Create Production Slot
                                            </button>
                                        </div>
                                    </div>
            <div class="table-responsive">
            <div id="productionOutputsFilterForm" class="form">
                               
                            </div>
                <table class="table table-bordered" id="productionSlotsTable">
                    <thead>
                        <tr>
                                                    <th>Date</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Breaks</th>
                                                    <th>Status</th>
                                                    <th>Attachment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                                            <tbody id="productionSlotsTable">
                                                @include('management.production.production_voucher.slot.getList', ['slots' => $productionVoucher->slots, 'productionVoucher' => $productionVoucher])
                    </tbody>
                </table>
        </div>
    </div>

                                <div class="row bottom-button-bar mt-4">
                                    <div class="col-12 text-right">
                                        <a href="{{ route('production-voucher.index') }}" class="btn btn-danger mr-2">Cancel</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Production Voucher</button>
        </div>
    </div>
</form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
<script>
filterationCommon('{{ route("get.production-voucher-outputs", $productionVoucher->id) }}', false, 'productionOutputsTable', 'productionOutputsFilterForm');
filterationCommon('{{ route("get.production-voucher-inputs", $productionVoucher->id) }}', false, 'productionInputsTable', 'productionInputsFilterForm');
filterationCommon('{{ route("get.production-voucher-slots", $productionVoucher->id) }}', false, 'productionSlotsFilterForm', 'productionSlotsTable');

    function loadCommoditiesByLocation(triggerChange = true) {
        const locationId = $('#location_id').val();
        const commoditySelect = $('#product_id');
        const jobOrderSelect = $('#job_order_id');

        // Clear commodity and job order dropdowns
        commoditySelect.empty().append('<option value="">Select Commodity</option>');
        jobOrderSelect.empty().append('<option value="">Select Job Order</option>');
        $('#packingItemsSection').hide();
        $('#packingItemsBody').empty();

        if (!locationId) {
            if (triggerChange) {
                commoditySelect.trigger('change');
                jobOrderSelect.trigger('change');
            }
            return Promise.resolve();
        }

        // Show loading
        commoditySelect.prop('disabled', true);

        return $.ajax({
            url: '{{ route("production-voucher.get-commodities-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.commodities && response.commodities.length > 0) {
                    $.each(response.commodities, function(index, commodity) {
                        commoditySelect.append(
                            $('<option></option>')
                                .attr('value', commodity.id)
                                .text(commodity.name)
                        );
                    });
                } else {
                    commoditySelect.append('<option value="">No Commodities Found</option>');
                }
                // Reinitialize select2 to show selected value if it exists
                commoditySelect.trigger('change.select2');
                if (triggerChange) {
                    commoditySelect.trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error loading commodities:', xhr);
                commoditySelect.append('<option value="">Error loading commodities</option>');
            },
            complete: function() {
                commoditySelect.prop('disabled', false);
            }
        });
    }

    function loadJobOrdersByLocation(triggerChange = true) {
        const locationId = $('#location_id').val();
        const productId = $('#product_id').val();
        const jobOrderSelect = $('#job_order_id');

        // Clear existing options
        jobOrderSelect.empty().append('<option value="">Select Job Order</option>');
        $('#packingItemsSection').hide();
        $('#packingItemsBody').empty();

        if (!locationId || !productId) {
            if (triggerChange) {
                jobOrderSelect.trigger('change');
            }
            return Promise.resolve();
        }

        // Show loading
        jobOrderSelect.prop('disabled', true);

        return $.ajax({
            url: '{{ route("production-voucher.get-job-orders-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                product_id: productId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.jobOrders && response.jobOrders.length > 0) {
                    $.each(response.jobOrders, function(index, jobOrder) {
                        jobOrderSelect.append(
                            $('<option></option>')
                                .attr('value', jobOrder.id)
                                .text(jobOrder.job_order_no + (jobOrder.ref_no ? ' (' + jobOrder.ref_no + ')' : ''))
                        );
                    });
                } else {
                    jobOrderSelect.append('<option value="">No Job Orders Found</option>');
                }
                // Reinitialize select2 to show selected values
                jobOrderSelect.trigger('change.select2');
                if (triggerChange) {
                    jobOrderSelect.trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error loading job orders:', xhr);
                jobOrderSelect.append('<option value="">Error loading job orders</option>');
            },
            complete: function() {
                jobOrderSelect.prop('disabled', false);
            }
        });
    }

    function loadPackingItems() {
        const jobOrderIds = $('#job_order_id').val();
        const locationId = $('#location_id').val();

        if (!jobOrderIds || !locationId || jobOrderIds.length === 0) {
            $('#packingItemsSection').hide();
            $('#packingItemsContainer').empty();
            return;
        }

        $('#packingItemsSection').show();

        // Use fetchDynamicHTML to load packing items with produced quantity
        fetchDynamicHTML(
            '{{ route("production-voucher.get-packing-items-with-produced") }}',
            'packingItemsContainer',
            {
                job_order_ids: jobOrderIds,
                location_id: locationId,
                current_production_voucher_id: {{ $productionVoucher->id ?? 'null' }}
            },
            {
                method: 'POST',
                loader: true,
                loadingText: 'Loading packing items...'
            }
        );
    }

    $(document).ready(function () {
        $('.select2').select2();

        // Override filterationCommon for inputs/outputs/slots to update tbody instead of #filteredData (arrival_location pattern)
        var originalFilterationCommon = window.filterationCommon;
        // window.filterationCommon = function(url, loadmore, appenddiv) {
        //     // Check if it's inputs/outputs/slots route
        //     if (url && (url.includes('get-production-voucher-inputs') || url.includes('get-production-voucher-outputs') || url.includes('get-production-voucher-slots'))) {
        //         // Custom handler: Load in wrapper div, then update tbody
        //         var targetTbody = url.includes('get-production-voucher-inputs') ? 'productionInputsTable' : 
        //                          url.includes('get-production-voucher-outputs') ? 'productionOutputsTable' : 
        //                          'productionSlotsTable';
                
        //         $.ajax({
        //             url: url,
        //             type: 'POST',
        //             data: {},
        //             success: function(data) {
        //                 $('#' + targetTbody).html(data);
        //             },
        //             error: function(xhr, status, error) {
        //                 console.error(error);
        //             }
        //         });
        //     } else {
        //         // Default behavior for other routes
        //         if (originalFilterationCommon) {
        //             originalFilterationCommon(url, loadmore, appenddiv);
        //         }
        //     }
        // };
    });

    function deleteProductionInput(voucherId, inputId) {
        if (confirm('Are you sure you want to delete this production input?')) {
            $.ajax({
                url: '{{ route("production-voucher.input.destroy", [":id", ":inputId"]) }}'
                    .replace(':id', voucherId)
                    .replace(':inputId', inputId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Refresh inputs list directly (arrival_location pattern - getList route)
                    $.post('{{ route("get.production-voucher-inputs", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                        $('#productionInputsTable').html(data);
                    });
                    showNotification('success', response.success);
                }
            });
        }
    }

    function deleteProductionOutput(voucherId, outputId) {
        if (confirm('Are you sure you want to delete this production output?')) {
            $.ajax({
                url: '{{ route("production-voucher.output.destroy", [":id", ":outputId"]) }}'
                    .replace(':id', voucherId)
                    .replace(':outputId', outputId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Refresh outputs list directly (arrival_location pattern - getList route)
                    $.post('{{ route("get.production-voucher-outputs", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                        $('#productionOutputsTable').html(data);
                    });
                    showNotification('success', response.success);
                }
            });
        }
    }

    function deleteProductionSlot(voucherId, slotId) {
        if (confirm('Are you sure you want to delete this production slot?')) {
            $.ajax({
                url: '{{ route("production-voucher.slot.destroy", [":id", ":slotId"]) }}'
                    .replace(':id', voucherId)
                    .replace(':slotId', slotId),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Refresh slots list directly (arrival_location pattern - getList route)
                    $.post('{{ route("get.production-voucher-slots", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                        $('#productionSlotsTable').html(data);
                    });
                    showNotification('success', response.success);
                }
            });
        }
    }
</script>
@endsection
