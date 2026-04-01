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
                        <label>Buyer:</label>
                        <select name="buyer_id" id="buyer_id" class="form-control select2" required>
                            <option value="">Select Buyer</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Export Order:</label>
                        <select name="export_order_id" id="export_order_id" class="form-control select2" required>
                            <option value="">Select Export Order</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Form-E Select: <span class="text-danger">*</span></label>
                        <select name="export_form_e_id" id="export_form_e_id" class="form-control select2" required>
                            <option value="">Select Form-E</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="exportOrderSnapshot" style="pointer-events: none; opacity: 0.9;">

        <div class="row form-mar">
            <div class="col-8">
                <!-- Basic Information -->
                <div class="">
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

                {{-- Commission Section --}}
                <div class="mt-4">
                    <h6 class="header-heading-sepration">Commission</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Commission (%):</label>
                                <input type="number" data-name="commission_percentage" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amt/Ton:</label>
                                <input type="number" data-name="commission_amount_per_ton" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Commission:</label>
                                <input type="number" data-name="commission" class="form-control" step="0.01" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        <tr>
                            <td style="width: 30%; font-weight: bold; vertical-align: middle;">RATE</td>
                            <td style="width: 70%;">
                                <input type="text" data-name="currency_rate" class="form-control" readonly>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Packing Details -->
            <div class="col-md-12 mt-4">
                <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                    Packing Details
                </h6>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">Brand</th>
                                <th style="min-width: 150px;">Bag Type</th>
                                <th style="min-width: 130px;">Packing</th>
                                <th style="min-width: 110px;">Color</th>
                                <th style="min-width: 100px;">Packing Size (kg)</th>
                                <th style="min-width: 100px;">Qty (MT)</th>
                                <th style="min-width: 100px;">Bags</th>
                                <th style="min-width: 120px;">Stuffing (MT)</th>
                                <th style="min-width: 90px;">Containers</th>
                                <th style="min-width: 110px;">Rate/Ton</th>
                                <th style="min-width: 130px;">Amount</th>
                                <th style="min-width: 130px;">Amount (PKR)</th>
                            </tr>
                        </thead>
                        <tbody id="snap_packingItems">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
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
                <textarea name="remarks" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Delivery Order</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

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
                }
            });

            $.ajax({
                url: "{{ route('export.get-export-order-details', '') }}/" + orderId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        populateSnapshot(response.data);
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch order details", err);
                    alert("Failed to fetch export order details.");
                }
            });
        });

        function populateSnapshot(data) {
            // General structure fields mapped
            $('[data-name="quotation_id"]').html('<option value="'+data.quotation_id+'" selected></option>').trigger('change.select2');
            $('[data-name="export_soda_id"]').html('<option value="'+data.export_soda_id+'" selected></option>').trigger('change.select2');
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

            // Specs
            if(data.specifications && data.specifications.length > 0) {
                let specHtml = '<table class="table table-bordered table-sm">';
                data.specifications.forEach(spec => {
                    specHtml += `<tr><td width="30%"><strong>${spec.spec_name}</strong></td><td>${spec.spec_value}</td></tr>`;
                });
                specHtml += '</table>';
                $('#snap_productSpecs').html(specHtml);
                $('#snap_specificationsSection').show();
            } else {
                $('#snap_specificationsSection').hide();
            }
            
            $('[data-name="commission_percentage"]').val(data.commission_percentage);
            $('[data-name="commission_amount_per_ton"]').val(data.commission_amount_per_ton);
            $('[data-name="commission"]').val(data.commission);

            // Export Table fields static options
            $('[data-name="packing_type"]').val(data.packing_type).trigger('change.select2');
            $('[data-name="partial_payment"]').val(data.partial_payment).trigger('change.select2');
            $('[data-name="transhipment"]').val(data.transhipment).trigger('change.select2');
            $('[data-name="part_shipment"]').val(data.part_shipment).trigger('change.select2');
            $('[data-name="insurance_covered_by"]').val(data.insurance_covered_by).trigger('change.select2');
            
            $('[data-name="advance_payment"]').val(data.advance_payment);
            $('[data-name="payment_days"]').val(data.payment_days);
            $('[data-name="currency_rate"]').val(data.currency_rate);

            // Packing items
            let packingHtml = '';
            if (data.packing_items && data.packing_items.length > 0) {
                data.packing_items.forEach((item, index) => {
                    packingHtml += `
                        <tr class="packing-item">
                            <td class="p-2"><input type="text" class="form-control" value="${item.brand ? item.brand.name : ''}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="${item.bag_type ? item.bag_type.name : ''}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="${item.bag_packing ? item.bag_packing.name : ''}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="${item.bag_color ? item.bag_color.color : ''}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.bag_size || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.metric_tons || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.no_of_bags || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.stuffing_in_container || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.no_of_containers || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.rate || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.amount || 0}" readonly></td>
                            <td class="p-2"><input type="number" class="form-control" value="${item.amount_pkr || 0}" readonly></td>
                        </tr>
                    `;
                });
            } else {
                packingHtml = '<tr><td colspan="12" class="text-center">No packing items found</td></tr>';
            }
            $('#snap_packingItems').html(packingHtml);
        }
    });
</script>