<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
</style>

<form action="{{ route('export-delivery-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-delivery-order') }}" />

    <!-- Delivery Order Details (Editable) -->
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Delivery Details</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Customer: <span class="text-danger">*</span></label>
                        <select name="buyer_id" id="buyer_id" class="form-control select2">
                            <option value="">Select Customer</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Export Order: <span class="text-danger">*</span></label>
                        <select name="export_order_id" id="export_order_id" class="form-control select2">
                            <option value="">Select Export Order</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Form-E Select: <span class="text-danger">*</span></label>
                        <select name="export_form_e_id" id="export_form_e_id" class="form-control select2">
                            <option value="">Select Form-E</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Reference Number: <span class="text-danger">*</span></label>
                        <input type="text" name="ref_no" id="ref_no" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <select name="job_order_no" id="job_order_no" class="form-control select2">
                            <option value="">Select Job Order</option>
                        </select>
                        <div id="job_order_no_hidden_container"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Financial Instrument No: <span class="text-danger">*</span></label>
                        <input type="text" name="financial_instrument_no" id="financial_instrument_no" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Details Section -->
    <div class="row form-mar">
        <div class="col-12 mt-3 d-flex justify-content-between align-items-center">
            <h6 class="header-heading-sepration mb-0" style="width:100%; margin: 0 10px 0 0;">Location Details</h6>
            <button type="button" class="btn btn-sm btn-success" id="addLocationRow">Add More Location</button>
        </div>
        <div class="col-12 mt-3">
            <table class="table table-bordered" id="locationTable">
                <thead>
                    <tr>
                        <th style="width: 30%;">Location</th>
                        <th style="width: 30%;">Factory</th>
                        <th style="width: 30%;">Section</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody id="locationRows">
                    <tr class="location-row">
                        <td>
                            <select name="locations[0][location_id]" class="form-control select2 location-select">
                                <option value="">Select Location</option>
                                @foreach (get_locations() as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger error-message" id="error_locations_0_location_id"></span>
                        </td>
                        <td>
                            <select name="locations[0][arrival_ids][]" class="form-control select2 arrival-select" multiple disabled>
                                <option value="">Select Factory</option>
                            </select>
                        </td>
                        <td>
                            <select name="locations[0][storage_ids][]" class="form-control select2 storage-select" multiple disabled>
                                <option value="">Select Section</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger remove-location-row" style="display: none;"><i class="ft-trash font-medium-1"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <span class="text-danger error-message" id="error_locations"></span>
        </div>
    </div>

    <!-- Hidden Template for Location Row -->
    <table class="d-none">
        <tbody id="locationRowTemplate">
            <tr class="location-row">
                <td>
                    <select name="locations[INDEX][location_id]" class="form-control location-select">
                        <option value="">Select Location</option>
                        @foreach (get_locations() as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error-message" id="error_locations_INDEX_location_id"></span>
                </td>
                <td>
                    <select name="locations[INDEX][arrival_ids][]" class="form-control arrival-select" multiple disabled>
                        <option value="">Select Factory</option>
                    </select>
                </td>
                <td>
                    <select name="locations[INDEX][storage_ids][]" class="form-control storage-select" multiple disabled>
                        <option value="">Select Section</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-location-row"><i class="ft-trash font-medium-1"></i></button>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="exportOrderSnapshot" style="pointer-events: none; opacity: 0.9;">

        <div class="row form-mar">
            <div class="col-8">
                <!-- Basic Information -->
                <div class="">
                    <div class="alert alert-info mt-3" id="qty_info_alert" style="display: none; padding: 10px; margin-bottom:15px; border-radius: 5px; border-left: 5px solid #17a2b8;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Form-E Capacity:</strong>
                                Total Allowed: <span id="lbl_total_eo_mt" class="font-weight-bold">0.000</span> MT &nbsp;|&nbsp;
                                Prev DO'd: <span id="lbl_consumed_mt" class="font-weight-bold">0.000</span> MT &nbsp;|&nbsp;
                                Current Request: <span id="lbl_current_request_mt" class="font-weight-bold text-primary">0.000</span> MT
                            </div>
                            <div class="badge badge-pill badge-light p-2" style="font-size: 1rem;">
                                Balance: <span id="lbl_remaining_mt" class="font-weight-bold">0.000</span> MT
                            </div>
                        </div>
                    </div>

                    <div id="qty_error_msg" class="alert alert-danger mt-2" style="display: none; font-weight: bold; border-left: 5px solid #dc3545;">
                        <i class="fas fa-exclamation-triangle mr-2"></i> ERROR: Quantity exceeds Export Form-E capacity!
                    </div>
                    
                    <h6 class="header-heading-sepration">Basic Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Quotation#:</label>
                                <select data-name="quotation_id" class="form-control select2">
                                    <option value="">Select Quotation</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <fieldset>
                                <label>Sauda#:</label>
                                <select data-name="export_soda_id" class="form-control select2">
                                    <option value="">Select Sauda</option>
                                </select>
                            </fieldset>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <fieldset>
                                <label>Contract No#:</label>
                                <div class="input-group">
                                    <input type="text" readonly data-name="voucher_no" class="form-control">
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contract Date:</label>
                                <input type="date" data-name="voucher_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference No#:</label>
                                <input type="text" data-name="contract_no" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Date:</label>
                                <input type="date" data-name="voucher_heading" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Shipment Delivery Date From:</label>
                                <input type="date" data-name="shipment_delivery_date_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label> Shipment Delivery Date To:</label>
                                <input type="date" data-name="shipment_delivery_date_to" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Marking/labeling:</label>
                                <input type="text" data-name="marking_labeling" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class="">
                    <div class="form-group">
                        <label>Commodity/Product:</label>
                        <select data-name="product_id" class="form-control select2">
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="form-group mt-2">
                        <label>Visual Name:</label>
                        <input type="text" data-name="visual_name" class="form-control">
                    </div>
                </div>

                <!-- Specifications Section -->
                <div class="" id="snap_specificationsSection" style="display: none;">
                    <h6 class="header-heading-sepration">Specifications</h6>
                    <div id="snap_productSpecs"></div>
                </div>

            </div>{{-- end col-8 --}}

            <div class="col-4">
                <h6 class="header-heading-sepration">Export</h6>
                <div class="table-responsive">
                    <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">INCOTERMS</td>
                            <td style="width: 70%;">
                                <select data-name="incoterm_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PACKING TYPE</td>
                            <td style="width: 70%;">
                                <select data-name="packing_type" class="form-control select2">
                                    <option value="In Conatiner">IN CONTAINER</option>
                                    <option value="In Bulk">IN BULK</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TERM</td>
                            <td style="width: 70%;">
                                <select data-name="mode_of_term_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TRANSPORT</td>
                            <td style="width: 70%;">
                                <select data-name="mode_of_transport_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">ORIGIN</td>
                            <td style="width: 70%;">
                                <select data-name="origin_country_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF DISCHARGE</td>
                            <td style="width: 70%;">
                                <select data-name="port_of_discharge_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF LOADING</td>
                            <td style="width: 70%;">
                                <select data-name="port_of_loading_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">HS CODE</td>
                            <td style="width: 70%;">
                                <select data-name="hs_code_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PARTIAL PAYMENT</td>
                            <td style="width: 70%;">
                                <select data-name="partial_payment" class="form-control select2">
                                    <option value="Yes">YES</option>
                                    <option value="No">NO</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">TRANSHIPMENT</td>
                            <td style="width: 70%;">
                                <select data-name="transhipment" class="form-control select2">
                                    <option value="shall be permitted">SHALL BE PERMITTED</option>
                                    <option value="shall not be permitted">SHALL NOT BE PERMITTED</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PART SHIPMENT</td>
                            <td style="width: 70%;">
                                <select data-name="part_shipment" class="form-control select2">
                                    <option value="shall be permitted">SHALL BE PERMITTED</option>
                                    <option value="shall not be permitted">SHALL NOT BE PERMITTED</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">INSURANCE COVERED BY</td>
                            <td style="width: 70%;">
                                <select data-name="insurance_covered_by" class="form-control select2">
                                    <option value="Buyer">BUYER</option>
                                    <option value="Supplier">SUPPLIER</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">ADVANCE PAYMENT(%)</td>
                            <td style="width: 70%;">
                                <input type="number" data-name="advance_payment" class="form-control">
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS(no of days)</td>
                            <td style="width: 70%;">
                                <input type="text" data-name="payment_days" class="form-control">
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                            <td style="width: 70%;">
                                <select data-name="currency_id" class="form-control select2"></select>
                            </td>
                        </tr>
                        <!-- <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">RATE</td>
                            <td style="width: 70%;">
                                <input type="text" data-name="currency_rate" class="form-control" readonly>
                            </td>
                        </tr> -->
                    </table>
                </div>
            </div>
        </div>
    </div> <!-- END of exportOrderSnapshot -->


    <!-- Packing Details (full-width, below snapshot) -->
    <div class="row form-mar mt-2" id="packingItemsWrapper">
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Packing Details
            </h6>

            <div id="packingItems">
                <!-- Template for clone, kept visually hidden until populated -->
                <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto" style="display:none;" id="dummyPackingRow">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Brand:</label>
                            <select name="packing_items[0][brand_id]" class="form-control select2" disabled>
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][brand_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Type/Product:</label>
                            <select name="packing_items[0][bag_product_id]" class="form-control select2" disabled>
                                <option value="">Select Bag Type</option>
                                @foreach($bagTypes as $bagType)
                                    <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][bag_product_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Condition:</label>
                            <select name="packing_items[0][bag_condition_id]" class="form-control select2" disabled>
                                <option value="">Select Condition</option>
                                @foreach($bagConditions as $condition)
                                    <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][bag_condition_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Color:</label>
                            <select name="packing_items[0][bag_color_id]" class="form-control select2" disabled>
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][bag_color_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Thread Color:</label>
                            <select name="packing_items[0][thread_color_id]" class="form-control select2" disabled>
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][thread_color_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Stitching:</label>
                            <select name="packing_items[0][stitching_id]" class="form-control select2" disabled>
                                <option value="">Select Stitching</option>
                                @foreach($stitchings as $stitching)
                                    <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="packing_items[0][stitching_id]" class="hidden-mirror" disabled>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Packing Size:</label>
                            <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size" readonly step="0.01">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>No. of Bags:</label>
                            <input type="number" name="packing_items[0][no_of_bags]" class="form-control no-of-bags" style="background-color: #fff9e6; border-color: #ffc107;">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Extra Bags:</label>
                            <input type="number" name="packing_items[0][extra_bags]" class="form-control extra-bags" readonly value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Extra Bags %:</label>
                            <input type="number" name="packing_items[0][extra_bags_percentage]" class="form-control extra-bags-percentage" readonly step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Empty Bags:</label>
                            <input type="number" name="packing_items[0][empty_bags]" class="form-control empty-bags" readonly value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total Bags:</label>
                            <input type="number" min="0" name="packing_items[0][total_bags]" class="form-control total-bags" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total KGs:</label>
                            <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Qty (MT):</label>
                            <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" step="0.001" min="0" style="background-color: #fff9e6; border-color: #ffc107;">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Stuffing (MT):</label>
                            <input type="number" name="packing_items[0][stuffing_in_container]" value="0" class="form-control stuffing" step="0.001" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>No. Containers:</label>
                            <input type="number" name="packing_items[0][no_of_containers]" class="form-control containers" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Min Wt Empty(g):</label>
                            <input type="number" name="packing_items[0][min_weight_empty_bags]" class="form-control min-weight" value="0" min="0" step="0.01" readonly>
                        </div>
                    </div>


                    <!-- Master Packing Section -->
                    <div class="col-md-12 mt-4">
                        <div class="card border-primary shadow-sm">
                            <div class="header-heading-sepration rounded-0 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold">Master Packing</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive special">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="col-2">Bag Type/Product</th>
                                                <th>Bag Size</th>
                                                <th>No of Primary Bags fit in master bag</th>
                                                <th>No. of Bags</th>
                                                <th>Empty Bags</th>
                                                <th>Extra Bags</th>
                                                <th>Extra Bags %</th>
                                                <th>Empty Bag Weight (g)</th>
                                                <th>Total Bags</th>
                                                <th class="col-1">Stitching</th>
                                                <th class="col-1">Bag Color</th>
                                                <th class="col-1">Brand</th>
                                                <th class="col-1">Thread Color</th>
                                            </tr>
                                        </thead>
                                        <tbody class="sub-packing-items-container" data-index="0">
                                            <!-- Master packing items will be added here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logistics & Shipment Details (Moved here after Packing Details and before Remarks) -->
    <div class="row form-mar mt-3">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Logistics & Shipment Details</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vessel Name: <span class="text-danger">*</span></label>
                        <input type="text" name="vessel_name" id="vessel_name" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vessel ETD: <span class="text-danger">*</span></label>
                        <input type="date" name="vessel_etd" id="vessel_etd" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vessel ETA: <span class="text-danger">*</span></label>
                        <input type="date" name="vessel_eta" id="vessel_eta" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loading Date: <span class="text-danger">*</span></label>
                        <input type="date" name="loading_date" id="loading_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estimated Payment Date: <span class="text-danger">*</span></label>
                        <input type="date" name="estimated_payment_date" id="estimated_payment_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Freight Amount (Per Container): <span class="text-danger">*</span></label>
                        <input type="text" name="freight_amount" id="freight_amount" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Transporter: <span class="text-danger">*</span></label>
                        <select name="transporter_id[]" id="transporter_id" class="form-control select2" multiple>
                            <!-- Options will be populated via AJAX based on selected Export Order -->
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Clearing Agent: <span class="text-danger">*</span></label>
                        <select name="c_agent" id="c_agent" class="form-control select2">
                            <option value="">Select Clearing Agent</option>
                            @foreach ($clearingAgents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Shipping Line: <span class="text-danger">*</span></label>
                        <input type="text" name="shipping_line" id="shipping_line" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Empty Container Pickup: <span class="text-danger">*</span></label>
                        <input type="text" name="empty_container_pickup" id="empty_container_pickup" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fumigation By:</label>
                        <select name="fumigation_by[]" id="fumigation_by" class="form-control select2" multiple disabled>
                            @foreach ($fumigationCompanies as $fCompany)
                                <option value="{{ $fCompany->id }}">{{ $fCompany->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="fumigation_by_hidden" id="fumigation_by_hidden">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Inspection By:</label>
                        <select name="inspection_by[]" id="inspection_by" class="form-control select2" multiple disabled>
                            @foreach ($inspectionCompanies as $iCompany)
                                <option value="{{ $iCompany->id }}">{{ $iCompany->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="inspection_by_hidden" id="inspection_by_hidden">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Phyto Certificate:</label>
                        <select name="phyto_certificate[]" id="phyto_certificate" class="form-control select2" multiple>
                            @foreach ($fumigationCompanies as $fCompany)
                                <option value="{{ $fCompany->id }}">{{ $fCompany->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Carton Supplier:</label>
                        <input type="text" name="carton_supplier" id="carton_supplier" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fumigation Tablets:</label>
                        <input type="text" name="fumigation_tablets" id="fumigation_tablets" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Fumigation Ref No:</label>
                        <input type="text" name="fumigation_ref_no" id="fumigation_ref_no" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Section (Moved here) -->
    <div class="row form-mar mt-3" id="commissionSection">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Commission</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Commission (%):</label>
                        <input type="text" data-name="commission_percentage" name="commission_percentage" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Amt/Ton:</label>
                        <input type="text" data-name="commission_amount_per_ton" name="commission_amount_per_ton" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Commission:</label>
                        <input type="text" data-name="commission" name="commission" class="form-control" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Editable Remarks -->
    <div class="row form-mar mt-3">
        <div class="col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <!-- Hidden Template for Sub Packing Item (used by JS) -->
    <table class="sub-packing-item-template d-none">
        <tbody>
            <tr class="sub-packing-item-row">
                <td>
                    <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_product_id]" class="form-control form-control-sm select2 sub-bag-product" disabled>
                        <option value="">Select Bag Type/Product</option>
                        @foreach($bagTypes as $bagType)
                            <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][bag_product_id]" class="hidden-mirror">
                </td>
                <td>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][bag_size_id]" class="hidden-mirror">
                    <input type="text" readonly class="form-control form-control-sm sub-bag-size-val">
                </td>
                <td>
                    <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_primary_bags]" class="form-control form-control-sm sub-no-of-primary-bags" readonly>
                </td>
                <td>
                    <!-- Master No. of Bags auto-calculated and readonly -->
                    <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_bags]" class="form-control form-control-sm sub-no-of-bags" style="background-color: #e9ecef;" readonly>
                </td>
                <td>
                    <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bags]" class="form-control form-control-sm sub-empty-bags" value="0" readonly>
                </td>
                <td><input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags]" class="form-control form-control-sm sub-extra-bags" value="0" readonly></td>
                <td><input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags_percentage]" class="form-control form-control-sm sub-extra-bags-percentage" value="0" readonly></td>
                <td><input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bag_weight]" class="form-control form-control-sm sub-empty-bag-weight" value="0" readonly></td>
                <td><input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][total_bags]" class="form-control form-control-sm sub-total-bags" readonly></td>
                <td>
                    <select name="packing_items[INDEX][sub_items][SUB_INDEX][stitching_id]" class="form-control form-control-sm select2 sub-stitching" disabled>
                        <option value="">Select Stitching</option>
                        @foreach($stitchings as $stitching)
                            <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][stitching_id]" class="hidden-mirror">
                </td>
                <td class="col-1">
                    <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_color_id]" class="form-control form-control-sm select2 sub-bag-color" disabled>
                        <option value="">Select Color</option>
                        @foreach($bagColors as $color)
                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][bag_color_id]" class="hidden-mirror">
                </td>
                <td class="col-1">
                    <select name="packing_items[INDEX][sub_items][SUB_INDEX][brand_id]" class="form-control form-control-sm select2 sub-brand" disabled>
                        <option value="">Select Brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][brand_id]" class="hidden-mirror">
                </td>
                <td class="col-1">
                    <select name="packing_items[INDEX][sub_items][SUB_INDEX][thread_color_id]" class="form-control form-control-sm select2 sub-thread-color" disabled>
                        <option value="">Select Color</option>
                        @foreach($bagColors as $color)
                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="packing_items[INDEX][sub_items][SUB_INDEX][thread_color_id]" class="hidden-mirror">
                </td>
            </tr>
        </tbody>
    </table>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Delivery Order</button>
        </div>
    </div>
</form>

<script>
    // Location Selection Functions (Global - accessible from HTML onchange)
    function selectLocationRow(el) {
        const row = $(el).closest('.location-row');
        const locationId = $(el).val();
        const arrivalSelect = row.find('.arrival-select');
        const storageSelect = row.find('.storage-select');
        
        if (!locationId) {
            arrivalSelect.prop("disabled", true).empty();
            storageSelect.prop("disabled", true).empty();
            return;
        }

        arrivalSelect.prop("disabled", false);
        $.ajax({
            url: "{{ route('export.get-arrival-locations') }}",
            method: "GET",
            data: { location_id: locationId },
            dataType: "json",
            success: function(res) {
                arrivalSelect.empty();
                // Auto-select ALL factories
                res.forEach(loc => {
                    const option = new Option(loc.text, loc.id, true, true);
                    arrivalSelect.append(option);
                });
                arrivalSelect.select2();
                
                // Auto-populate and select all sections
                if (res.length > 0) {
                    const arrivalIds = res.map(loc => loc.id);
                    fetchStorageRow(row, arrivalIds);
                }
            },
            error: function(error) {
                console.error("Error fetching arrival locations:", error);
            }
        });
    }

    function selectStorageRow(el) {
        const row = $(el).closest('.location-row');
        const arrivalIds = $(el).val();
        fetchStorageRow(row, arrivalIds);
    }

    function fetchStorageRow(row, arrivalIds) {
        const storageSelect = row.find('.storage-select');
        
        if (!arrivalIds || (Array.isArray(arrivalIds) && arrivalIds.length === 0)) {
            storageSelect.prop("disabled", true).empty();
            return;
        }

        $.ajax({
            url: "{{ route('export.get-sub-arrival-locations') }}",
            method: "GET",
            data: { arrival_id: arrivalIds },
            dataType: "json",
            success: function(res) {
                storageSelect.empty();
                // Auto-select ALL sections
                res.forEach(storage => {
                    const option = new Option(storage.text, storage.id, true, true);
                    storageSelect.append(option);
                });
                storageSelect.prop("disabled", false).select2();
            },
            error: function(error) {
                console.error("Error fetching sub-arrival locations:", error);
            }
        });
    }

    // Global delegated listeners for location selection
    $(document).off('change', '.location-select').on('change', '.location-select', function() {
        selectLocationRow(this);
    });

    $(document).off('change', '.arrival-select').on('change', '.arrival-select', function() {
        selectStorageRow(this);
    });

    // Row manipulation
    $(document).off('click', '#addLocationRow').on('click', '#addLocationRow', function() {
        let index = $('#locationRows tr.location-row').length;
        let template = $('#locationRowTemplate').html();
        template = template.replace(/\[INDEX\]/g, '[' + index + ']');
        
        let $newRow = $(template);
        $('#locationRows').append($newRow);
        
        // Initialize Select2 for new row
        $newRow.find('.select2, .location-select, .arrival-select, .storage-select').select2({ width: '100%' });
        
        // Show remove buttons if more than one row
        $('.remove-location-row').show();
    });

    $(document).off('click', '.remove-location-row').on('click', '.remove-location-row', function() {
        if ($('#locationRows tr.location-row').length > 1) {
            $(this).closest('tr').remove();
            reindexLocationRows();
        }
        if ($('#locationRows tr.location-row').length === 1) {
            $('.remove-location-row').hide();
        }
    });

    function reindexLocationRows() {
        $('#locationRows tr.location-row').each(function(index) {
            $(this).find('select').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/locations\[\d+\]/, 'locations[' + index + ']'));
                }
            });
        });
    }

    // DOM Ready Initialization
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Track Form-E remaining qty for packing row autofill
        var currentFormERemainingMt = 0;
        var currentFormETotalMt = 0;
        var currentFormEConsumedMt = 0;

        function applyFormEQtyToPackingRows(remainingMt) {
            var $rows = $('#packingItems').find('.packing-item:not(#dummyPackingRow):visible');
            if ($rows.length === 0 || remainingMt <= 0) return;

            if ($rows.length === 1) {
                $rows.first().find('.metric-tons').val(remainingMt.toFixed(3)).trigger('input');
            } else {
                var totalMt = 0;
                $rows.each(function() { totalMt += parseFloat($(this).find('.metric-tons').val()) || 0; });
                if (totalMt > 0) {
                    $rows.each(function() {
                        var proportion = (parseFloat($(this).find('.metric-tons').val()) || 0) / totalMt;
                        $(this).find('.metric-tons').val((remainingMt * proportion).toFixed(3)).trigger('input');
                    });
                } else {
                    var perItem = remainingMt / $rows.length;
                    $rows.each(function() {
                        $(this).find('.metric-tons').val(perItem.toFixed(3)).trigger('input');
                    });
                }
            }
            checkCapacity();
        }

        getNumber();

        function getNumber() {
            $.ajax({
                url: "{{ route('sales.get.delivery-order.getnumber') }}",
                method: "GET",
                data: {
                    contract_date: $("#dispatch_date").val()
                },
                dataType: "json",
                success: function(res) {
                    $('#reference_no').val(res.so_no);
                },
                error: function(error) {
                    console.error("Error fetching Delivery Order Number:", error);
                }
            });
        }

        $('#buyer_id').on('change', function() {
            var buyerId = $(this).val();
            $('#export_order_id').html('<option value="">Select Export Order</option>').trigger('change');
            if(buyerId) {
                $.get("{{ route('export.get-orders-by-buyer', '') }}/" + buyerId, function(res) {
                    if(res.success && res.data) {
                        var opts = '<option value="">Select Export Order</option>';
                        res.data.forEach(function(eo) {
                            opts += '<option value="'+eo.id+'">#'+eo.voucher_no+'</option>';
                        });
                        $('#export_order_id').html(opts).trigger('change');
                    }
                });
            }
        });

        $('#export_order_id').on('change', function() {
            var orderId = $(this).val();
            if (!orderId) {
                // Clear inputs if nothing selected
                $('[data-name]').val('');
                $('#snap_productSpecs, #snap_packingItems').html('');
                $('#export_form_e_id').html('<option value="">Select Form-E</option>').trigger('change');
                return;
            }

            // Fetch Form-Es for this order
            $.get("{{ route('export.get-form-es-by-order', '') }}/" + orderId, function(res) {
                if(res.success && res.data) {
                    var opts = '<option value="">Select Form-E</option>';
                    res.data.forEach(function(fe) {
                        opts += '<option value="'+fe.id+'">'+(fe.form_e_no || 'FE-'+fe.id)+' (Qty: '+fe.input_quantity+' MT)</option>';
                    });
                    $('#export_form_e_id').html(opts).trigger('change');
                    // Hide qty alert until a Form-E is actually selected
                    $('#qty_info_alert').hide();
                }
            });

            $.ajax({
                url: "{{ route('export.get-export-order-details', '') }}/" + orderId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        let data = response.data.export_order;
                        let totalEoMt  = parseFloat(response.data.total_eo_mt);
                        let consumedMt = parseFloat(response.data.consumed_mt  || 0);
                        let remainingMt= parseFloat(response.data.remaining_mt || 0);

                        // Fallback calculation if server doesn't return total (though it's being sent)
                        if (isNaN(totalEoMt) || totalEoMt === 0) {
                            if (data.packing_items && data.packing_items.length > 0) {
                                totalEoMt = data.packing_items.reduce((sum, item) => sum + (parseFloat(item.metric_tons) || 0), 0);
                            } else if (response.data.packing_items_autofill && response.data.packing_items_autofill.length > 0) {
                                totalEoMt = response.data.packing_items_autofill.reduce((sum, item) => sum + (parseFloat(item.metric_tons) || 0), 0);
                            }
                        }

                        $('#qty_info_alert').show();
                        $('#lbl_total_eo_mt').text(totalEoMt.toFixed(3));
                        $('#lbl_consumed_mt').text(consumedMt.toFixed(3));
                        $('#lbl_remaining_mt').text(remainingMt.toFixed(3));

                        populateSnapshot(data, response.data.packing_items_autofill);

                        // Autofill new fields
                        if (response.data.autofill) {
                            let joSelect = $('#job_order_no');
                            joSelect.empty().append('<option value="">Select Job Order</option>');
                            $('#job_order_no_hidden_container').empty();
                            if (response.data.autofill.job_orders && response.data.autofill.job_orders.length > 0) {
                                response.data.autofill.job_orders.forEach(function(jo) {
                                    joSelect.append('<option value="'+jo+'">'+jo+'</option>');
                                });
                                if (response.data.autofill.job_orders.length === 1) {
                                    joSelect.val(response.data.autofill.job_orders[0]).trigger('change');
                                    joSelect.attr('disabled', true);
                                    $('#job_order_no_hidden_container').html('<input type="hidden" name="job_order_no" value="'+response.data.autofill.job_orders[0]+'">');
                                } else {
                                    joSelect.removeAttr('disabled');
                                }
                            } else {
                                joSelect.removeAttr('disabled');
                            }
                            
                            if(response.data.autofill.vessel_name) $('#vessel_name').val(response.data.autofill.vessel_name);
                            if(response.data.autofill.vessel_eta) $('#vessel_eta').val(response.data.autofill.vessel_eta);
                            if(response.data.autofill.vessel_etd) $('#vessel_etd').val(response.data.autofill.vessel_etd);
                            if(response.data.autofill.shipping_line) $('#shipping_line').val(response.data.autofill.shipping_line);
                            if(response.data.autofill.estimated_payment_date) $('#estimated_payment_date').val(response.data.autofill.estimated_payment_date);
                            if(response.data.autofill.freight_amount) $('#freight_amount').val(response.data.autofill.freight_amount);

                            // Populate Logistics Transporters
                            let transporterSelect = $('#transporter_id');
                            transporterSelect.empty().attr('name', 'transporter_id[]').prop('multiple', true);
                            if(response.data.autofill.logistics_transporters && response.data.autofill.logistics_transporters.length > 0) {
                                response.data.autofill.logistics_transporters.forEach(function(transporter) {
                                    transporterSelect.append(new Option(transporter.name, transporter.id, true, true));
                                });
                            }
                            transporterSelect.trigger('change');
                        }
                            if(response.data.autofill.inspection_by && response.data.autofill.inspection_by.length > 0) {
                                $('#inspection_by').val(response.data.autofill.inspection_by).trigger('change');
                                $('#inspection_by_hidden').val(JSON.stringify(response.data.autofill.inspection_by));
                            } else {
                                $('#inspection_by').val([]).trigger('change');
                                $('#inspection_by_hidden').val('');
                            }

                            if(response.data.autofill.fumigation_by && response.data.autofill.fumigation_by.length > 0) {
                                $('#fumigation_by').val(response.data.autofill.fumigation_by).trigger('change');
                                $('#fumigation_by_hidden').val(JSON.stringify(response.data.autofill.fumigation_by));
                            } else {
                                $('#fumigation_by').val([]).trigger('change');
                                $('#fumigation_by_hidden').val('');
                            }
                            
                            if(response.data.autofill.phyto_certificate && response.data.autofill.phyto_certificate.length > 0) {
                                $('#phyto_certificate').val(response.data.autofill.phyto_certificate).trigger('change');
                            } else {
                                $('#phyto_certificate').val([]).trigger('change');
                            }
                            
                            if(response.data.autofill.carton_supplier) $('#carton_supplier').val(response.data.autofill.carton_supplier);
                            else $('#carton_supplier').val('');
                            
                            if(response.data.autofill.fumigation_tablets) $('#fumigation_tablets').val(response.data.autofill.fumigation_tablets);
                            else $('#fumigation_tablets').val('');
                            
                            if(response.data.autofill.fumigation_ref_no) $('#fumigation_ref_no').val(response.data.autofill.fumigation_ref_no);
                            else $('#fumigation_ref_no').val('');
                        // After packing rows are added, apply Form-E qty (overrides EO qty)
                        if (currentFormERemainingMt > 0) {
                            applyFormEQtyToPackingRows(currentFormERemainingMt);
                        }

                        checkCapacity();
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch order details", err);
                    alert("Failed to fetch export order details.");
                }
            });
        });

        $('#export_form_e_id').on('change', function() {
            var formEId = $(this).val();
            if (!formEId) {
                currentFormERemainingMt = 0;
                currentFormETotalMt = 0;
                currentFormEConsumedMt = 0;
                $('#qty_info_alert').hide();
                return;
            }

            $.ajax({
                url: "{{ route('export-delivery-order.form-e-usage', '') }}/" + formEId,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        currentFormETotalMt    = parseFloat(response.total);
                        currentFormEConsumedMt = parseFloat(response.consumed);
                        currentFormERemainingMt= parseFloat(response.remaining);

                        $('#qty_info_alert').show();
                        $('#lbl_total_eo_mt').text(currentFormETotalMt.toFixed(3));
                        $('#lbl_consumed_mt').text(currentFormEConsumedMt.toFixed(3));
                        $('#lbl_remaining_mt').text(currentFormERemainingMt.toFixed(3));

                        if (response.job_order_no) {
                            $('#job_order_no').val(response.job_order_no);
                        }

                        // Apply Form-E qty to any already-loaded packing rows
                        applyFormEQtyToPackingRows(currentFormERemainingMt);
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch Form-E usage details", err);
                }
            });
        });

        function populateSnapshot(data, packingItemsAuto) {
            // General structure fields mapped
            $('[data-name="quotation_id"]').html('<option value="'+(data.quotation_id || '')+'" selected>'+(data.quotation ? (data.quotation.reference || '#' + data.quotation_id) + ' - ' + (data.quotation.product ? data.quotation.product.name : '') : (data.quotation_id ? '#' + data.quotation_id : 'Select Quotation'))+'</option>').trigger('change.select2');
            $('[data-name="export_soda_id"]').html('<option value="'+(data.export_soda_id || '')+'" selected>'+(data.export_soda ? (data.export_soda.voucher_no || '#' + data.export_soda_id) + ' - ' + (data.export_soda.product ? data.export_soda.product.name : '') : (data.export_soda_id ? '#' + data.export_soda_id : 'Select Sauda'))+'</option>').trigger('change.select2');
            $('[data-name="voucher_no"]').val(data.voucher_no);
            $('[data-name="voucher_date"]').val(data.voucher_date ? data.voucher_date.split('T')[0] : '');
            $('[data-name="contract_no"]').val(data.contract_no);
            $('[data-name="voucher_heading"]').val(data.voucher_heading ? data.voucher_heading.split('T')[0] : '');
            
            $('[data-name="shipment_delivery_date_from"]').val(data.shipment_delivery_date_from ? data.shipment_delivery_date_from.split('T')[0] : '');
            $('[data-name="shipment_delivery_date_to"]').val(data.shipment_delivery_date_to ? data.shipment_delivery_date_to.split('T')[0] : '');
            $('[data-name="marking_labeling"]').val(data.marking_labeling);
            $('[data-name="visual_name"]').val(data.visual_name);
            
            // Reconstruct single options for dropdowns with dynamic labels
            $('[data-name="product_id"]').html('<option value="'+data.product_id+'" selected>'+(data.product ? data.product.name : '')+'</option>').trigger('change.select2');
            $('[data-name="incoterm_id"]').html('<option value="'+data.incoterm_id+'" selected>'+(data.incoterm ? data.incoterm.name : '')+'</option>').trigger('change.select2');
            $('[data-name="mode_of_term_id"]').html('<option value="'+data.mode_of_term_id+'" selected>'+(data.mode_of_term ? data.mode_of_term.name : '')+'</option>').trigger('change.select2');
            $('[data-name="mode_of_transport_id"]').html('<option value="'+data.mode_of_transport_id+'" selected>'+(data.mode_of_transport ? data.mode_of_transport.name : '')+'</option>').trigger('change.select2');
            $('[data-name="origin_country_id"]').html('<option value="'+data.origin_country_id+'" selected>'+(data.origin_country ? data.origin_country.name : '')+'</option>').trigger('change.select2');
            $('[data-name="port_of_discharge_id"]').html('<option value="'+data.port_of_discharge_id+'" selected>'+(data.port_of_discharge ? data.port_of_discharge.name : '')+'</option>').trigger('change.select2');
            $('[data-name="port_of_loading_id"]').html('<option value="'+data.port_of_loading_id+'" selected>'+(data.port_of_loading ? data.port_of_loading.name : '')+'</option>').trigger('change.select2');
            $('[data-name="hs_code_id"]').html('<option value="'+data.hs_code_id+'" selected>'+(data.hs_code ? data.hs_code.code : '')+'</option>').trigger('change.select2');
            $('[data-name="currency_id"]').html('<option value="'+data.currency_id+'" selected>'+(data.currency ? data.currency.currency_name : '')+'</option>').trigger('change.select2');

            // Specs - Align with Export Order Show style
            if(data.specifications && data.specifications.length > 0) {
                let specHtml = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="40%">Specification Name</th>
                                    <th width="30%">Value</th>
                                    <th width="30%">Type</th>
                                </tr>
                            </thead>
                            <tbody>`;
                data.specifications.forEach(spec => {
                    let specName = spec.product_slab_type ? spec.product_slab_type.name : (spec.spec_name || 'N/A');
                    let qcSymbol = spec.product_slab_type ? (spec.product_slab_type.qc_symbol || spec.uom) : (spec.uom || 'N/A');
                    let valueType = (spec.value_type || 'min').charAt(0).toUpperCase() + (spec.value_type || 'min').slice(1);
                    specHtml += `
                        <tr>
                            <td><strong>${specName}</strong></td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" value="${spec.spec_value}" class="form-control" readonly>
                                    <div class="input-group-prepend">
                                        <button class="btn btn-secondary" type="button">${qcSymbol}</button>
                                    </div>
                                </div>
                            </td>
                            <td>${valueType}</td>
                        </tr>`;
                });
                specHtml += '</tbody></table></div>';
                $('#snap_productSpecs').html(specHtml);
                $('#snap_specificationsSection').show();
            } else {
                $('#snap_specificationsSection').hide();
            }
            
            // Commission
            let totalMt = packingItemsAuto ? packingItemsAuto.reduce((sum, item) => sum + (parseFloat(item.metric_tons) || 0), 0) : 0;
            let commission = parseFloat(data.commission) || 0;
            let amtPerTon = totalMt > 0 ? (commission / totalMt) : (parseFloat(data.commission_amount_per_ton) || 0);

            $('[data-name="commission_percentage"]').val(data.commission_percentage);
            $('[data-name="commission_amount_per_ton"]').val(amtPerTon.toFixed(2));
            $('[data-name="commission"]').val(commission.toFixed(2));

            // Export Table fields static options
            $('[data-name="packing_type"]').val(data.packing_type).trigger('change.select2');
            $('[data-name="partial_payment"]').val(data.partial_payment).trigger('change.select2');
            $('[data-name="transhipment"]').val(data.transhipment).trigger('change.select2');
            $('[data-name="part_shipment"]').val(data.part_shipment).trigger('change.select2');
            $('[data-name="insurance_covered_by"]').val(data.insurance_covered_by).trigger('change.select2');
            
            $('[data-name="advance_payment"]').val(data.advance_payment);
            $('[data-name="payment_days"]').val(data.payment_days);
            // $('[data-name="currency_rate"]').val(data.currency_rate);

            // Packing items
            $('#packingItemsWrapper').show();
            if (packingItemsAuto && packingItemsAuto.length > 0) {
                addPackingRowsFromExportOrder(packingItemsAuto);
            } else {
                $('#packingItems').find('.packing-item:not(#dummyPackingRow)').remove();
                if ($('#packingItems').find('tbody.empty-placeholder').length === 0) {
                    $('#packingItems').append('<div class="text-center text-muted empty-placeholder p-4">No packing items found in Export Order</div>');
                }
            }
        }

        function addPackingRowsFromExportOrder(items) {
            let container = $('#packingItems');
            let templateRow = $('#dummyPackingRow').clone();
            
            // Remove existing rows except template
            container.find('.packing-item:not(#dummyPackingRow)').remove();

            items.forEach(function(item, index) {
                let row = templateRow.clone();
                row.removeAttr('id');
                
                // Destroy any existing Select2 instances in cloned item
                row.find('select.select2').each(function () {
                    var $select = $(this);
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    $select.siblings('.select2-container').remove();
                    $select.show().removeClass('select2-hidden-accessible');
                    $select.removeAttr('data-select2-id');
                });
                
                row.show();
                row.find('.no-of-bags, .metric-tons, .stuffing, .containers').removeAttr('disabled');
                row.find('.hidden-mirror').removeAttr('disabled');
                
                // Helper to find bag product ID by name
                function findOptionIdByName(name, selectElement) {
                    if (!name) return null;
                    let id = null;
                    selectElement.find('option').each(function() {
                        if ($(this).text().trim().toLowerCase() === name.trim().toLowerCase()) {
                            id = $(this).val();
                            return false;
                        }
                    });
                    return id;
                }

                // Fill values 
                // We use `.val()` on the select, and ALSO set the hidden-mirror so it submits
                function setDisabledSelectValue(selector, varName) {
                    row.find(selector).val(varName);
                    row.find(selector).siblings('.hidden-mirror').val(varName);
                }

                setDisabledSelectValue(`select[name="packing_items[0][brand_id]"]`, item.brand_id);
                
                let bagProductSelect = row.find(`select[name="packing_items[0][bag_product_id]"]`);
                let mappedId = findOptionIdByName(item.bag_type_name, bagProductSelect);
                if (mappedId) {
                    setDisabledSelectValue(`select[name="packing_items[0][bag_product_id]"]`, mappedId);
                } else {
                    setDisabledSelectValue(`select[name="packing_items[0][bag_product_id]"]`, item.bag_product_id); // Fallback
                }

                setDisabledSelectValue(`select[name="packing_items[0][bag_condition_id]"]`, item.bag_condition_id);
                setDisabledSelectValue(`select[name="packing_items[0][bag_color_id]"]`, item.bag_color_id);
                setDisabledSelectValue(`select[name="packing_items[0][thread_color_id]"]`, item.thread_color_id);
                setDisabledSelectValue(`select[name="packing_items[0][stitching_id]"]`, item.stitching_id);
                
                row.find(`input[name="packing_items[0][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[0][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[0][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[0][extra_bags_percentage]"]`).val(item.extra_bags_percentage);
                row.find(`input[name="packing_items[0][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[0][total_bags]"]`).val(item.total_bags);
                row.find(`input[name="packing_items[0][total_kgs]"]`).val(item.total_kgs);
                row.find(`input[name="packing_items[0][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[0][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[0][no_of_containers]"]`).val(item.no_of_containers);
                row.find('.min-weight').val(item.min_weight_empty_bags || 0);


                
                // Clear sub items container
                let subContainer = row.find('.sub-packing-items-container');
                subContainer.attr('data-index', index);
                subContainer.empty();

                 // Update names for the main row
                 row.find('input, select, textarea').each(function() {
                    let name = $(this).attr('name');
                    if(name) {
                        name = name.replace(/\[0\]/, `[${index}]`);
                        $(this).attr('name', name);
                    }
                });

                // Inside sub items
                if(item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        let $template = $('.sub-packing-item-template tbody');
                        let subRowHtml = $template.html();
                        
                        // Replace placeholders securely.
                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);

                        function setSubDisabledSelect(selector, val) {
                            subRow.find(selector).val(val);
                            subRow.find(selector).siblings('.hidden-mirror').val(val);
                        }
                        
                        let subBagProductSelect = subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`);
                        let subMappedId = findOptionIdByName(sub.bag_type_name, subBagProductSelect);
                        if (subMappedId) {
                            setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`, subMappedId);
                        } else {
                            setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`, sub.bag_product_id);
                        }

                        // For bag size ID, populate hidden input and show display val
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        subRow.find('.sub-bag-size-val').val(sub.bag_size_name || sub.bag_size_id || ''); // Display name instead of ID

                        setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][stitching_id]"]`, sub.stitching_id);
                        setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_color_id]"]`, sub.bag_color_id);
                        setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][brand_id]"]`, sub.brand_id);
                        setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][thread_color_id]"]`, sub.thread_color_id);
                        
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_primary_bags]"]`).val(sub.no_of_primary_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_bags]"]`).val(sub.no_of_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags]"]`).val(sub.empty_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags]"]`).val(sub.extra_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags_percentage]"]`).val(sub.extra_bags_percentage);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bag_weight]"]`).val(sub.empty_bag_weight);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);

                        // Prevent Select2 duplicates
                        subRow.find('select.select2').each(function () {
                            var $select = $(this);
                            if ($select.data('select2')) {
                                $select.select2('destroy');
                            }
                            $select.siblings('.select2-container').remove();
                            $select.show().removeClass('select2-hidden-accessible');
                            $select.removeAttr('data-select2-id');
                            $select.select2({width: '100%'});
                        });
                        
                        subContainer.append(subRow);
                    });
                } else {
                    subContainer.append('<tr><td colspan="10" class="text-center text-muted">No master packing attached</td></tr>');
                }

                container.append(row);
                
                // Initialize Select2 
                row.find('select.select2').each(function() {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $(this).closest('.table-responsive').length ? $(this).closest('.table-responsive') : $('body')
                    });
                });
            });
        }
        
        // Setup Auto-calculation logic for packing items (Qty MT and Bags)
        $(document).off('input.exportDOCreate', '.no-of-bags, .metric-tons, .bag-size, .extra-bags, .empty-bags, .stuffing, .containers');
        $(document).on('input.exportDOCreate', '.no-of-bags, .metric-tons, .bag-size, .extra-bags, .empty-bags, .stuffing, .containers', function () {
            var item = $(this).closest('.packing-item');
            var source = $(this);
            
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var bagSize = parseFloat(item.find('.bag-size').val()) || 0;
            var extraBags = parseInt(item.find('.extra-bags').val()) || 0;
            var emptyBags = parseInt(item.find('.empty-bags').val()) || 0;

            // 1. Sync Qty MT and No. of Bags
            if (source.hasClass('no-of-bags') || source.hasClass('bag-size')) {
                if (bagSize > 0) {
                    metricTons = (noOfBags * bagSize) / 1000;
                    item.find('.metric-tons').val(metricTons.toFixed(3));
                }
            } else if (source.hasClass('metric-tons')) {
                if (bagSize > 0) {
                    noOfBags = Math.round((metricTons * 1000) / bagSize);
                    item.find('.no-of-bags').val(noOfBags);
                }
            }

            // 2. Stuffing vs Containers Logic (Revised)
            var stuffing = parseFloat(item.find('.stuffing').val()) || 0;
            var containers = parseInt(item.find('.containers').val()) || 0;

            if (source.hasClass('metric-tons') || source.hasClass('no-of-bags') || source.hasClass('bag-size')) {
                // Qty changed: stuffing stays fixed, containers update
                if (stuffing > 0) {
                    containers = Math.ceil(metricTons / stuffing);
                    item.find('.containers').val(containers);
                }
            } else if (source.hasClass('stuffing')) {
                // When manual stuffing edit, containers should update
                if (stuffing > 0) {
                    containers = Math.ceil(metricTons / stuffing);
                    item.find('.containers').val(containers);
                }
            } else if (source.hasClass('containers')) {
                // When manual container edit, stuffing should update
                if (containers > 0) {
                    stuffing = metricTons / containers;
                    item.find('.stuffing').val(stuffing.toFixed(3));
                }
            }

            // Recalculate Total Bags (Crucial: User wants NO auto-increase of extra bags when no-of-bags changes)
            var totalBags = noOfBags + extraBags + emptyBags;
            var totalKgs = noOfBags * bagSize;
            
            item.find('.total-bags').val(totalBags);
            item.find('.total-kgs').val(totalKgs.toFixed(2));
            
            checkCapacity();

            // Now auto-calculate sub-items master packing no-of-bags
            item.find('.sub-packing-item-row').each(function () {
                var subRow = $(this);
                var primaryBagsInMaster = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;
                if (primaryBagsInMaster > 0 && totalBags > 0) {
                    var masterNoOfBags = Math.ceil(totalBags / primaryBagsInMaster);
                    subRow.find('.sub-no-of-bags').val(masterNoOfBags); // Update value
                    
                    // Recalculate Sub-Total Bags
                    var subExtra = parseInt(subRow.find('.sub-extra-bags').val()) || 0;
                    var subEmpty = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
                    subRow.find('.sub-total-bags').val(masterNoOfBags + subExtra + subEmpty);
                } else {
                    subRow.find('.sub-no-of-bags').val(0);
                    subRow.find('.sub-total-bags').val(0);
                }
            });
        });

        // Auto-calculation logic for sub items (manual edits to sub extra/empty)
        $(document).off('input.exportDOCreateSub', '.sub-empty-bags, .sub-extra-bags');
        $(document).on('input.exportDOCreateSub', '.sub-empty-bags, .sub-extra-bags', function () {
            var subRow = $(this).closest('tr');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
            var extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;
            var totalBags = noOfBags + emptyBags + extraBags;
            subRow.find('.sub-total-bags').val(totalBags);
        });

    // Real-time Capacity Checker
    function checkCapacity() {
        let totalAllowed = parseFloat($('#lbl_total_eo_mt').text()) || 0;
        let consumed = parseFloat($('#lbl_consumed_mt').text()) || 0;
        let currentRequest = 0;

        $('.packing-item:visible').each(function() {
            if ($(this).attr('id') !== 'dummyPackingRow') {
                currentRequest += parseFloat($(this).find('.metric-tons').val()) || 0;
            }
        });

        let remaining = totalAllowed - consumed;
        let balance = remaining - currentRequest;

        $('#lbl_current_request_mt').text(currentRequest.toFixed(3));
        $('#lbl_remaining_mt').text(balance.toFixed(3));

        if (balance < -0.001) {
            $('#lbl_remaining_mt').removeClass('text-success').addClass('text-danger');
            let errMsg = `Total Metric Tons (${currentRequest.toFixed(3)}) exceeds the remaining capacity of Export Order (${remaining.toFixed(3)} MT).`;
            $('#qty_error_msg').html(`<i class="fa fa-exclamation-triangle mr-2"></i> ERROR: ${errMsg}`).slideDown();
            $('.submitbutton').attr('disabled', true);
        } else {
            $('#lbl_remaining_mt').removeClass('text-danger').addClass('text-success');
            $('#qty_error_msg').slideUp();
            $('.submitbutton').attr('disabled', false);
        }
    }
    });
</script>