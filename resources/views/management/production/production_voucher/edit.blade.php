@extends('management.layouts.master')
@section('title')
    Edit Production Voucher
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
        <!-- <div class="col-md-12 mt-3">
            <div class="row">
            <div class="col-md-4">
                    <button type="button" class="btn btn-warning btn-block" onclick="openModal(this, '{{ route('production-voucher.slot.form', $productionVoucher->id) }}', 'Create Production Slot', false, '50%')">
                        <i class="ft-plus"></i> Create Production Slot
                    </button>
                </div> 
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
               
            </div>
        </div> -->
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
                        <select name="location_id" id="location_id" class="form-control select2" required onchange="loadData();loadCommoditiesByLocation();">
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
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}" {{ $productionVoucher->plant_id == $plant->id ? 'selected' : '' }}>
                                    {{ $plant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Head Product:</label>
                        <select name="product_id" id="product_id" class="form-control select2" required onchange="loadJobOrdersByLocation();loadHeadProductsData();">
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
                        <select name="job_order_id[]" id="job_order_id" class="form-control select2" multiple required onchange="loadPackingItems();loadHeadProductsData();loadData();">
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
                        <label>By Product:</label>
                        <select name="by_product_id" id="by_product_id" onchange="loadData()" class="form-control select2">
                            <option value="">Select By Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ $productionVoucher->by_product_id == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- <div class="col-md-3">
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
                </div> -->

                <!-- <div class="col-md-3">
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
                </div> -->

                <!-- <div class="col-md-3">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" id="status" class="form-control select2" required>
                            <option value="draft" {{ $productionVoucher->status == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="completed" {{ $productionVoucher->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="approved" {{ $productionVoucher->status == 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    </div>
                </div> -->

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
                    // 'bagType',
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

       


        <!-- Production Inputs Section -->
        <div id="productionInputsSection" class="mt-3 col-12">
            <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                <div class="col-md-12">
                    <h6 class="m-0">Production Inputs</h6>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div id="productionInputsTable">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="col-2">Commodity</th>
                                    <th class="col-2">Location</th>
                                    <th class="col-1">Qty (kg)</th>
                                    <th class="col-1">Yield %</th>
                                    <th class="col-5">Remarks</th>
                                    <th class="col-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($productionVoucher->inputs && $productionVoucher->inputs->count() > 0)
                                    @foreach($productionVoucher->inputs as $input)
                                        <tr>
                                            <td>
                                                <select name="input_product_id[]" class="form-control select2" required>
                                                    <option value="">Select Commodity</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" {{ $input->product_id == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="input_location_id[]" class="form-control select2" required>
                                                    <option value="">Select Location</option>
                                                    @foreach($sublocations as $sublocation)
                                                        <option value="{{ $sublocation->id }}" {{ $input->location_id == $sublocation->id ? 'selected' : '' }}>
                                                            {{ $sublocation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="input_qty[]" class="form-control" step="0.01" min="0.01" required value="{{ $input->qty }}">
                                            </td>
                                            <td>
                                                <input type="number" name="input_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                                            </td>
                                            <td>
                                                <textarea name="input_remarks[]" class="form-control" rows="1">{{ $input->remarks }}</textarea>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-plus"></i></button>
                                                <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>
                                            <select name="input_product_id[]" class="form-control select2" required>
                                                <option value="">Select Commodity</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="input_location_id[]" class="form-control select2" required>
                                                <option value="">Select Location</option>
                                                @foreach($sublocations as $sublocation)
                                                    <option value="{{ $sublocation->id }}">{{ $sublocation->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="input_qty[]" class="form-control" step="0.01" min="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" name="input_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                                        </td>
                                        <td>
                                            <textarea name="input_remarks[]" class="form-control" rows="1"></textarea>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-plus"></i></button>
                                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>
                                        <strong>Total</strong>
                                    </td>
                                    <td></td>
                                    <td>
                                        <input type="number" name="input_total_qty[]" class="form-control" step="0.01" min="0.01" readonly>
                                    </td>
                                    <td>
                                        <input type="number" name="input_total_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Outputs Section -->
        <div id="productionOutputsSection" class="mt-3">
            <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                <div class="col-md-12">
                    <h6 class="m-0">Production Outputs</h6>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center" style="background-color: #93c3f2;">
                        <div class="col-md-12">
                            <h6 class="m-0">Head Products</h6>
                        </div>
                    </div>
                    <div id="productionHeadProductsTable">
                        @php
                            $headProduct = $productionVoucher->product;
                            $headProductOutputs = $productionVoucher->outputs->where('product_id', $productionVoucher->product_id);
                            $arrivalSubLocations = $sublocations;
                        @endphp
                        @include('management.production.production_voucher.partials.head_products_table', [
                            'headProduct' => $headProduct,
                            'arrivalSubLocations' => $arrivalSubLocations,
                            'brands' => $brands,
                            'jobOrders' => $jobOrders,
                            'headProductOutputs' => $headProductOutputs
                        ])
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center" style="background-color: #93c3f2;">
                        <div class="col-md-12">
                            <h6 class="m-0">By Products</h6>
                        </div>
                    </div>
                    <div id="productionByProductsTable">
                        @php
                            $byProduct = $productionVoucher->by_product_id ? \App\Models\Product::find($productionVoucher->by_product_id) : null;
                            $byProducts = getByProductsById($productionVoucher->by_product_id);
                            $byProductOutputs = $productionVoucher->by_product_id ? $productionVoucher->outputs : collect();
                            $arrivalSubLocations = $sublocations;
                        @endphp
                        @include('management.production.production_voucher.partials.by_product_table', [
                            'byProducts' => $byProducts,
                            'arrivalSubLocations' => $arrivalSubLocations,
                            'brands' => $brands,
                            'jobOrders' => $jobOrders,
                            'byProductOutputs' => $byProductOutputs
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="row bottom-button-bar mt-4 w-100 mx-auto">
            <div class="col-12 text-right">
                <!-- <a href="{{ route('production-voucher.index') }}" class="btn btn-danger mr-2">Cancel</a> -->
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
    $(document).ready(function () {
        // Initialize Select2 for all selects
        $('.select2').select2();
        
        // Initialize calculations on page load
        setTimeout(function() {
            initializeCalculations();
        }, 300);
    });

    function loadCommoditiesByLocation() {
        const locationId = $('#location_id').val();
        const commoditySelect = $('#product_id');
        const jobOrderSelect = $('#job_order_id');
        const plantSelect = $('#plant_id');

        // Clear commodity and job order dropdowns
        commoditySelect.empty().append('<option value="">Select Commodity</option>');
        jobOrderSelect.empty().append('<option value="">Select Job Order</option>');
        plantSelect.empty().append('<option value="">Select Plant</option>');
        $('#packingItemsSection').hide();
        $('#packingItemsBody').empty();

        if (!locationId) {
            commoditySelect.trigger('change');
            jobOrderSelect.trigger('change');
            plantSelect.trigger('change');
            return;
        }

        // Load plants for this location
        loadPlantsByLocation();

        // Show loading
        commoditySelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-commodities-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.commodities && response.commodities.length > 0) {
                    $.each(response.commodities, function (index, commodity) {
                        commoditySelect.append(
                            $('<option></option>')
                                .attr('value', commodity.id)
                                .text(commodity.name)
                        );
                    });
                } else {
                    commoditySelect.append('<option value="">No Commodities Found</option>');
                }
                commoditySelect.trigger('change');
            },
            error: function (xhr) {
                console.error('Error loading commodities:', xhr);
                commoditySelect.append('<option value="">Error loading commodities</option>');
            },
            complete: function () {
                commoditySelect.prop('disabled', false);
            }
        });
    }

    function loadPlantsByLocation() {
        const locationId = $('#location_id').val();
        const plantSelect = $('#plant_id');

        if (!locationId) {
            plantSelect.empty().append('<option value="">Select Plant</option>');
            return;
        }

        plantSelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-plants-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                plantSelect.empty().append('<option value="">Select Plant</option>');
                if (response.plants && response.plants.length > 0) {
                    $.each(response.plants, function (index, plant) {
                        plantSelect.append(
                            $('<option></option>')
                                .attr('value', plant.id)
                                .text(plant.name)
                        );
                    });
                } else {
                    plantSelect.append('<option value="">No Plants Found</option>');
                }
                plantSelect.trigger('change');
            },
            error: function (xhr) {
                console.error('Error loading plants:', xhr);
                plantSelect.append('<option value="">Error loading plants</option>');
            },
            complete: function () {
                plantSelect.prop('disabled', false);
            }
        });
    }

    function loadJobOrdersByLocation() {
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

    $(document).on('click', '.copythis', function (e) {
        e.stopImmediatePropagation();

        var $originalRow = $(this).closest('tr');
        var $tbody = $originalRow.closest('tbody');
        var $table = $tbody.closest('table');
        var $container = $table.closest('div');
        var clone = $originalRow.clone();
        
        // Clear input values in cloned row (but keep readonly fields empty)
        clone.find('input[type="number"]:not([readonly]), input[type="text"]:not([readonly])').val('');
        clone.find('textarea').val('');
        clone.find('input[readonly]').val(''); // Clear readonly fields too
        clone.find('input[name="output_qty[]"]').removeData('manual-change'); // Reset manual change flag
        
        // Insert clone after the original row
        $originalRow.after(clone);
        
        // Initialize Select2 for cloned row - properly destroy and reinitialize
        clone.find('select').each(function() {
            var $select = $(this);
            
            // Destroy existing select2 instance if any
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            
            // Remove select2 containers and classes
            $select.siblings('.select2-container').remove();
            $select.removeClass('select2-hidden-accessible').removeAttr('data-select2-id');
            
            // Make sure it has select2 class
            if (!$select.hasClass('select2')) {
                $select.addClass('select2');
            }
        });
        
        // Reinitialize select2 after DOM is ready
        setTimeout(function() {
            clone.find('select.select2').each(function() {
                var $select = $(this);
                // Initialize select2 with proper settings
                $select.select2({
                    dropdownParent: $select.closest('.modal-body, .card-body, body')
                });
            });
        }, 100);
        
        // Function to determine which calculation to run
        function runAppropriateCalculation() {
            var containerId = $container.attr('id');
            
            // Check by container ID first
            if (containerId === 'productionInputsTable') {
                calculateProductionInputs();
            } else if (containerId === 'productionHeadProductsTable') {
                // Recalculate both head and by products since total outputs changed
                calculateHeadProducts();
                calculateByProducts();
            } else if (containerId === 'productionByProductsTable') {
                // Recalculate both head and by products since total outputs changed
                calculateHeadProducts();
                calculateByProducts();
            } else {
                // Check by looking at the table structure
                var hasYield = $table.find('th:contains("Yield %")').length > 0;
                var hasAvgWeight = $table.find('th:contains("Avg Weight")').length > 0;
                var hasStorage = $table.find('th:contains("Storage")').length > 0;
                var hasLocation = $table.find('th:contains("Location")').length > 0;
                
                if (hasYield && hasAvgWeight && hasStorage) {
                    // This could be Head Products or By Products
                    // Check if it's inside productionHeadProductsTable or productionByProductsTable
                    if ($table.closest('#productionHeadProductsTable').length > 0) {
                        calculateHeadProducts();
                        calculateByProducts(); // Recalculate both
                    } else if ($table.closest('#productionByProductsTable').length > 0) {
                        calculateHeadProducts(); // Recalculate both
                        calculateByProducts();
                    } else {
                        // Run both to be safe
                        calculateHeadProducts();
                        calculateByProducts();
                    }
                } else if (hasYield && hasLocation) {
                    // This looks like Production Inputs table
                    calculateProductionInputs();
                } else {
                    // Fallback: run all calculations
                    calculateProductionInputs();
                    calculateHeadProducts();
                    calculateByProducts();
                }
            }
        }
        
        // Immediately trigger calculations
        setTimeout(runAppropriateCalculation, 50);
        
        // Also attach direct event handlers to cloned row inputs for immediate calculation
        clone.find('input[name="output_qty[]"]').on('input change keyup', function() {
            $(this).data('manual-change', true);
            setTimeout(runAppropriateCalculation, 10);
        });
        
        clone.find('input[name="output_no_of_bags[]"], input[name="output_bag_size[]"]').on('input change keyup', function() {
            // Clear manual-change flag so qty can be recalculated
            $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
            setTimeout(runAppropriateCalculation, 10);
        });
    });

    $(document).on('click', '.removethis', function () {
        var $row = $(this).closest('tr');
        var $tbody = $row.closest('tbody');
        var $table = $tbody.closest('table');
        var $container = $table.closest('div');
        
        if ($tbody.find('tr').length > 1) {
            $row.remove();
            
            // Immediately trigger calculations after removing row
            setTimeout(function() {
                var containerId = $container.attr('id');
                
                if (containerId === 'productionInputsTable' || $container.find('#productionInputsTable').length > 0) {
                    calculateProductionInputs();
                } else if (containerId === 'productionHeadProductsTable' || $container.find('#productionHeadProductsTable').length > 0) {
                    // Recalculate both head and by products since total outputs changed
                    calculateHeadProducts();
                    calculateByProducts();
                } else if (containerId === 'productionByProductsTable' || $container.find('#productionByProductsTable').length > 0) {
                    // Recalculate both head and by products since total outputs changed
                    calculateHeadProducts();
                    calculateByProducts();
                } else {
                    // Fallback: run all calculations
                    calculateProductionInputs();
                    calculateHeadProducts();
                    calculateByProducts();
                }
            }, 50);
        } else {
            toastr.error('You cannot remove the last row');
            return false;
        }
    });

    function loadData() {
        const byProductId = $('[name="by_product_id"]').val();
        const locationId = $('[name="location_id"]').val();
        const jobOrderIds = $('#job_order_id').val();
        console.log(jobOrderIds);
        if (byProductId) {
            fetchDynamicHTML('{{ route('production-voucher.get-by-product-table') }}', 'productionByProductsTable', {
                by_product_id: byProductId,
                location_id: locationId,
                job_order_ids: jobOrderIds
            }, { 
                method: 'POST',
                onSuccess: function(response, target) {
                    target.html(response);
                    // Reinitialize calculations after table loads
                    setTimeout(function() {
                        initializeCalculations();
                    }, 300);
                }
            });
        }
    }
    
    function loadHeadProductsData() {
        const productId = $('[name="product_id"]').val();
        const locationId = $('[name="location_id"]').val();
        const jobOrderIds = $('#job_order_id').val();
        console.log(jobOrderIds);
        if (productId) {
            fetchDynamicHTML('{{ route('production-voucher.get-head-products-data') }}', 'productionHeadProductsTable', {
                product_id: productId,
                location_id: locationId,
                job_order_ids: jobOrderIds
            }, { 
                method: 'POST',
                onSuccess: function(response, target) {
                    target.html(response);
                    // Reinitialize calculations after table loads
                    setTimeout(function() {
                        initializeCalculations();
                    }, 300);
                }
            });
        }
    }

    // ========== REAL-TIME CALCULATIONS ==========
    
    // Helper function to round to 2 decimal places
    function roundToTwo(num) {
        return Math.round((num + Number.EPSILON) * 100) / 100;
    }

    // Calculate Production Inputs Yield and Totals
    function calculateProductionInputs() {
        const tbody = $('#productionInputsTable tbody');
        
        if (tbody.length === 0) return;
        
        let totalQty = 0;
        
        // First, calculate total input qty (excluding total row)
        tbody.find('tr').not(':last').each(function() {
            const qty = parseFloat($(this).find('input[name="input_qty[]"]').val()) || 0;
            totalQty += qty;
        });
        
        // Now calculate yield for each input row (total input = 100%)
        tbody.find('tr').not(':last').each(function() {
            const qtyInput = $(this).find('input[name="input_qty[]"]');
            const yieldInput = $(this).find('input[name="input_yield[]"]');
            
            const qty = parseFloat(qtyInput.val()) || 0;
            
            // Calculate yield: (input_qty / total_input_qty) * 100
            if (qty > 0 && totalQty > 0) {
                const yield = (qty / totalQty) * 100;
                yieldInput.val(roundToTwo(yield));
            } else {
                yieldInput.val('');
            }
        });
        
        // Update total row
        const totalQtyInput = tbody.find('tr:last').find('input[name="input_total_qty[]"]');
        const totalYieldInput = tbody.find('tr:last').find('input[name="input_total_yield[]"]');
        
        totalQtyInput.val(roundToTwo(totalQty));
        
        // Total yield should be 100% (since total input = 100%)
        if (totalQty > 0) {
            totalYieldInput.val(roundToTwo(100));
        } else {
            totalYieldInput.val('');
        }
    }

    // Calculate total outputs (head products + by products)
    function getTotalOutputs() {
        let totalOutputs = 0;
        
        // Sum all head products qty
        $('#productionHeadProductsTable tbody tr').not(':last').each(function() {
            const qty = parseFloat($(this).find('input[name="output_qty[]"]').val()) || 0;
            totalOutputs += qty;
        });
        
        // Sum all by products qty
        $('#productionByProductsTable tbody tr').each(function() {
            const row = $(this);
            // Skip total rows
            if (!row.find('strong').text().includes('Total') && !row.find('strong').text().includes('Commodity Total')) {
                const qty = parseFloat(row.find('input[name="output_qty[]"]').val()) || 0;
                totalOutputs += qty;
            }
        });
        
        return totalOutputs;
    }

    // Calculate Head Products Yield, Avg Weight, and Totals
    function calculateHeadProducts() {
        const tbody = $('#productionHeadProductsTable tbody');
        
        if (tbody.length === 0) return;
        
        // Get total outputs (head + by products) - this will be 100%
        const totalOutputs = getTotalOutputs();
        
        let totalQty = 0;
        let totalNoOfBags = 0;
        
        // Calculate for each product row (excluding total row)
        tbody.find('tr').not(':last').each(function() {
            const qtyInput = $(this).find('input[name="output_qty[]"]');
            const noOfBagsInput = $(this).find('input[name="output_no_of_bags[]"]');
            const bagSizeInput = $(this).find('input[name="output_bag_size[]"]');
            const avgWeightInput = $(this).find('input[name="output_avg_weight_per_bag[]"]');
            const yieldInput = $(this).find('input[name="output_yield[]"]');
            
            const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
            const bagSize = parseFloat(bagSizeInput.val()) || 0;
            
            // Calculate Qty = No of Bags × Bag Size (only if qty wasn't manually changed)
            if (!qtyInput.data('manual-change')) {
                if (noOfBags > 0 && bagSize > 0) {
                    const calculatedQty = noOfBags * bagSize;
                    qtyInput.val(roundToTwo(calculatedQty));
                } else if (noOfBags === 0 || bagSize === 0) {
                    qtyInput.val('');
                }
            }
            
            const qty = parseFloat(qtyInput.val()) || 0;
            
            // Calculate avg_weight_per_bag = qty / no_of_bags
            // Only calculate if both qty and no_of_bags are provided
            if (noOfBags > 0 && qty > 0) {
                const avgWeight = qty / noOfBags;
                avgWeightInput.val(roundToTwo(avgWeight));
            } else if (qty === 0 || noOfBags === 0) {
                avgWeightInput.val('');
            }
            
            // Calculate yield: (qty / total_outputs) * 100
            if (qty > 0 && totalOutputs > 0) {
                const yield = (qty / totalOutputs) * 100;
                yieldInput.val(roundToTwo(yield));
            } else {
                yieldInput.val('');
            }
            
            totalQty += qty;
            totalNoOfBags += noOfBags;
        });
        
        // Update total row - handle both input name variations
        const totalRow = tbody.find('tr:last');
        const totalQtyInput = totalRow.find('input[name="commodity_total_qty[]"], input[name="total_qty[]"]');
        const totalBagsInput = totalRow.find('input[name="commodity_total_no_of_bags[]"]');
        const totalYieldInput = totalRow.find('input[name="total_yield[]"]');
        
        if (totalQtyInput.length > 0) {
            totalQtyInput.val(roundToTwo(totalQty));
        }
        if (totalBagsInput.length > 0) {
            totalBagsInput.val(Math.round(totalNoOfBags));
        }
        
        // Calculate total yield: (total_qty / total_outputs) * 100
        if (totalYieldInput.length > 0) {
            if (totalQty > 0 && totalOutputs > 0) {
                const overallYield = (totalQty / totalOutputs) * 100;
                totalYieldInput.val(roundToTwo(overallYield));
            } else {
                totalYieldInput.val('');
            }
        }
    }

    // Calculate By Products Yield, Avg Weight, and Totals
    function calculateByProducts() {
        const tbody = $('#productionByProductsTable tbody');
        
        if (tbody.length === 0) return;
        
        // Get total outputs (head + by products) - this will be 100%
        const totalOutputs = getTotalOutputs();
        
        let grandTotalQty = 0;
        let grandTotalBags = 0;
        
        // Group rows by commodity (each commodity has its rows + total row)
        let currentCommodityRows = [];
        
        tbody.find('tr').each(function() {
            const row = $(this);
            
            // Check if it's a commodity total row
            if (row.find('strong').text().includes('Commodity Total')) {
                // Calculate commodity totals
                let commodityQty = 0;
                let commodityBags = 0;
                
                currentCommodityRows.forEach(function(commodityRow) {
                    const qty = parseFloat(commodityRow.find('input[name="output_qty[]"]').val()) || 0;
                    const bags = parseFloat(commodityRow.find('input[name="output_no_of_bags[]"]').val()) || 0;
                    commodityQty += qty;
                    commodityBags += bags;
                });
                
                // Update commodity total row
                row.find('input[name="commodity_total_qty[]"]').val(roundToTwo(commodityQty));
                row.find('input[name="commodity_total_no_of_bags[]"]').val(Math.round(commodityBags));
                
                // Calculate commodity yield: (commodity_qty / total_outputs) * 100
                if (commodityQty > 0 && totalOutputs > 0) {
                    const commodityYield = (commodityQty / totalOutputs) * 100;
                    row.find('input[name="total_yield[]"]').val(roundToTwo(commodityYield));
                } else {
                    row.find('input[name="total_yield[]"]').val('');
                }
                
                grandTotalQty += commodityQty;
                grandTotalBags += commodityBags;
                currentCommodityRows = [];
            } 
            // Check if it's grand total row
            else if (row.hasClass('table-success') || row.find('strong').text().includes('Total')) {
                // This will be handled after the loop
            }
            // Regular product row
            else {
                const qtyInput = row.find('input[name="output_qty[]"]');
                const noOfBagsInput = row.find('input[name="output_no_of_bags[]"]');
                const bagSizeInput = row.find('input[name="output_bag_size[]"]');
                const avgWeightInput = row.find('input[name="output_avg_weight_per_bag[]"]');
                const yieldInput = row.find('input[name="output_yield[]"]');
                
                const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
                const bagSize = parseFloat(bagSizeInput.val()) || 0;
                
                // Calculate Qty = No of Bags × Bag Size (only if qty wasn't manually changed)
                if (!qtyInput.data('manual-change')) {
                    if (noOfBags > 0 && bagSize > 0) {
                        const calculatedQty = noOfBags * bagSize;
                        qtyInput.val(roundToTwo(calculatedQty));
                    } else if (noOfBags === 0 || bagSize === 0) {
                        qtyInput.val('');
                    }
                }
                
                const qty = parseFloat(qtyInput.val()) || 0;
                
                // Calculate avg_weight_per_bag = qty / no_of_bags
                // Only calculate if both qty and no_of_bags are provided
                if (noOfBags > 0 && qty > 0) {
                    const avgWeight = qty / noOfBags;
                    avgWeightInput.val(roundToTwo(avgWeight));
                } else if (qty === 0 || noOfBags === 0) {
                    avgWeightInput.val('');
                }
                
                // Calculate yield: (qty / total_outputs) * 100
                if (qty > 0 && totalOutputs > 0) {
                    const yield = (qty / totalOutputs) * 100;
                    yieldInput.val(roundToTwo(yield));
                } else {
                    yieldInput.val('');
                }
                
                currentCommodityRows.push(row);
            }
        });
        
        // Update grand total row
        const grandTotalRow = tbody.find('tr.table-success, tr:last');
        if (grandTotalRow.length > 0 && grandTotalRow.find('strong').text().includes('Total')) {
            grandTotalRow.find('input[name="commodity_total_qty[]"]').val(roundToTwo(grandTotalQty));
            grandTotalRow.find('input[name="commodity_total_no_of_bags[]"]').val(Math.round(grandTotalBags));
            
            if (grandTotalQty > 0 && totalOutputs > 0) {
                const overallYield = (grandTotalQty / totalOutputs) * 100;
                grandTotalRow.find('input[name="total_yield[]"]').val(roundToTwo(overallYield));
            } else {
                grandTotalRow.find('input[name="total_yield[]"]').val('');
            }
        }
    }

    // Initialize all calculations
    function initializeCalculations() {
        // Reinitialize Select2 for dynamically loaded selects - properly destroy and reinitialize
        setTimeout(function() {
            // Destroy existing select2 instances first
            $('#productionInputsTable select, #productionHeadProductsTable select, #productionByProductsTable select').each(function() {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
            });
            
            // Remove existing select2 containers
            $('#productionInputsTable .select2-container, #productionHeadProductsTable .select2-container, #productionByProductsTable .select2-container').remove();
            
            // Clean up all selects
            $('#productionInputsTable select, #productionHeadProductsTable select, #productionByProductsTable select')
                .removeClass('select2-hidden-accessible')
                .removeAttr('data-select2-id');
            
            // Reinitialize all select2 with proper settings
            $('#productionInputsTable select, #productionHeadProductsTable select, #productionByProductsTable select').each(function() {
                var $select = $(this);
                // Make sure it has select2 class
                if (!$select.hasClass('select2')) {
                    $select.addClass('select2');
                }
                // Initialize select2 with proper settings
                $select.select2({
                    dropdownParent: $select.closest('.modal-body, .card-body, body')
                });
            });
        }, 200);
        
        // Attach event handlers using delegated events (works for dynamically added rows)
        // Production Inputs - qty changes
        $(document).off('input change keyup', '#productionInputsTable input[name="input_qty[]"]');
        $(document).on('input change keyup', '#productionInputsTable input[name="input_qty[]"]', function() {
            calculateProductionInputs();
        });
        
        // Head Products - no_of_bags and bag_size changes (auto calculate qty)
        $(document).off('input change keyup', '#productionHeadProductsTable input[name="output_no_of_bags[]"], #productionHeadProductsTable input[name="output_bag_size[]"]');
        $(document).on('input change keyup', '#productionHeadProductsTable input[name="output_no_of_bags[]"], #productionHeadProductsTable input[name="output_bag_size[]"]', function() {
            // Clear manual-change flag so qty can be recalculated
            $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
            calculateHeadProducts();
            calculateByProducts(); // Recalculate by products too since total outputs changed
        });
        
        // Head Products - qty manual change (mark as manual, then recalculate)
        $(document).off('input change keyup', '#productionHeadProductsTable input[name="output_qty[]"]');
        $(document).on('input change keyup', '#productionHeadProductsTable input[name="output_qty[]"]', function() {
            $(this).data('manual-change', true);
            calculateHeadProducts();
            calculateByProducts(); // Recalculate by products too since total outputs changed
        });
        
        // By Products - no_of_bags and bag_size changes (auto calculate qty)
        $(document).off('input change keyup', '#productionByProductsTable input[name="output_no_of_bags[]"], #productionByProductsTable input[name="output_bag_size[]"]');
        $(document).on('input change keyup', '#productionByProductsTable input[name="output_no_of_bags[]"], #productionByProductsTable input[name="output_bag_size[]"]', function() {
            // Clear manual-change flag so qty can be recalculated
            $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
            calculateHeadProducts(); // Recalculate head products too since total outputs changed
            calculateByProducts();
        });
        
        // By Products - qty manual change (mark as manual, then recalculate)
        $(document).off('input change keyup', '#productionByProductsTable input[name="output_qty[]"]');
        $(document).on('input change keyup', '#productionByProductsTable input[name="output_qty[]"]', function() {
            $(this).data('manual-change', true);
            calculateHeadProducts(); // Recalculate head products too since total outputs changed
            calculateByProducts();
        });
        
        // Note: produced_qty_kg no longer affects production inputs yield calculation
        // Production inputs yield is now based on total input = 100%
        
        // Run initial calculations
        calculateProductionInputs();
        calculateHeadProducts();
        calculateByProducts();
    }

    // Initialize on page load
    $(document).ready(function() {
        initializeCalculations();
        
        // Reinitialize after dynamic content loads
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).find('#productionInputsTable, #productionHeadProductsTable, #productionByProductsTable').length > 0) {
                setTimeout(function() {
                    initializeCalculations();
                }, 100);
            }
        });
    });

    function deleteProductionInput(voucherId, inputId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: () => {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request.',
                    icon: 'question',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("production-voucher.input.destroy", [":id", ":inputId"]) }}'
                        .replace(':id', voucherId)
                        .replace(':inputId', inputId),
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Your record has been deleted.',
                            'success'
                        ).then(() => {
                            $.post('{{ route("get.production-voucher-inputs", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                                $('#productionInputsTable').html(data);
                            });
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error deleting content:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error ' + status,
                            html: `<p>${xhr.responseJSON?.error || 'An error occurred'}</p><small>${xhr.responseJSON?.details || ''}</small>`
                        });
                    }
                });
            }
        });
    }

    function deleteProductionOutput(voucherId, outputId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: () => {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request.',
                    icon: 'question',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("production-voucher.output.destroy", [":id", ":outputId"]) }}'
                        .replace(':id', voucherId)
                        .replace(':outputId', outputId),
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Your record has been deleted.',
                            'success'
                        ).then(() => {
                            $.post('{{ route("get.production-voucher-outputs", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                                $('#productionOutputsTable').html(data);
                            });
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error deleting content:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error ' + status,
                            html: `<p>${xhr.responseJSON?.error || 'An error occurred'}</p><small>${xhr.responseJSON?.details || ''}</small>`
                        });
                    }
                });
            }
        });
    }

    function deleteProductionSlot(voucherId, slotId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: () => {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request.',
                    icon: 'question',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("production-voucher.slot.destroy", [":id", ":slotId"]) }}'
                        .replace(':id', voucherId)
                        .replace(':slotId', slotId),
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Your record has been deleted.',
                            'success'
                        ).then(() => {
                            $.post('{{ route("get.production-voucher-slots", ":id") }}'.replace(':id', voucherId), {}, function(data) {
                                $('#productionSlotsTableBody').html(data);
                            });
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error deleting content:", error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error ' + status,
                            html: `<p>${xhr.responseJSON?.error || 'An error occurred'}</p><small>${xhr.responseJSON?.details || ''}</small>`
                        });
                    }
                });
            }
        });
    }
</script>
@endsection
