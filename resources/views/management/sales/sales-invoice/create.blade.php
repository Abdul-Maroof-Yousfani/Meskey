<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<form action="{{ route('sales.sales-invoice.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-invoice.list') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <!-- Row 1: Customer, Invoice Address, SI No -->
            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Customer:<span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" onchange="get_delivery_challans()"
                            class="form-control select2">
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Invoice Address:</label>
                        <textarea name="invoice_address" id="invoice_address" class="form-control" rows="1"
                            placeholder="Enter invoice address"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">SI No:<span class="text-danger">*</span></label>
                        <input type="text" name="si_no" id="si_no" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- Row 2: Company Location, Arrival Location, Date -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Company Location:<span class="text-danger">*</span></label>
                        <select name="locations" id="locations" onchange="selectLocation(this);"
                            class="form-control select2">
                            <option value="">Select Company Location</option>
                            @foreach (get_locations() ?? [] as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:<span class="text-danger">*</span></label>
                        <select name="arrival_locations" id="arrivals" onchange="selectStorage(this);"
                            class="form-control select2">
                            <option value="">Select Factory</option>
                        </select>
                    </div>
                </div>
              
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Invoice Date:<span class="text-danger">*</span></label>
                        <input 
                            type="date" 
                            name="invoice_date" 
                            onchange="getNumber()" 
                            id="invoice_date"
                            class="form-control"
                            value="{{ date('Y-m-d') }}"
                            readonly
                        >
                    </div>
                </div>
            </div>

            <!-- Row 3: Reference Number, Sauda Type, DC Numbers -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control"
                            placeholder="Enter reference number">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Sauda Type:<span class="text-danger">*</span></label>
                        <select name="sauda_type" id="sauda_type" class="form-control select2" onchange="get_delivery_challans()">
                            <option value="">Select Sauda Type</option>
                            <option value="pohanch">Pohanch</option>
                            <option value="x-mill">X-mill</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DC Numbers:</label>
                        <select name="dc_no[]" id="dc_no" onchange="get_items(this)" class="form-control select2"
                            multiple>
                            <option value="">Select Delivery Challans</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 4: Remarks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label">Remarks:</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Enter remarks"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInvoiceTable" style="min-width:2200px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Gross Amount</th>
                            <th>Discount %</th>
                            <th>Discount Amount</th>
                            <th>Amount</th>
                            <th>GST %</th>
                            <th>GST Amount</th>
                            <th>Net Amount</th>
                            <th>Line Desc</th>
                            <th>Truck No</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="siTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    salesInvoiceRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();

        getNumber();
    });

    function selectStorage(el) {
        const arrival = $(el).val();
        console.log(arrival);
        if (!arrival) {
            $("#sections").prop("disabled", true);
            $("#sections").empty();
            return;
        } else {
            // get.arrival-locations; send request to this url
            $("#sections").prop("disabled", false);
            $.ajax({
                url: "{{ route('sales.get.storage-locations') }}",
                method: "GET",
                data: {
                    arrival_id: arrival
                },
                dataType: "json",
                success: function(res) {
                    console.log(res);
                    $("#sections").empty();
                    $("#sections").append(`<option value=''>Select Storage</option>`)
                    res.forEach(loc => {
                        $("#sections").append(`
                        <option value="${loc.id}">
                            ${loc.text}
                        </option>
                    `);
                    });

                    $("#sections").select2();
                },
                error: function(error) {

                }
            });
        }
    }

    function selectLocation(el) {
        const company = $(el).val();

        if (!company) {
            $("#arrivals").prop("disabled", true);
            $("#arrivals").empty();
            return;
        } else {
            $("#arrivals").prop("disabled", false);
            $.ajax({
                url: "{{ route('sales.get.arrival-locations') }}",
                method: "GET",
                data: {
                    location_id: company
                },
                dataType: "json",
                success: function(res) {
                    $("#arrivals").empty();
                    $("#arrivals").append(`<option value=''>Select Arrival Location</option>`)

                    res.forEach(location => {
                        $("#arrivals").append(`
                            <option value="${location.id}">
                                ${location.text}
                            </option>
                        `);
                    });

                    $("#arrivals").select2();
                },
                error: function(error) {
                    console.error("Error:", error);
                }
            });
        }
    }

    function get_items(el) {
        const delivery_challans = $(el).val();

        if (!delivery_challans || delivery_challans.length === 0) {
            $("#siTableBody").empty();
            return;
        }

        $.ajax({
            url: "{{ route('sales.get.sales-invoice.get-items') }}",
            method: "GET",
            data: {
                delivery_challan_ids: $(el).val(),
            },
            dataType: "html",
            success: function(res) {
                $("#siTableBody").empty();
                $("#siTableBody").html(res);
                $(".select2").select2();
            },
            error: function(error) {
                console.error("Error:", error);
            }
        });
    }

    function get_delivery_challans() {
        const customer_id = $("#customer_id").val();
        const location_id = $("#locations").val();
        const arrival_location_id = $("#arrivals").val();

        // if (!customer_id || !location_id || !arrival_location_id) return;

        $.ajax({
            url: "{{ route('sales.get.sales-invoice.get-dc') }}",
            method: "GET",
            data: {
                customer_id: $("#customer_id").val(),
                company_location_id: $("#locations").val(),
                arrival_location_id: $("#arrivals").val(),
                sauda_type: $("#sauda_type").val()
            },
            dataType: "json",
            success: function(res) {
                $("#dc_no").empty();
                $("#dc_no").append(`<option value=''>Select Delivery Challan</option>`)

                res.forEach(delivery_challan => {
                    $("#dc_no").append(`
                        <option value="${delivery_challan.id}">
                            ${delivery_challan.text}
                        </option>
                    `);
                });

                $("#dc_no").select2();
            },
            error: function(error) {
                console.error("Error:", error);
            }
        });
    }

    function addRow() {
        let index = salesInvoiceRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td style="min-width: 200px;">
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="dc_data_id[]" value="">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="packing[]" id="packing_${index}" onkeyup="calculateRow(this)" class="form-control packing" step="0.01" min="0">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="no_of_bags[]" id="no_of_bags_${index}" onkeyup="calculateRow(this)" class="form-control no_of_bags" step="0.01" min="0">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="qty[]" id="qty_${index}" class="form-control qty" step="0.01" min="0" readonly>
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calculateRow(this)" class="form-control rate" step="0.01" min="0">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="gross_amount[]" id="gross_amount_${index}" class="form-control gross_amount" readonly>
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="discount_percent[]" id="discount_percent_${index}" onkeyup="calculateRow(this)" class="form-control discount_percent" step="0.01" min="0" max="100" value="0">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="discount_amount[]" id="discount_amount_${index}" class="form-control discount_amount" readonly>
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="gst_percent[]" id="gst_percent_${index}" onkeyup="calculateRow(this)" class="form-control gst_percent" step="0.01" min="0" value="0">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="gst_amount[]" id="gst_amount_${index}" class="form-control gst_amount" readonly>
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="net_amount[]" id="net_amount_${index}" class="form-control net_amount" readonly>
            </td>
            <td style="min-width: 150px;">
                <input type="text" name="line_desc[]" id="line_desc_${index}" class="form-control line_desc">
            </td>
            <td style="min-width: 120px;">
                <input type="text" name="truck_no[]" id="truck_no_${index}" class="form-control truck_no">
            </td>
            <td style="min-width: 80px;">
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#siTableBody').append(row);
        $(`#item_id_${index}`).select2();
    }

    function removeRow(index) {
        $('#row_' + index).remove();
    }

    function round(num, decimals = 2) {
        return Number(Math.round(num + "e" + decimals) + "e-" + decimals);
    }

    function calculateRow(el) {
        const row = $(el).closest("tr");
        // Get input elements
        const packingInput = row.find(".packing");
        const noOfBagsInput = row.find(".no_of_bags");
        const qtyInput = row.find(".qty");
        const rateInput = row.find(".rate");
        const grossAmountInput = row.find(".gross_amount");
        const discountPercentInput = row.find(".discount_percent");
        const discountAmountInput = row.find(".discount_amount");
        const amountInput = row.find(".amount");
        const gstPercentInput = row.find(".gst_percent");
        const gstAmountInput = row.find(".gst_amount");
        const netAmountInput = row.find(".net_amount");

        // Get values
        let packing = parseFloat(packingInput.val()) || 0;
        let noOfBags = parseFloat(noOfBagsInput.val()) || 0;
        let qty = parseFloat(qtyInput.val()) || 0;
        let rate = parseFloat(rateInput.val()) || 0;
        let discountPercent = parseFloat(discountPercentInput.val()) || 0;
        let gstPercent = parseFloat(gstPercentInput.val()) || 0;

        // Calculate based on what changed
        if ($(el).hasClass("packing") || $(el).hasClass("no_of_bags")) {
            // When packing or no_of_bags changes, calculate qty
            qty = packing * noOfBags;
            qtyInput.val(round(qty));
        } else if ($(el).hasClass("qty")) {
            // When qty changes, calculate no_of_bags (if packing > 0)
            if (packing > 0) {
                noOfBags = qty / packing;
                noOfBagsInput.val(Math.round(noOfBags));
            }
        }

        // Always recalculate amounts based on current values
        const grossAmount = qty * rate;
        grossAmountInput.val(round(grossAmount));

        // Calculate Discount Amount = (Discount % / 100) * Gross Amount
        const discountAmount = (discountPercent / 100) * grossAmount;
        discountAmountInput.val(round(discountAmount));

        // Calculate Amount = Gross Amount - Discount Amount
        const amount = grossAmount - discountAmount;
        amountInput.val(round(amount));

        // Calculate GST Amount = (GST % / 100) * Amount
        const gstAmount = (gstPercent / 100) * amount;
        gstAmountInput.val(round(gstAmount));

        // Calculate Net Amount = Amount + GST Amount
        const netAmount = amount + gstAmount;
        netAmountInput.val(round(netAmount));
    }

    // Legacy function for backward compatibility
    function calc(el) {
        calculateRow(el);
    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-invoice.getNumber') }}",
            method: "GET",
            data: {
                invoice_date: $("#invoice_date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#si_no").val(res.si_no)
            },
            error: function(error) {
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    function validateBalance(el) {
        const row = $(el).closest("tr");
        const maxBalance = parseFloat(row.find(".max_balance").val()) || 0;
        const noOfBags = parseFloat($(el).val()) || 0;

        if (noOfBags > maxBalance) {
            $(el).val(maxBalance);
            toastr.warning(`Cannot exceed available balance of ${maxBalance} bags`);
            calculateRow(el);
        }

        if (noOfBags < 0) {
            $(el).val(0);
            calculateRow(el);
        }
    }
</script>
