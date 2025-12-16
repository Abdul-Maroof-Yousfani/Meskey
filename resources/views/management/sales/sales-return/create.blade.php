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

<form action="{{ route('sales.sales-return.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-return.list') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <!-- Row 1: Customer, Invoice Address, SI No -->
            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Customer:<span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" onchange="get_sale_invoices()"
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
                        <label class="form-label">SR No:<span class="text-danger">*</span></label>
                        <input type="text" name="sr_no" id="sr_no" class="form-control" readonly>
                    </div>
                </div>

                 <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:<span class="text-danger">*</span></label>
                        <input type="date" name="date" onchange="getNumber()" id="date" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Row 2: Company Location, Arrival Location, Date -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Company Location:<span class="text-danger">*</span></label>
                        <select name="company_location_id" id="locations" onchange="selectLocation(this); get_sale_invoices()"
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
                        <label class="form-label">Arrival Location:<span class="text-danger">*</span></label>
                        <select name="arrival_location_id" id="arrivals" onchange="get_sale_invoices()"
                            class="form-control select2">
                            <option value="">Select Arrival Location</option>
                            @foreach(get_arrival_locations() as $arrival_location)
                                <option value="{{ $arrival_location->id }}">{{ $arrival_location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Storage:<span class="text-danger">*</span></label>
                        <select name="storage_location_id" id="storages" onchange="get_sale_invoices()"
                            class="form-control select2">
                            <option value="">Select Arrival Location</option>
                            @foreach(get_sub_arrival_locations() as $sub_arrival_location)
                                <option value="{{ $sub_arrival_location->id }}">{{ $sub_arrival_location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: Reference Number, Sauda Type, DC Numbers -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Enter reference number">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Contract Type:<span class="text-danger">*</span></label>
                        <select name="contract_type" id="sauda_type" class="form-control select2">
                            <option value="">Select Sauda Type</option>
                            <option value="pohanch">Pohanch</option>
                            <option value="x-mill">X-mill</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">SI Numbers:</label>
                        <select name="si_no[]" id="si_no" onchange="get_items(this)" class="form-control select2" multiple>
                            <option value="">Select Sale Invoice</option>
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
    let salesInvoiceRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
    });

    // Legacy function for backward compatibility
    function calc(el) {
        calculateRow(el);
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
            url: "{{ route('sales.get.invoice-items') }}",
            method: "GET",
            data: {
                sale_invoice_ids: $(el).val(),
            },
            dataType: "html",
            success: function(res) {
                $("#siTableBody").empty();
                $("#siTableBody").html(res);
                console.log(res);
            },
            error: function(error) {
                console.error("Error:", error);
            }
        });
    }

    function get_sale_invoices() {
        const customer_id = $("#customer_id").val();
        const location_id = $("#locations").val();
        const arrival_location_id = $("#arrivals").val();
        const storage_id = $("#storages").val();

        // if (!customer_id || !location_id || !arrival_location_id) return;

        $.ajax({
            url: "{{ route('sales.get.invoice-numbers') }}",
            method: "GET",
            data: {
                customer_id,
                location_id,
                arrival_location_id,
                storage_id
            },
            dataType: "json",
            success: function(res) {
                console.log(res);
                $("#si_no").empty();
                $("#si_no").append(`<option value=''>Select Sale Invoices</option>`)

                res.forEach(sale_invoice => {
                    $("#si_no").append(`
                        <option value="${sale_invoice.id}">
                            ${sale_invoice.text}
                        </option>
                    `);
                });

                $("#si_no").select2();
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
        const packing = parseFloat(packingInput.val()) || 0;
        const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
        const rate = parseFloat(rateInput.val()) || 0;
        const discountPercent = parseFloat(discountPercentInput.val()) || 0;
        const gstPercent = parseFloat(gstPercentInput.val()) || 0;

        // Calculate Qty = Packing * No of Bags
        const result =  parseFloat(packingInput.val()) / parseFloat(qtyInput.val()).toFixed();
        noOfBagsInput.val(result);
      
        // Calculate Gross Amount = Qty * Rate
        const grossAmount = result * rate;
        
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

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-return.getNumber') }}",
            method: "GET",
            data: {
                date: $("#date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#sr_no").val(res.sr_no)
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

