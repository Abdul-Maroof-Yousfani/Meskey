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
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Buyer:</label>
                        <select name="buyer_id" id="buyer_id_edit" class="form-control" disabled>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" {{ $deliveryOrder->buyer_id == $buyer->id ? 'selected' : '' }}>{{ $buyer->name }}</option>
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
            </div>
        </div>
    </div>

    <!-- Export Order Snapshot Area (Read-Only) -->
    <div id="exportOrderSnapshotEdit" class="snapshot-area" style="pointer-events: none;">

        <div class="row form-mar" style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
            <div class="col-8">
                <!-- Basic Information -->
                <div class="">
                    <h6 class="header-heading-sepration">Basic Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Sauda# / Contract No#:</label>
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
            </div>

            <div class="col-md-12 mt-4">
                <h6 class="header-heading-sepration">Packing Details</h6>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="snap_packingTable_edit" style="background: #fff;">
                        <thead>
                            <tr>
                                <th>Brand</th>
                                <th>Bag Type</th>
                                <th>Packing</th>
                                <th>Color</th>
                                <th>Packing Size (kg)</th>
                                <th>Qty (MT)</th>
                                <th>Bags</th>
                                <th>Stuffing (MT)</th>
                                <th>Containers</th>
                                <th>Rate/Ton</th>
                                <th>Amount</th>
                                <th>Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody id="snap_packingItems_edit">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
    $(document).ready(function() {
        // Render JSON dumped from backend
        let snapshotData = @json($deliveryOrder->export_snapshot);

        if (snapshotData) {
            populateSnapshotEdit(snapshotData);
        }

        function populateSnapshotEdit(data) {
            $('#snap_voucher_no_edit').val(data.voucher_no || '');
            $('#snap_voucher_date_edit').val(data.voucher_date ? data.voucher_date.split('T')[0] : '');
            $('#snap_contract_no_edit').val(data.contract_no || '');
            $('#snap_voucher_heading_edit').val(data.voucher_heading ? data.voucher_heading.split('T')[0] : '');
            $('#snap_shipment_delivery_date_from_edit').val(data.shipment_delivery_date_from ? data.shipment_delivery_date_from.split('T')[0] : '');
            $('#snap_shipment_delivery_date_to_edit').val(data.shipment_delivery_date_to ? data.shipment_delivery_date_to.split('T')[0] : '');
            $('#snap_marking_labeling_edit').val(data.marking_labeling || '');

            $('#snap_product_name_edit').val(data.product ? data.product.name : '');
            $('#snap_visual_name_edit').val(data.visual_name || '');

            if (data.specifications && data.specifications.length > 0) {
                let specHtml = '<table class="table table-bordered table-sm">';
                data.specifications.forEach(spec => {
                    specHtml += `<tr><td width="30%"><strong>${spec.spec_name}</strong></td><td>${spec.spec_value}</td></tr>`;
                });
                specHtml += '</table>';
                $('#snap_productSpecs_edit').html(specHtml);
                $('#snap_specificationsSection_edit').show();
            } else {
                $('#snap_specificationsSection_edit').hide();
            }

            } else {
                $('#snap_specificationsSection_edit').hide();
            }

            $('#snap_commission_percentage_edit').val(data.commission_percentage || '');
            $('#snap_commission_amount_per_ton_edit').val(data.commission_amount_per_ton || '');
            $('#snap_commission_edit').val(data.commission || '');

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

            let packingHtml = '';
            if (data.packing_items && data.packing_items.length > 0) {
                data.packing_items.forEach(item => {
                    packingHtml += `
                        <tr>
                            <td>${item.brand ? item.brand.name : ''}</td>
                            <td>${item.bag_type ? item.bag_type.name : ''}</td>
                            <td>${item.bag_packing ? item.bag_packing.name : ''}</td>
                            <td>${item.bag_color ? item.bag_color.color : ''}</td>
                            <td>${item.bag_size || ''}</td>
                            <td>${item.metric_tons || ''}</td>
                            <td>${item.no_of_bags || ''}</td>
                            <td>${item.stuffing_in_container || ''}</td>
                            <td>${item.no_of_containers || ''}</td>
                            <td>${item.rate || ''}</td>
                            <td>${item.amount || ''}</td>
                            <td>${item.amount_pkr || ''}</td>
                        </tr>
                    `;
                });
            } else {
                packingHtml = '<tr><td colspan="11" class="text-center text-muted">No packing items found.</td></tr>';
            }
            $('#snap_packingItems_edit').html(packingHtml);
        }
    });
</script>
