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
    .snapshot-area {
        opacity: 0.9;
    }
</style>

<form action="{{ route('export-delivery-order.update', $deliveryOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-delivery-order') }}" />

    <!-- Delivery Order Details (Editable) -->
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Delivery Details</h6>
            <div class="row">
                <div class="col-md-3 mt-2">
                    <div class="form-group">
                        <label>Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" value="{{ $deliveryOrder->reference_no }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Customer:</label>
                        <select name="buyer_id" id="buyer_id_edit" class="form-control" disabled>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" {{ $deliveryOrder->customer_id == $buyer->id ? 'selected' : '' }}>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Export Order:</label>
                        <select name="export_order_id" id="export_order_id_edit" class="form-control" disabled>
                            @foreach ($export_orders as $eo)
                                <option value="{{ $eo->id }}" {{ $deliveryOrder->export_order_id == $eo->id ? 'selected' : '' }}>#{{ $eo->voucher_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Form-E Select: <span class="text-danger">*</span></label>
                        <select name="export_form_e_id" id="export_form_e_id_edit" class="form-control select2" required>
                            <option value="">Select Form-E</option>
                            @php
                                $formEs = \App\Models\Export\ExportFormE::where('export_order_id', $deliveryOrder->export_order_id)->get();
                            @endphp
                            @foreach($formEs as $fe)
                                <option value="{{ $fe->id }}" {{ $deliveryOrder->export_form_e_id == $fe->id ? 'selected' : '' }}>
                                    {{ $fe->form_e_no ?? 'FE-'.$fe->id }} (Qty: {{ $fe->input_quantity }} MT)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-2">
                    <div class="form-group">
                        <label>Reference Number:</label>
                        <input type="text" name="ref_no" id="ref_no" value="{{ $deliveryOrder->ref_no }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Details Section -->
    <div class="row form-mar">
        <div class="col-12 mt-3">
            <h6 class="header-heading-sepration">Location Details</h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="location_id" id="locations" onchange="selectLocation(this)" class="form-control select2">
                    <option value="">Select Locations</option>
                    @foreach (get_locations() as $location)
                        <option value="{{ $location->id }}" @selected($location->id == $deliveryOrder->location_id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Factory:</label>
                <select name="arrival_id[]" id="arrivals" class="form-control select2" multiple @if (!$deliveryOrder->arrival_location_id) disabled @endif>
                    <option value="">Select Factory</option>
                    @php
                        $selectedArrivalIds = $deliveryOrder->arrival_location_id ? explode(',', $deliveryOrder->arrival_location_id) : [];
                    @endphp
                    @if($deliveryOrder->location_id)
                        @foreach (get_arrivals_by($deliveryOrder->location_id) as $location)
                            <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedArrivalIds))>{{ $location->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Section:</label>
                <select name="storage_id[]" id="storages" class="form-control select2" multiple>
                    <option value="">Select Section</option>
                    @php
                        $selectedSubArrivalIds = $deliveryOrder->sub_arrival_location_id ? explode(',', $deliveryOrder->sub_arrival_location_id) : [];
                        $arrivalIds = $deliveryOrder->arrival_location_id ? explode(',', $deliveryOrder->arrival_location_id) : [];
                    @endphp
                    @if(!empty($arrivalIds))
                        @foreach (get_sub_arrivals_by_multiple($arrivalIds) as $location)
                            <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedSubArrivalIds))>{{ $location->name }} ({{ $location->arrivalLocation->name }})</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div id="exportOrderSnapshotEdit" class="snapshot-area" style="pointer-events: none;">
        <div class="row form-mar">
            <div class="col-8">
                <!-- Basic Information -->
                <div class="">
                    <div class="alert alert-info mt-3" id="qty_info_alert" style="padding: 10px; margin-bottom:15px; border-radius: 5px; border-left: 5px solid #17a2b8;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Quantity Capacity:</strong>
                                Total EO: <span id="lbl_total_eo_mt" class="font-weight-bold">{{ round($totalAllowedMt, 3) }}</span> MT &nbsp;|&nbsp;
                                Prev DO'd (Others): <span id="lbl_consumed_mt" class="font-weight-bold">{{ round($alreadyConsumedMt, 3) }}</span> MT &nbsp;|&nbsp;
                                Current Request: <span id="lbl_current_request_mt" class="font-weight-bold text-primary">{{ round($currentRequestMt, 3) }}</span> MT
                            </div>
                            <div class="badge badge-pill badge-light p-2" style="font-size: 1rem;">
                                Balance: <span id="lbl_remaining_mt" class="font-weight-bold">{{ round($remainingMt, 3) }}</span> MT
                            </div>
                        </div>
                    </div>

                    <div id="qty_error_msg" class="alert alert-danger mt-2" style="display: none; font-weight: bold; border-left: 5px solid #dc3545;">
                        <i class="fas fa-exclamation-triangle mr-2"></i> ERROR: Quantity exceeds Export Order capacity!
                    </div>

                    <h6 class="header-heading-sepration">Basic Information</h6>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label>Quotation#:</label>
                            <input type="text" id="snap_quotation_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Sauda#:</label>
                            <input type="text" id="snap_export_soda_edit" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label>Contract No#:</label>
                            <input type="text" id="snap_voucher_no_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Contract Date:</label>
                            <input type="date" id="snap_voucher_date_edit" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label>Reference No#:</label>
                            <input type="text" id="snap_contract_no_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label>Reference Date:</label>
                            <input type="date" id="snap_voucher_heading_edit" class="form-control" disabled>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4">
                            <label>Shipment Delivery Date From:</label>
                            <input type="date" id="snap_shipment_delivery_date_from_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Shipment Delivery Date To:</label>
                            <input type="date" id="snap_shipment_delivery_date_to_edit" class="form-control" disabled>
                        </div>

                        <div class="col-md-12 mt-2">
                            <label>Marking/labeling:</label>
                            <input type="text" id="snap_marking_labeling_edit" class="form-control" disabled>
                        </div>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class=" mt-3">
                    <label>Commodity/Product:</label>
                    <input type="text" id="snap_product_name_edit" class="form-control" disabled>
                    
                    <label class="mt-2">Visual Name:</label>
                    <input type="text" id="snap_visual_name_edit" class="form-control" disabled>
                </div>

                <!-- Specifications Section -->
                <div class="mt-3" id="snap_specificationsSection_edit" style="display: none;">
                    <h6 class="header-heading-sepration">Specifications</h6>
                    <div id="snap_productSpecs_edit">
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="header-heading-sepration">Commission</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <label>Commission (%):</label>
                            <input type="text" id="snap_commission_percentage_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Amt/Ton:</label>
                            <input type="text" id="snap_commission_amount_per_ton_edit" class="form-control" disabled>
                        </div>
                        <div class="col-md-4">
                            <label>Total Commission:</label>
                            <input type="text" id="snap_commission_edit" class="form-control" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <h6 class="header-heading-sepration">Export</h6>
                <div class="table-responsive">
                    <table class="table table-bordered spacing-table" style="margin-bottom:0; background: #fff;">
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">INCOTERMS</td>
                            <td style="width: 70%;"><input type="text" id="snap_incoterm_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PACKING TYPE</td>
                            <td style="width: 70%;"><input type="text" id="snap_packing_type_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TERM</td>
                            <td style="width: 70%;"><input type="text" id="snap_mode_of_term_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">MODE OF TRANSPORT</td>
                            <td style="width: 70%;"><input type="text" id="snap_mode_of_transport_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">ORIGIN</td>
                            <td style="width: 70%;"><input type="text" id="snap_origin_country_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF DISCHARGE</td>
                            <td style="width: 70%;"><input type="text" id="snap_port_of_discharge_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PORT OF LOADING</td>
                            <td style="width: 70%;"><input type="text" id="snap_port_of_loading_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">HS CODE</td>
                            <td style="width: 70%;"><input type="text" id="snap_hs_code_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PARTIAL PAYMENT</td>
                            <td style="width: 70%;"><input type="text" id="snap_partial_payment_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">TRANSHIPMENT</td>
                            <td style="width: 70%;"><input type="text" id="snap_transhipment_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PART SHIPMENT</td>
                            <td style="width: 70%;"><input type="text" id="snap_part_shipment_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">INSURANCE COVERED BY</td>
                            <td style="width: 70%;"><input type="text" id="snap_insurance_covered_by_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">ADVANCE PAYMENT(%)</td>
                            <td style="width: 70%;"><input type="text" id="snap_advance_payment_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">PAYMENT DAYS</td>
                            <td style="width: 70%;"><input type="text" id="snap_payment_days_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">CURRENCY</td>
                            <td style="width: 70%;"><input type="text" id="snap_currency_edit" class="form-control" disabled></td>
                        </tr>
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">RATE</td>
                            <td style="width: 70%;"><input type="text" id="snap_currency_rate_edit" class="form-control" disabled></td>
                        </tr>
                    </table>
                </div>
            </div>{{-- end col-4 --}}
        </div>{{-- end row --}}
    </div> <!-- End of exportOrderSnapshotEdit -->

    <!-- Packing Details (full width, below snapshot) -->
    <div class="row form-mar">
        <div class="col-md-12" id="packingItemsWrapper">
                <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                    Packing Details
                </h6>

                <div id="export-order-quantity-info" class="mb-3" style="display: none;"></div>

                <div id="packingItems">
                    <!-- Template for clone, kept visually hidden until populated -->
                    <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto" style="display:none;" id="dummyPackingRow">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Brand:</label>
                                <select name="packing_items[0][brand_id]" class="form-control select2" disabled>
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" >{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="packing_items[0][brand_id]" class="hidden-mirror" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Type/Product:</label>
                                <select name="packing_items[0][bag_product_id]" class="form-control select2" disabled>
                                    <option value="">Select Bag Type/Product</option>
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
        </div>{{-- end col-md-12 packingItemsWrapper --}}
    </div>{{-- end row --}}

    <!-- Hidden Template for Sub Packing Item -->
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

    <!-- Editable Remarks -->
    <div class="row form-mar mt-3">
        <div class="col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="3">{{ $deliveryOrder->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Delivery Order</button>
        </div>
    </div>
</form>

<script>
    // Location Selection Functions (Global - accessible from HTML onchange)
    function selectLocation(el) {
        const locationId = $(el).val();
        
        if (!locationId) {
            $("#arrivals").prop("disabled", true).empty();
            $("#storages").prop("disabled", true).empty();
            return;
        }

        $("#arrivals").prop("disabled", false);
        $.ajax({
            url: "{{ route('export.get-arrival-locations') }}",
            method: "GET",
            data: { location_id: locationId },
            dataType: "json",
            success: function(res) {
                $("#arrivals").empty();
                // Auto-select ALL factories
                res.forEach(loc => {
                    const option = new Option(loc.text, loc.id, true, true);
                    $("#arrivals").append(option);
                });
                $("#arrivals").select2();
                
                // Auto-populate and select all sections
                if (res.length > 0) {
                    const arrivalIds = res.map(loc => loc.id);
                    selectStorage(arrivalIds);
                }
            },
            error: function(error) {
                console.error("Error fetching arrival locations:", error);
            }
        });
    }

    function selectStorage(arrivalIds) {
        if (!arrivalIds || (Array.isArray(arrivalIds) && arrivalIds.length === 0)) {
            $("#storages").prop("disabled", true).empty();
            return;
        }

        // Handle both direct call with IDs and call from onchange
        const ids = Array.isArray(arrivalIds) ? arrivalIds : $(arrivalIds).val();
        
        if (!ids || ids.length === 0) {
            $("#storages").prop("disabled", true).empty();
            return;
        }

        $.ajax({
            url: "{{ route('export.get-sub-arrival-locations') }}",
            method: "GET",
            data: { arrival_id: ids },
            dataType: "json",
            success: function(res) {
                $("#storages").empty();
                // Auto-select ALL sections
                res.forEach(storage => {
                    const option = new Option(storage.text, storage.id, true, true);
                    $("#storages").append(option);
                });
                $("#storages").prop("disabled", false).select2();
            },
            error: function(error) {
                console.error("Error fetching sub-arrival locations:", error);
            }
        });
    }

    // DOM Ready Initialization
    $(document).ready(function() {
        // Render JSON dumped from backend for snapshot
        let snapshotData = @json($deliveryOrder->exportOrder);
        // Render relational packing items from DB
        let packingItems = @json($deliveryOrder->exportPackingItems);

        if (snapshotData) {
            populateSnapshotEdit(snapshotData, packingItems);
        }

        // Initialize Location Dropdowns for Edit Mode - Only initialize Select2, don't re-populate
        setTimeout(function() {
            // Just initialize Select2 for existing dropdowns, don't change their values
            $("#locations").select2();
            $("#arrivals").select2();
            $("#storages").select2();
        }, 100);

        function populateSnapshotEdit(data, packingItemsAuto) {
            $('#snap_quotation_edit').val(data.quotation ? (data.quotation.reference || '#' + data.quotation_id) + ' - ' + (data.quotation.product ? data.quotation.product.name : '') : (data.quotation_id ? '#' + data.quotation_id : '---'));
            $('#snap_export_soda_edit').val(data.export_soda ? (data.export_soda.voucher_no || '#' + data.export_soda_id) + ' - ' + (data.export_soda.product ? data.export_soda.product.name : '') : (data.export_soda_id ? '#' + data.export_soda_id : '---'));
            $('#snap_voucher_no_edit').val(data.voucher_no || '');
            $('#snap_voucher_date_edit').val(data.voucher_date ? data.voucher_date.split('T')[0] : '');
            $('#snap_contract_no_edit').val(data.contract_no || '');
            $('#snap_voucher_heading_edit').val(data.voucher_heading ? data.voucher_heading.split('T')[0] : '');
            $('#snap_shipment_delivery_date_from_edit').val(data.shipment_delivery_date_from ? data.shipment_delivery_date_from.split('T')[0] : '');
            $('#snap_shipment_delivery_date_to_edit').val(data.shipment_delivery_date_to ? data.shipment_delivery_date_to.split('T')[0] : '');
            $('#snap_marking_labeling_edit').val(data.marking_labeling || '');

            $('#snap_product_name_edit').val(data.product ? data.product.name : '');
            $('#snap_visual_name_edit').val(data.visual_name || '');

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
                $('#snap_productSpecs_edit').html(specHtml);
                $('#snap_specificationsSection_edit').show();
            } else {
                $('#snap_specificationsSection_edit').hide();
            }

            // Commission
            let totalMt = packingItemsAuto ? packingItemsAuto.reduce((sum, item) => sum + (parseFloat(item.metric_tons) || 0), 0) : 0;
            let commission = parseFloat(data.commission) || 0;
            let amtPerTon = totalMt > 0 ? (commission / totalMt) : (parseFloat(data.commission_amount_per_ton) || 0);

            $('#snap_commission_percentage_edit').val(data.commission_percentage || '');
            $('#snap_commission_amount_per_ton_edit').val(amtPerTon.toFixed(2));
            $('#snap_commission_edit').val(commission.toFixed(2));

            $('#snap_incoterm_edit').val(data.incoterm ? data.incoterm.name : '');
            $('#snap_packing_type_edit').val(data.packing_type || '');
            $('#snap_mode_of_term_edit').val(data.mode_of_term ? data.mode_of_term.name : '');
            $('#snap_mode_of_transport_edit').val(data.mode_of_transport ? data.mode_of_transport.name : '');
            $('#snap_origin_country_edit').val(data.origin_country ? data.origin_country.name : '');
            $('#snap_port_of_discharge_edit').val(data.port_of_discharge ? data.port_of_discharge.name : '');
            $('#snap_port_of_loading_edit').val(data.port_of_loading ? data.port_of_loading.name : '');
            $('#snap_hs_code_edit').val(data.hs_code ? data.hs_code.code : '');
            $('#snap_partial_payment_edit').val(data.partial_payment || '');
            $('#snap_transhipment_edit').val(data.transhipment || '');
            $('#snap_part_shipment_edit').val(data.part_shipment || '');
            $('#snap_insurance_covered_by_edit').val(data.insurance_covered_by || '');
            $('#snap_advance_payment_edit').val(data.advance_payment || '');
            $('#snap_payment_days_edit').val(data.payment_days || '');
            $('#snap_currency_edit').val(data.currency ? data.currency.currency_name : '');
            $('#snap_currency_rate_edit').val(data.currency_rate || '');

            // Populate relational packing items instead of data.packing_items from JSON
            $('#packingItemsWrapper').show();
            if (packingItemsAuto && packingItemsAuto.length > 0) {
                addPackingRowsFromExportOrder(packingItemsAuto);
                checkCapacity();
            } else {
                $('#packingItems').find('.packing-item:not(#dummyPackingRow)').remove();
                if ($('#packingItems').find('tbody.empty-placeholder').length === 0) {
                    $('#packingItems').append('<div class="text-center text-muted empty-placeholder p-4">No packing items found</div>');
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

                function setDisabledSelectValue(selector, varName) {
                    row.find(selector).val(varName);
                    row.find(selector).siblings('.hidden-mirror').val(varName);
                }

                setDisabledSelectValue(`select[name="packing_items[0][brand_id]"]`, item.brand_id);
                setDisabledSelectValue(`select[name="packing_items[0][bag_product_id]"]`, item.bag_type_id);
                setDisabledSelectValue(`select[name="packing_items[0][bag_condition_id]"]`, item.bag_condition_id);
                setDisabledSelectValue(`select[name="packing_items[0][bag_color_id]"]`, item.bag_color_id);
                setDisabledSelectValue(`select[name="packing_items[0][thread_color_id]"]`, item.thread_color_id);
                setDisabledSelectValue(`select[name="packing_items[0][stitching_id]"]`, item.stitching_id);
                
                row.find(`input[name="packing_items[0][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[0][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[0][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[0][extra_bags_percentage]"]`).val(item.extra_bags_percentage);
                row.find(`input[name="packing_items[0][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[0][total_bags]"]`).val((item.no_of_bags||0) + (item.extra_bags||0) + (item.empty_bags||0));
                row.find(`input[name="packing_items[0][total_kgs]"]`).val((item.metric_tons||0) * 1000);
                row.find(`input[name="packing_items[0][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[0][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[0][no_of_containers]"]`).val(item.no_of_containers);
                row.find(`input[name="packing_items[0][min_weight_empty_bags]"]`).val(item.min_weight_empty_bags);
                
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

                if(item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        let $template = $('.sub-packing-item-template tbody');
                        let subRowHtml = $template.html();
                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);

                        function setSubDisabledSelect(selector, val) {
                            subRow.find(selector).val(val);
                            subRow.find(selector).siblings('.hidden-mirror').val(val);
                        }
                        
                        setSubDisabledSelect(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`, sub.bag_type_id);

                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        // Can't show display size val purely from relational data unless eager loaded correctly, but it's fine for edit mode.

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
                
                row.find('select.select2').each(function() {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $(this).closest('.table-responsive').length ? $(this).closest('.table-responsive') : $('body')
                    });
                });
            });
        }
        
        // Setup Auto-calculation logic for packing items (Qty MT and Bags)
        $(document).off('input.exportDOEdit', '.no-of-bags, .metric-tons, .bag-size, .extra-bags, .empty-bags, .stuffing, .containers');
        $(document).on('input.exportDOEdit', '.no-of-bags, .metric-tons, .bag-size, .extra-bags, .empty-bags, .stuffing, .containers', function () {
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
                // When MT increases, stuffing should increase if Containers is fixed
                if (containers > 0) {
                    stuffing = metricTons / containers;
                    item.find('.stuffing').val(stuffing.toFixed(3));
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

            // Recalculate Total Bags (Crucial: User wants NO auto-increase of extra bags)
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
                    subRow.find('.sub-no-of-bags').val(masterNoOfBags);
                    
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

        // Auto-calculation logic for sub items (manual edits)
        $(document).off('input.exportDOEditSub', '.sub-empty-bags, .sub-extra-bags');
        $(document).on('input.exportDOEditSub', '.sub-empty-bags, .sub-extra-bags', function () {
            var subRow = $(this).closest('tr');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
            var extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;
            var totalBags = noOfBags + emptyBags + extraBags;
            subRow.find('.sub-total-bags').val(totalBags);
        });

        // Current request MT check
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
                $('#qty_error_msg').html(`<i class="fas fa-exclamation-triangle mr-2"></i> ERROR: ${errMsg}`).slideDown();
                $('.submitbutton').attr('disabled', true);
            } else {
                $('#lbl_remaining_mt').removeClass('text-danger').addClass('text-success');
                $('#qty_error_msg').slideUp();
                $('.submitbutton').attr('disabled', false);
            }
        }
    });
</script>
