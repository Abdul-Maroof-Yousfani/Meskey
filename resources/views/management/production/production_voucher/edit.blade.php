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
                        <select name="location_id" id="location_id" class="form-control select2" required onchange="loadData();loadCommoditiesByLocation();loadSubLocationByLocation();">
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
                        <label>Sub Location:</label>
                        <select name="sub_location_id" id="sub_location_id" class="form-control select2" required onchange="loadPlantsBySubLocation();">
                            <option value="">Select Sub Location</option>
                            @foreach($sublocations as $sublocation)
                                @if($sublocation->company_location_id == $productionVoucher->location_id)
                                    <option value="{{ $sublocation->id }}" {{ $productionVoucher->sub_location_id == $sublocation->id ? 'selected' : '' }}>
                                        {{ $sublocation->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required onchange="loadlabourCharges();loadMachinesByPlant();">
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                @if($plant->arrival_location_id == $productionVoucher->sub_location_id)
                                    <option value="{{ $plant->id }}" {{ $productionVoucher->plant_id == $plant->id ? 'selected' : '' }} data-production_labour_charges_per_kg="{{ $plant->production_labour_charges_per_kg }}">
                                        {{ $plant->name }}
                                    </option>
                                @endif
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

                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}" {{ in_array($jobOrder->id, $jobOrderIds) ? 'selected' : '' }}>
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
                            'headProductOutputs' => $headProductOutputs,
                            'productionVoucher' => $productionVoucher,
                            'jobOrderPackings' =>$jobOrderPackings
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
                            $byProducts = getByProductsById($productionVoucher->by_product_id)->where('id', '!=', $productionVoucher->product_id);
                            $byProductOutputs = $productionVoucher->by_product_id ? $productionVoucher->outputs : collect();
                            $arrivalSubLocations = $sublocations;
                        @endphp
                        @include('management.production.production_voucher.partials.by_product_table', [
                            'byProducts' => $byProducts,
                            'arrivalSubLocations' => $arrivalSubLocations,
                            'brands' => $brands,
                            'jobOrders' => $jobOrders,
                            'byProductOutputs' => $byProductOutputs,
                          //  'jobOrderPackings'=>$jobOrderPackingsByProduct
                            'jobOrderPackings'=>collect()
                        ])
                    </div>
                </div>
            </div>



             <div class="row my-2">
                                    <div class="col-md-6">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Total Input</h6>
                                            </div>
                                        </div>
                                        <div>
                                            <input type="text" name="net_total_input" value="{{ $productionVoucher->net_total_input }}" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Total Output</h6>
                                            </div>
                                        </div>
                                        <div id="productionTotalOutputTable">
                                            <input type="text" name="net_total_output" value="{{ $productionVoucher->net_total_output }}" class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Labour Charges / Kg</th>
                                                    <th>Total Labour Charges</th>
                                                    <th>Deduction</th>
                                                    <th>Net Amount</th>
                                                    <th>Deduction Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input type="number" name="labour_charges_per_kg"
                                                            id="production_labour_charges_per_kg" class="form-control"
                                                            step="0.01" min="0.01" value="{{ $productionVoucher->labour_charges_per_kg }}" readonly></td>
                                                    <td><input type="number" name="total_labour_charges"
                                                            class="form-control" step="0.01" min="0.01" value="{{ $productionVoucher->total_labour_charges }}" readonly></td>
                                                    <td><input type="number" name="labour_deduction"
                                                            onkeyup="calculateNetTotalInput()" value="{{ $productionVoucher->labour_deduction }}" class="form-control"
                                                            step="0.01" min="0.01"></td>
                                                    <td><input type="number" name="labour_net_amount" value="{{ $productionVoucher->labour_net_amount }}" class="form-control"
                                                            step="0.01" min="0.01" readonly></td>
                                                    <td><input type="text" name="labour_deduction_remarks"
                                                            class="form-control" value="{{ $productionVoucher->labour_deduction_remarks }}">
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="row mt-3" id="productionMachinesSection" style="display: {{ $productionVoucher->plant_id ? 'block' : 'none' }};">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center" style="background-color: #93c3f2;">
                                            <div class="col-md-12 d-flex justify-content-between align-items-center py-1">
                                                <h6 class="m-0">Production Machines</h6>
                                            </div>
                                        </div>
                                        <div id="productionMachinesContainer">
                                            <!-- Machines will be loaded here -->
                                            @if($productionVoucher->plant_id)
                                                {{-- 
                                                @php
                                                    $allMachines = \App\Models\Master\ProductionMachine::where('plant_id', $productionVoucher->plant_id)->where('status', 'active')->get();
                                                    $selectedMachineIds = $productionVoucher->productionMachines->pluck('id')->toArray();
                                                @endphp
                                                <table class="table table-bordered table-sm">
                                                    <thead style="background-color: #f8f9fa;">
                                                        <tr>
                                                            <th width="10%">S.No</th>
                                                            <th>Machine Name</th>
                                                            <th width="20%" class="text-center">Select</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($allMachines as $index => $machine)
                                                            <tr>
                                                                <td>{{ $index + 1 }}</td>
                                                                <td>{{ $machine->name }}</td>
                                                                <td class="text-center">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox" class="custom-control-input" name="production_machine_id[]" value="{{ $machine->id }}" id="machine_{{ $machine->id }}" {{ in_array($machine->id, $selectedMachineIds) ? 'checked' : '' }}>
                                                                        <label class="custom-control-label" for="machine_{{ $machine->id }}"></label>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                --}}

                                                @php
                                                    $machinePlanSetting = \App\Models\Production\MachinePlanSetting::with(['items.machine'])
                                                        ->where('plant_id', $productionVoucher->plant_id)
                                                        ->whereDate('date', $productionVoucher->prod_date)
                                                        ->first();
                                                        
                                                    $allMachines = [];
                                                    if ($machinePlanSetting && $machinePlanSetting->items) {
                                                        foreach ($machinePlanSetting->items as $item) {
                                                            if ($item->machine && $item->machine->status === 'active') {
                                                                $allMachines[] = $item->machine;
                                                            }
                                                        }
                                                    }
                                                    
                                                    $selectedMachineIds = $productionVoucher->productionMachines->pluck('id')->toArray();
                                                    $machineTimes = $productionVoucher->productionVoucherMachineTimes->groupBy('production_machine_id');
                                                @endphp

                                                @if(count($allMachines) > 0)
                                                <div class="row">
                                                    @foreach($allMachines as $index => $machine)
                                                        @php
                                                            $isChecked = count($selectedMachineIds) > 0 ? in_array($machine->id, $selectedMachineIds) : $machine->is_enabled;
                                                            $times = isset($machineTimes[$machine->id]) && $machineTimes[$machine->id]->count() > 0 ? $machineTimes[$machine->id] : collect([new \App\Models\Production\ProductionVoucherMachineTime()]);
                                                            $totalMins = 0;
                                                        @endphp
                                                        <div class="col-md-12 mb-3">
                                                            <div class="machine-card" id="machine_card_{{ $machine->id }}" style="border: 1px solid {{ $isChecked ? '#93c3f2' : '#e0e0e0' }}; border-radius: 4px; overflow: hidden; transition: all 0.3s;">
                                                                <!-- Machine Card Header -->
                                                                <div class="machine-card-header d-flex align-items-center justify-content-between px-3 py-2" id="machine_card_header_{{ $machine->id }}" style="background: {{ $isChecked ? '#e8f3fc' : '#f5f5f5' }}; border-bottom: 1px solid {{ $isChecked ? '#93c3f2' : '#e0e0e0' }};">
                                                                    <div class="d-flex align-items-center">
                                                                        <div>
                                                                            <div style="font-weight:bold; font-size:14px; color: #333;">{{ $machine->name }}</div>
                                                                            <div style="font-size:12px; color: #666;">Machine #{{ $index + 1 }}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex align-items-center">
                                                                        <label class="machine-toggle-label mb-0" for="machine_{{ $machine->id }}" style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                                                                            <span id="machine_status_{{ $machine->id }}" style="font-size:12px; font-weight:bold; color: {{ $isChecked ? '#007bff' : '#6c757d' }};">{{ $isChecked ? 'Active' : 'Inactive' }}</span>
                                                                            <div class="custom-control custom-switch mb-0">
                                                                                <input type="checkbox" class="custom-control-input machine-toggle" name="production_machine_id[]" value="{{ $machine->id }}" id="machine_{{ $machine->id }}" {{ $isChecked ? 'checked' : '' }} onchange="updateMachineCardStyle(this, {{ $machine->id }})">
                                                                                <label class="custom-control-label" for="machine_{{ $machine->id }}"></label>
                                                                            </div>
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <!-- Time Slots -->
                                                                <div class="machine-card-body px-3 pt-2 pb-2" style="background:#fff;">
                                                                    <div style="font-size:14px; font-weight:bold; color:#333; margin-bottom:12px;">Time Slots</div>
                                                                    <div id="machine_time_table_{{ $machine->id }}">
                                                                        @foreach($times as $time)
                                                                            @php
                                                                                $totalMins += $time->duration_minutes;
                                                                                $hours = floor($time->duration_minutes / 60);
                                                                                $mins = $time->duration_minutes % 60;
                                                                                $durText = $time->duration_minutes > 0 ? "{$hours}h {$mins}m" : '';
                                                                            @endphp
                                                                            <div class="row time-row align-items-end mb-3">
                                                                                <div class="col-md-3">
                                                                                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">Start Time</label>
                                                                                    <input type="time" name="machine_start_time[{{ $machine->id }}][]" class="form-control start-time" value="{{ $time->start_time ? \Carbon\Carbon::parse($time->start_time)->format('H:i') : '' }}" onchange="calculateMachineTime({{ $machine->id }})" style="font-size:14px; height:42px;">
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">End Time</label>
                                                                                    <input type="time" name="machine_end_time[{{ $machine->id }}][]" class="form-control end-time" value="{{ $time->end_time ? \Carbon\Carbon::parse($time->end_time)->format('H:i') : '' }}" onchange="calculateMachineTime({{ $machine->id }})" style="font-size:14px; height:42px;">
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">Duration</label>
                                                                                    <input type="text" class="form-control duration-display" value="{{ $durText }}" readonly style="background:#f8f9fa; font-weight:bold; font-size:14px; text-align:center; height:42px;">
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <button type="button" class="btn btn-danger remove-time-row" onclick="removeMachineTimeRow(this, {{ $machine->id }})" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; margin: 0;"><i class="fa fa-trash"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>

                                                                    <!-- Add More Button -->
                                                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top: 1px solid #dee2e6;">
                                                                        @php $gHours = floor($totalMins / 60); $gMins = $totalMins % 60; @endphp
                                                                        <div class="d-flex align-items-center" style="gap:10px;">
                                                                            <span style="font-size:15px; font-weight:bold; color:#333;">Grand Total Time:</span>
                                                                            <input type="text" class="grand-total-display form-control" value="{{ $gHours }}h {{ $gMins }}m" readonly style="width:120px; font-weight:bold; text-align:center; font-size:15px; height:42px;">
                                                                        </div>
                                                                        <button type="button" class="btn btn-primary" onclick="addMachineTimeRow({{ $machine->id }})" style="height:42px; padding:0 20px;">
                                                                            <i class="fa fa-plus mr-1"></i> Add Time Slot
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @else
                                                    <div class="text-center py-4" style="color:#999; font-size:13px;">
                                                        <i class="fa fa-info-circle mr-1"></i> No machines found in Machine Plan Setting for this date & plant.
                                                    </div>
                                                @endif
                                            @endif
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

    function loadlabourCharges() {
        const plantId = $('#plant_id').val();
        const plant = $('#plant_id option:selected');
        const productionLabourChargesPerKg = plant.data('production_labour_charges_per_kg');
        $('#production_labour_charges_per_kg').val(productionLabourChargesPerKg);
    }

    function loadSubLocationByLocation() {
        const locationId = $('#location_id').val();
        const subLocationSelect = $('#sub_location_id');
        const plantSelect = $('#plant_id');

        // Clear sublocation and plant dropdowns
        subLocationSelect.empty().append('<option value="">Select Sub Location</option>');
        plantSelect.empty().append('<option value="">Select Plant</option>');

        if (!locationId) {
            subLocationSelect.trigger('change');
            plantSelect.trigger('change');
            return;
        }

        subLocationSelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-sublocations-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                subLocationSelect.empty().append('<option value="">Select Sub Location</option>');
                if (response.sublocations && response.sublocations.length > 0) {
                    $.each(response.sublocations, function (index, sublocation) {
                        subLocationSelect.append(
                            $('<option></option>')
                                .attr('value', sublocation.id)
                                .text(sublocation.name)
                        );
                    });
                } else {
                    subLocationSelect.append('<option value="">No Sub Location Found</option>');
                }
                subLocationSelect.trigger('change');
            },
            error: function (xhr) {
                console.error('Error loading sublocations:', xhr);
                subLocationSelect.append('<option value="">Error loading Sub Location</option>');
            },
            complete: function () {
                subLocationSelect.prop('disabled', false);
            }
        });
    }

    function loadPlantsBySubLocation() {
        const subLocationId = $('#sub_location_id').val();
        const plantSelect = $('#plant_id');

        if (!subLocationId) {
            plantSelect.empty().append('<option value="">Select Plant</option>');
            return;
        }

        plantSelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-plants-by-location") }}',
            method: 'POST',
            data: {
                subLocationId: subLocationId,
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
                                .attr('data-production_labour_charges_per_kg', plant.production_labour_charges_per_kg)
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

    function loadMachinesByPlant() {
        const plantId = $('#plant_id').val();
        const date = $('input[name="prod_date"]').val();
        const container = $('#productionMachinesContainer');
        const section = $('#productionMachinesSection');

        if (!plantId || !date) {
            container.empty();
            section.hide();
            return;
        }

        $.ajax({
            url: '{{ route("production-voucher.get-machines-by-plant") }}',
            method: 'POST',
            data: {
                plant_id: plantId,
                date: date,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                container.empty();
                if (response.machines && response.machines.length > 0) {
                    section.show();
                    let html = `<div class="row">`;
                    $.each(response.machines, function (index, machine) {
                        const isEnabled = machine.is_enabled;
                        const headerBg = isEnabled ? 'linear-gradient(135deg, #667eea, #764ba2)' : '#f5f5f5';
                        const iconBg = isEnabled ? 'rgba(255,255,255,0.25)' : '#e0e0e0';
                        const iconColor = isEnabled ? '#fff' : '#999';
                        const nameColor = isEnabled ? '#fff' : '#444';
                        const subColor = isEnabled ? 'rgba(255,255,255,0.75)' : '#999';
                        const statusLabel = isEnabled ? 'Active' : 'Inactive';
                        const statusColor = isEnabled ? 'rgba(255,255,255,0.85)' : '#999';
                        const cardBorder = isEnabled ? '#667eea' : '#e0e0e0';
                        html += `
                            <div class="col-md-6 mb-3">
                                <div class="machine-card" id="machine_card_${machine.id}" style="border: 1px solid ${cardBorder}; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.07); transition: all 0.3s;">
                                    <div class="machine-card-header d-flex align-items-center justify-content-between px-3 py-2" id="machine_card_header_${machine.id}" style="background: ${headerBg};">
                                        <div class="d-flex align-items-center">
                                            <div class="machine-icon mr-2" style="width:32px; height:32px; border-radius:50%; background: ${iconBg}; display:flex; align-items:center; justify-content:center;">
                                                <i class="fa fa-cog" style="color:${iconColor}; font-size:14px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:600; font-size:13px; color:${nameColor};">${machine.name}</div>
                                                <div style="font-size:11px; color:${subColor};">Machine #${index + 1}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <label class="machine-toggle-label mb-0" for="machine_${machine.id}" style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                                                <span id="machine_status_${machine.id}" style="font-size:11px; font-weight:500; color:${statusColor};">${statusLabel}</span>
                                                <div class="custom-control custom-switch mb-0">
                                                    <input type="checkbox" class="custom-control-input machine-toggle" name="production_machine_id[]" value="${machine.id}" id="machine_${machine.id}" ${isEnabled ? 'checked' : ''} onchange="updateMachineCardStyle(this, ${machine.id})">
                                                    <label class="custom-control-label" for="machine_${machine.id}"></label>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="machine-card-body px-3 pt-2 pb-2" style="background:#fafbff;">
                                        <div style="font-size:11px; font-weight:600; color:#667eea; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;"><i class="fa fa-clock-o mr-1"></i>Time Slots</div>
                                        <div id="machine_time_table_${machine.id}">
                                            <div class="time-row d-flex align-items-center mb-2" style="gap:6px;">
                                                <div style="flex:1;">
                                                    <label style="font-size:10px; color:#888; margin-bottom:2px;">Start</label>
                                                    <input type="time" name="machine_start_time[${machine.id}][]" class="form-control form-control-sm start-time" onchange="calculateMachineTime(${machine.id})" style="font-size:13px;">
                                                </div>
                                                <div style="flex:1;">
                                                    <label style="font-size:10px; color:#888; margin-bottom:2px;">End</label>
                                                    <input type="time" name="machine_end_time[${machine.id}][]" class="form-control form-control-sm end-time" onchange="calculateMachineTime(${machine.id})" style="font-size:13px;">
                                                </div>
                                                <div style="flex:1;">
                                                    <label style="font-size:10px; color:#888; margin-bottom:2px;">Duration</label>
                                                    <input type="text" class="form-control form-control-sm duration-display" readonly style="background:#f0f4ff; color:#667eea; font-weight:600; font-size:12px; text-align:center;">
                                                </div>
                                                <div style="padding-top:16px;">
                                                    <button type="button" class="btn btn-sm remove-time-row" onclick="removeMachineTimeRow(this, ${machine.id})" style="background:#fff0f0; color:#e74c3c; border:1px solid #ffd0d0; border-radius:6px; padding:3px 8px;"><i class="fa fa-times"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top: 1px dashed #e0e7ff;">
                                            <button type="button" class="btn btn-sm" onclick="addMachineTimeRow(${machine.id})" style="background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:20px; font-size:11px; padding:4px 12px; font-weight:500;">
                                                <i class="fa fa-plus mr-1"></i>Add Slot
                                            </button>
                                            <div class="d-flex align-items-center" style="gap:6px;">
                                                <span style="font-size:11px; color:#888;">Total:</span>
                                                <input type="text" class="grand-total-display" readonly style="width:70px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:20px; font-size:12px; font-weight:700; text-align:center; padding:3px 8px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    container.append(html);
                } else {
                    section.hide();
                }
            },
            error: function (xhr) {
                console.error('Error loading machines:', xhr);
                section.hide();
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
            // if (triggerChange) {
                jobOrderSelect.trigger('change');
            // }
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
                // if (triggerChange) {
                    jobOrderSelect.trigger('change');
                // }
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
        const headProductId = $('[name="product_id"]').val();
        console.log(jobOrderIds);
        if (byProductId) {
            fetchDynamicHTML('{{ route('production-voucher.get-by-product-table') }}', 'productionByProductsTable', {
                by_product_id: byProductId,
                location_id: locationId,
                job_order_ids: jobOrderIds,
                production_voucher_id: {{ $productionVoucher->id ?? 'null' }},
                head_product_id: headProductId
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
                job_order_ids: jobOrderIds,
                production_voucher_id: {{ $productionVoucher->id ?? 'null' }}

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

    function addMachineTimeRow(machineId) {
        const container = $(`#machine_time_table_${machineId}`);
        const tr = `
            <div class="row time-row align-items-end mb-3">
                <div class="col-md-3">
                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">Start Time</label>
                    <input type="time" name="machine_start_time[${machineId}][]" class="form-control start-time" onchange="calculateMachineTime(${machineId})" style="font-size:14px; height:42px;">
                </div>
                <div class="col-md-3">
                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">End Time</label>
                    <input type="time" name="machine_end_time[${machineId}][]" class="form-control end-time" onchange="calculateMachineTime(${machineId})" style="font-size:14px; height:42px;">
                </div>
                <div class="col-md-3">
                    <label style="font-size:14px; color:#555; font-weight:500; margin-bottom:4px;">Duration</label>
                    <input type="text" class="form-control duration-display" readonly style="background:#f8f9fa; font-weight:bold; font-size:14px; text-align:center; height:42px;">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger remove-time-row" onclick="removeMachineTimeRow(this, ${machineId})" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; margin: 0;"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        `;
        container.append(tr);
    }

    function removeMachineTimeRow(btn, machineId) {
        $(btn).closest('.time-row').remove();
        calculateMachineTime(machineId);
    }

    function calculateMachineTime(machineId) {
        let totalMinutes = 0;
        $(`#machine_time_table_${machineId} .time-row`).each(function() {
            const startTime = $(this).find('.start-time').val();
            const endTime = $(this).find('.end-time').val();
            let durationInput = $(this).find('.duration-display');

            if (startTime && endTime) {
                const start = new Date(`1970-01-01T${startTime}:00`);
                let end = new Date(`1970-01-01T${endTime}:00`);
                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }
                const diffMs = end - start;
                const diffMins = Math.floor(diffMs / 60000);
                totalMinutes += diffMins;
                
                const hours = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                durationInput.val(`${hours}h ${mins}m`);
            } else {
                durationInput.val('');
            }
        });

        const grandTotalHours = Math.floor(totalMinutes / 60);
        const grandTotalMins = totalMinutes % 60;
        $(`#machine_time_table_${machineId}`).closest('.machine-card-body').find('.grand-total-display').val(`${grandTotalHours}h ${grandTotalMins}m`);
    }

    function updateMachineCardStyle(checkbox, machineId) {
        const isChecked = $(checkbox).is(':checked');
        const card = $(`#machine_card_${machineId}`);
        const header = $(`#machine_card_header_${machineId}`);
        const statusLabel = $(`#machine_status_${machineId}`);
        if (isChecked) {
            card.css('border-color', '#93c3f2');
            header.css('background', '#e8f3fc');
            header.css('border-bottom', '1px solid #93c3f2');
            statusLabel.text('Active').css('color', '#007bff');
        } else {
            card.css('border-color', '#e0e0e0');
            header.css('background', '#f5f5f5');
            header.css('border-bottom', '1px solid #e0e0e0');
            statusLabel.text('Inactive').css('color', '#6c757d');
        }
    }

    $(document).ready(function() {
        $('input[name="prod_date"]').on('change', function() {
            loadMachinesByPlant();
        });
    });
</script>
@endsection
